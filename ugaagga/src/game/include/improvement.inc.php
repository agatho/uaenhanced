<?
/*
 * improvement.inc.php - 
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function improvement_getImprovementQueueForCave($playerID, $caveID) {
  $db = new DB();

  if (!($r=$db->query($query="SELECT e.* ".
          "FROM Event_expansion e ".
          "LEFT JOIN Cave c ON c.caveID = e.caveID ".
          "WHERE c.caveID IS NOT NULL ".
          "AND c.playerID = '$playerID' ".
          "AND e.caveID = '$caveID'"))) {
    return 0;
  }
  if ($r->isEmpty()) {
    $r->free();
    return 0;
  }
  return $r;
}


function improvement_processOrderCancel($event_expansionID, $caveID, $db) {
  if($db->query("DELETE FROM Event_expansion ".
    "WHERE event_expansionID = '".$event_expansionID."' ".
    "AND caveID = '".$caveID."'")) {
    return 0;                     // order canceled
  }
  else {
    return 1;                     // order NOT cancled or not found
  }
}

function improvement_toreDownIsPossible($caveID, $db)
{
  $query = "SELECT toreDownTimeout < NOW()+0 AS possible ".
           "FROM Cave ".
	   "WHERE caveID = '$caveID'";
  if (! ($result = $db->query($query))) { echo $query;
    return 0;
  }
  if (!($row = $result->nextRow()) || ! $row["possible"] ) {
    return 0;
  }
  return 1;
}

function improvement_breakDown($buildingID, $caveID, $caveData, $db) {
  global $resourceTypeList, $buildingTypeList, $config;

  $bFieldName = $buildingTypeList[$buildingID]->dbFieldName;

  // can't tear down
  if (!improvement_toreDownIsPossible($caveID, $db)) return 8;

  // no building of that type
  if ($caveData[$bFieldName] < 1) return 7;

  $query= "UPDATE Cave ";
  $where= "WHERE caveID = '$caveID' ".
          "AND {$bFieldName} > 0 ";

  // add resources gain
  /*
  if (is_array($buildingTypeList[$buildingID]->resourceProductionCost)){
    $resources = array();
    foreach ($buildingTypeList[$buildingID]->resourceProductionCost as $key => $value){
      if ($value != "" && $value != "0"){
        $formula     = formula_parseToSQL($value);
        $dbField     = $resourceTypeList[$key]->dbFieldName;
        $maxLevel    = round(eval('return '.formula_parseToPHP("{$resourceTypeList[$key]->maxLevel};", '$caveData')));
        $resources[] = "$dbField = LEAST($maxLevel, $dbField + ($formula) / {$config->IMPROVEMENT_PAY_BACK_DIVISOR})";
      }
    }
    $set .= implode(", ", $resources);
  }
  */
  // ATTENTION: "SET building = building - 1" has to be placed BEFORE
  //            the calculation of the resource return. Otherwise
  //            mysql would calculate the cost of the NEXT step not
  //            of the LAST building step (returns would be too high)...

  $query .= "SET {$bFieldName} = {$bFieldName} - 1, ".
            "toreDownTimeout = (NOW() + INTERVAL ".
            TORE_DOWN_TIMEOUT." MINUTE)+0 ";
  if (strlen($set)) $query .= ", $set ";

  if (!$db->query($query.$where) || !$db->affected_rows() == 1)
    return 6;

  return 5;
}

function improvement_processOrder($buildingID, $caveID, $caveData, $db) {

  global $defenseSystemTypeList,
         $unitTypeList,
         $buildingTypeList,
         $scienceTypeList,
         $resourceTypeList;

  $building = $buildingTypeList[$buildingID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$building->maxLevel};", '$caveData')));

  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '$caveID'",
                   "{$building->dbFieldName} < $maxLevel");

  // get all the resource costs
  foreach ($building->resourceProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $resourceTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - ({$formula})");
      array_push($setBack, "{$dbField} = {$dbField} + ({$formula})");
      array_push($where,   "{$dbField} >= ({$formula})");
    }
  }

  // get all the unit costs
  foreach ($building->unitProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $unitTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }
  // get all the building costs
  foreach ($building->buildingProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $buildingTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }

  // get all the external costs
  foreach ($building->externalProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $defenseSystemTypeList[$key]->dbFieldName;

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
  foreach($building->buildingDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$buildingTypeList[$key]->dbFieldName} >= $value");
  foreach($building->maxBuildingDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$buildingTypeList[$key]->dbFieldName} <= $value");

  foreach($building->defenseSystemDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} >= $value");
  foreach($building->maxDefenseSystemDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} <= $value");

  foreach($building->resourceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} >= $value");
  foreach($building->maxResourceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} <= $value");

  foreach($building->scienceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} >= $value");
  foreach($building->maxScienceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} <= $value");

  foreach($building->unitDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$unitTypeList[$key]->dbFieldName} >= $value");
  foreach($building->maxUnitDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$unitTypeList[$key]->dbFieldName} <= $value");

  $where   = implode(" AND ", $where);

  if (!$db->query($set.$where) || !$db->affected_rows() == 1) {
    return 2;
  }
  $prodTime = 0;

  // calculate the production time;
  if ($time_formula = $building->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$caveData');

    $time_eval_formula="\$prodTime=$time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= BUILDING_TIME_BASE_FACTOR;
  $now = time();
  $query = sprintf("INSERT INTO Event_expansion (caveID, expansionID, ".
                   "`start`, `end`) VALUES (%d, %d, '%s', '%s')",
                   $caveID, $buildingID,
                   time_toDatetime($now),
                   time_toDatetime($now + $prodTime));
  if (!$db->query($query)){
    $db->query($setBack);
    return 2;
  }
  return 3;
}
?>
