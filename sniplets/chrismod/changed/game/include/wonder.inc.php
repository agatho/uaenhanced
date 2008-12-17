<?php
init_Wonders();

function wonder_getActiveWondersForCaveID($caveID, $db) {
  $query =
    "SELECT *,  ".
    "DATE_FORMAT(end, '%d.%m.%Y %H:%i:%s') AS end_time ".
    "FROM ActiveWonder ".
    "WHERE caveID = '$caveID' ".
    "ORDER BY end";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return ;
  }

  $wonders = array();
  while($row = $result->nextRow(MYSQL_ASSOC)) {
    array_push($wonders, $row);
  }

  return $wonders;
}

function wonder_recalc($caveID, $db) {
  global
    $effectTypeList;

  $fields = array();
  foreach($effectTypeList AS $effectID => $data) {
    array_push($fields, 
	       "SUM(".$data->dbFieldName.") AS ".$data->dbFieldName);
  }

  $select = implode(", ", $fields);

  $query = 
    "SELECT $fields ".
    "FROM ActiveWonder ".
    "WHERE caveID = '$caveID'";

  if (!($result = $db->query($query))) {
    echo ("Error: Couldn't get ActiveWonder entries for the specified cave.");
    exit -1;
  }
  if (!($row = $result->nextRow())) {
    echo ("Error: Result was empty when trying to get event.");
    exit -1;
  }

  $effects = array();
  foreach($effectTypeList AS $effectID => $data) {
    $effects[$effectID] = $row[$data->dbFieldName];
  }

  return $effects;
}

function wonder_createMessage($message, $data, $deltas) {
  if ($message[type] == "none") {
    return "";
  }

  $text = 
    "In der Siedlung $data[name] <b>($data[xCoord]/$data[yCoord])</b>:<br>";
  $text.= $message[message];

  if ($message[type] == "note") {
  
    $text.= "<br><p>Einer Eurer Schamanen berichtet Euch von folgenden Wirkungen, ".
          "da eine göttliche Eingebung ausbleibt. Nun ja, an der Vollständigkeit und ".
	  "Korrektheit seines Berichts hegt Ihr, aus Erfahrung schlau geworden, ".
	  "Zweifel.</p><table>\n";
	  
    foreach($deltas AS $field => $data) {
      if (rand(0, 10) < 6) {
        $value = rand(0,600) / 100. - 3.;
	$value = $value < 0. ? 1 / (1-$value) : 1+$value;
	$value *=$data[delta];
        $text.= "<tr><td>$data[name]:</td><td>".(int)($value)."</td></tr>\n";
      }
    }
    $text.="</table>\n";      
    return $text;
  }

  $text.= "<br><p>Es zeigen sich folgende Wirkungen:</p><table>\n";

  // $message[type] == "detailed"

  foreach($deltas AS $field => $data) {
    if ($data[delta] != 0) {
      $text.="<tr><td>$data[name]:</td><td>$data[delta]</td></tr>";
    }
  }

  $text.= "</table>\n";
  
  return $text;
}

function wonder_createEndMessage($message, $data, $deltas) {
  $message[message] = "Ein Zauber verliert seine Wirkung.";
  
  return wonder_createMessage($message, $data, $deltas);
}

function wonder_processOrder($playerID, $wonderID, $caveID, $coordX, $coordY, 
			     $caveData, $db) {

  global 
    $defenseSystemTypeList,
    $unitTypeList,
    $buildingTypeList,
    $scienceTypeList,
    $resourceTypeList,
    $wonderTypeList;

// ADDED by chris--- for farmschutz: protection_end in sql

  // check the target cave
  $query = 
    "SELECT playerID, caveID, protection_end FROM Cave ".
    "WHERE xCoord = '$coordX' ".
    "AND yCoord = '$coordY'";
  
  if (!($result=$db->query($query)) || !($targetData = $result->nextRow())) {
    return -3;
  }

// ADDED by chris--- for farmschutz

  if (date("YmdHis", time()) < $targetData[protection_end]) {
    return -4;
  }

// ----------------------------------

  $targetID = $targetData[caveID];

  // check, if cave allowed

  if ($wonderTypeList[$wonderID]->target == "same") {
    $allowed = $caveID == $targetID;
  }
  else if ($wonderTypeList[$wonderID]->target == "own") {
    $allowed = $playerID == $targetData[playerID];
  }
  else if ($wonderTypeList[$wonderID]->target == "other") {
    $allowed = $playerID != $targetData[playerID];
  }
  else {      // $wonderTypeList[$wonderID]->target == "all"
    $allowed = 1;
  }
  if (! $allowed) {
    return -2;
  }

  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '$caveID' ");

  // get all the resource costs
  foreach ($wonderTypeList[$wonderID]->resourceProductionCost 
	   as $key => $value)
  {
    if ($value != "" && $value != "0") {
      $formula = formula_parseToSQL($value);

      $dbField = $resourceTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - ({$formula})");
      array_push($setBack, "{$dbField} = {$dbField} + ({$formula})");
      array_push($where,   "{$dbField} >= ({$formula})");
    }
  }

  // get all the unit costs
  foreach ($wonderTypeList[$wonderID]->unitProductionCost 
	   as $key => $value) 
  {
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $unitTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }

  // generate SQL
  if (sizeof($set)){
    $set     = implode(", ", $set);
    $set     = "UPDATE Cave SET $set ";
    $setBack = implode(", ", $setBack);
    $setBack = "UPDATE Cave SET $setBack WHERE caveID = '$caveID'";
  }
  // generate dependecies
  foreach($wonderTypeList[$wonderID]->buildingDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$buildingTypeList[$key]->dbFieldName} >= $value");

  foreach($wonderTypeList[$wonderID]->maxBuildingDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$wonderTypeList[$key]->dbFieldName} <= $value");

  foreach($wonderTypeList[$wonderID]->defenseSystemDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, 
		 "{$defenseSystemTypeList[$key]->dbFieldName} >= $value");
  foreach($wonderTypeList[$wonderID]->maxDefenseSystemDepList 
	  as $key => $value)
    if ($value != -1)
      array_push($where, 
		 "{$defenseSystemTypeList[$key]->dbFieldName} <= $value");

  foreach($wonderTypeList[$wonderID]->resourceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} >= $value");
  foreach($wonderTypeList[$wonderID]->maxResourceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} <= $value");

  foreach($wonderTypeList[$wonderID]->scienceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} >= $value");
  foreach($wonderTypeList[$wonderID]->maxScienceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} <= $value");

  foreach($wonderTypeList[$wonderID]->unitDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$unitTypeList[$key]->dbFieldName} >= $value");
  foreach($wonderTypeList[$wonderID]->maxUnitDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$unitTypeList[$key]->dbFieldName} <= $value");

  $where   = implode(" AND ", $where);

  if (!$db->query($set.$where) || $db->affected_rows() != 1) {
    return 0;
  }

  // calculate the chance and evaluate into $chance
  if ($chance_formula = $wonderTypeList[$wonderID]->chance) {
    $chance_eval_formula = formula_parseToPHP($chance_formula, '$caveData');

    $chance_eval_formula="\$chance=$chance_eval_formula;";
    eval($chance_eval_formula);
  }

  // does the wonder fail?
//  if ((double)rand() / (double)getRandMax() > $chance) {
srand ((double)microtime()*1000000);
$wond1 = (double)rand();
$wond2 = (double)getRandMax();
//echo $wond1." / ".$wond2." = ".$wond1/$wond2." -- ". $chance."<br>";
if ($wond1 / $wond2 > $chance) {
    return 2;          // wonder did fail
  }

  // schedule the wonder's impacts
  
  // create a random factor between -0.3 and +0.3
  $delayRandFactor = (rand(0,getrandmax()) / getrandmax()) * 0.6 - 0.3;
  // now calculate the delayDelta depending on the first imact's delay
  $delayDelta = 
    $wonderTypeList[$wonderID]->impactList[0][delay] * $delayRandFactor;

  foreach($wonderTypeList[$wonderID]->impactList AS $impactID => $impact) {
    $delay = (int)(($delayDelta + $impact[delay]) * WONDER_TIME_BASE_FACTOR);

    $query =
      "INSERT INTO Event_wonder ".
      "(casterID, sourceID, targetID, wonderID, impactID, event_start, ".
      "event_end) ".
      "VALUES ('$playerID', '$caveID', '$targetID', '$wonderID', ".
      "'$impactID', NOW()+0, (NOW() + INTERVAL $delay SECOND)+0)";

    if (!$db->query($query)){
      $db->query($setBack);
      return -1;
    }
  }

  // create messages
  $sourceMessage = 
    "Sie haben auf die Siedlung in $coordX/$coordY einen Zauber ".
    $wonderTypeList[$wonderID]->name." erwirkt.";
  $targetMessage =
    "Der Besitzer der Siedlung in $caveData[xCoord]/$caveData[yCoord] ".
    "hat auf Ihre Siedlung in $coordX/$coordY einen Zauber gewirkt.";

  messages_sendSystemMessage($playerID, 9, 
			     "Zauber erwirkt auf $coordX/$coordY",
			     $sourceMessage, $db);
  messages_sendSystemMessage($targetData[playerID], 9, 
			     "Zauber!",
			     $targetMessage, $db);

  return 1;
}
?>
