<?
/*
 * wonder.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class WonderTarget {

  function getWonderTargets(){
    static $result = NULL;

    if ($result === NULL){
      $result = array("same"  => _('Wirkungshöhle'),
                      "own"   => _('eigene Höhlen'),
                      "other" => _('fremde Höhlen'),
                      "all"   => _('jede Höhle'));
    }

    return $result;
  }
}

init_Wonders();

function wonder_getActiveWondersForCaveID($caveID, $db) {
  $query = "SELECT * ".
           "FROM Event_wonderEnd ".
           "WHERE caveID = '$caveID' ".
           "ORDER BY end";

  if (!($result = $db->query($query)) || ($result->isEmpty()))
    return;

  $wonders = array();
  while($row = $result->nextRow(MYSQL_ASSOC)){
    $row['end_time'] = time_formatDatetime($row['end']);
    $wonders[] = $row;
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

  $fields = implode(", ", $fields);

  $query =
    "SELECT $fields ".
    "FROM Event_wonderEnd ".
    "WHERE caveID = '$caveID'";

  if (!($result = $db->query($query))) {
    echo ("Error: Couldn't get Event_wonderEnd entries for the specified cave.");
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

function wonder_processOrder($playerID, $wonderID, $caveID, $coordX, $coordY,
           $caveData, $db) {

  global
    $defenseSystemTypeList,
    $unitTypeList,
    $buildingTypeList,
    $scienceTypeList,
    $resourceTypeList,
    $wonderTypeList,
    $WONDERRESISTANCE;

  if ($wonderTypeList[$wonderID]->target == "same") {
    $targetID = $caveID;
    $query = 
      "SELECT * FROM Cave ".
      "WHERE caveID = '$targetID' ";
    if (!($result=$db->query($query)) || !($targetData = $result->nextRow())) {
      return -3;
    }
    $coordX = $targetData['xCoord'];
    $coordY = $targetData['yCoord'];
  }
  else {

    // check the target cave
    $query =
      "SELECT * FROM Cave ".
      "WHERE xCoord = '$coordX' ".
      "AND yCoord = '$coordY'";

    if (!($result=$db->query($query)) || !($targetData = $result->nextRow())) {
      return -3;
    }
    $targetID = $targetData['caveID'];
  }

  // check, if cave allowed

  if ($wonderTypeList[$wonderID]->target == "own") {
    $allowed = $playerID == $targetData['playerID'];
  }
  else if ($wonderTypeList[$wonderID]->target == "other") {
    $allowed = $playerID != $targetData['playerID'];
  }
  else {      // $wonderTypeList[$wonderID]->target == "all"  or == "same"
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

  foreach ($wonderTypeList[$wonderID]->buildingProductionCost
     as $key => $value)
  {
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $buildingTypeList[$key]->dbFieldName;

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

  // if this wonder is offensive
  // calculate the wonder resistance and evaluate into $resistance
  // TODO: Wertebereich der Resistenz ist derzeit 0 - 1, also je höher desto resistenter
  if ($wonderTypeList[$wonderID]->offensiveness == "offensive"){
    $resistance_eval_formula = formula_parseToPHP($WONDERRESISTANCE, '$targetData');
    $resistance_eval_formula = "\$resistance=$resistance_eval_formula;";
    eval($resistance_eval_formula);
  } else {
    $resistance = 0.0;
  }

  // does the wonder fail?
  if (((double)rand() / (double)getRandMax()) > ($chance - $resistance)) {
    return 2;          // wonder did fail
  }

  // schedule the wonder's impacts

  // create a random factor between -0.3 and +0.3
  $delayRandFactor = (rand(0,getrandmax()) / getrandmax()) * 0.6 - 0.3;
  // now calculate the delayDelta depending on the first impact's delay
  $delayDelta =
    $wonderTypeList[$wonderID]->impactList[0]['delay'] * $delayRandFactor;

  foreach($wonderTypeList[$wonderID]->impactList AS $impactID => $impact) {
    $delay = (int)(($delayDelta + $impact['delay']) * WONDER_TIME_BASE_FACTOR);

    $now = time();
    $query = sprintf("INSERT INTO Event_wonder (casterID, sourceID, targetID, ".
                     "wonderID, impactID, `start`, `end`) ".
                     "VALUES (%d, %d, %d, %d, %d, '%s', '%s')",
                     $playerID, $caveID, $targetID, $wonderID, $impactID,
                     time_toDatetime($now),
                     time_toDatetime($now + $delay));

    if (!$db->query($query)){
      $db->query($setBack);
      return -1;
    }
  }

  // create messages
  $sourceMessage =
    "Sie haben auf die H&ouml;hle in $coordX/$coordY ein Wunder ".
    $wonderTypeList[$wonderID]->name." erwirkt.";
  $targetMessage =
    "Der Besitzer der H&ouml;hle in {$caveData['xCoord']}/{$caveData['yCoord']} ".
    "hat auf Ihre H&ouml;hle in $coordX/$coordY ein Wunder gewirkt.";

  messages_sendSystemMessage($playerID, 9,
           "Wunder erwirkt auf $coordX/$coordY",
           $sourceMessage, $db);
  messages_sendSystemMessage($targetData['playerID'], 9,
           "Wunder!",
           $targetMessage, $db);

  return 1;
}
?>
