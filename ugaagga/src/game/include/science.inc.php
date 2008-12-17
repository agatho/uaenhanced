<?
/*
 * science.inc.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function science_getScienceQueueForCave($playerID, $caveID) {
  $db = new DB();

  if (!($r=$db->query($query="SELECT e.* ".
          "FROM Event_science e ".
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


function science_processOrderCancel($event_scienceID, $caveID, $db) {
  if($db->query("DELETE FROM Event_science ".
    "WHERE event_scienceID = '".$event_scienceID."' ".
    "AND caveID = '".$caveID."'")) {
    return 0;                     // order canceled
  }
  else {
    return 1;                     // order NOT canceled or not found
  }
}

function science_processOrder($scienceID, $caveID, $playerID, $caveData, $db){

  global $defenseSystemTypeList,
         $buildingTypeList,
         $scienceTypeList,
         $resourceTypeList,
         $unitTypeList,
         $config;

  $science = $scienceTypeList[$scienceID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$science->maxLevel};", '$caveData')));

  // check, that this science isn't researched in an other cave at the
  // same time
  $sql = "SELECT event_scienceID FROM Event_science " .
         "WHERE playerID='$playerID' AND scienceID = '$scienceID'";

  $r = $db->query($sql);
  if (!$r) page_dberror();
  if (!$r->isEmpty())
    return 4;

  // check for scienceMaxDeps in Event_Handler
  $dep_count = 0;
  foreach ($science->maxScienceDepList as $key => $value){
    if ($value != -1 && $caveData[$scienceTypeList[$key]->dbFieldName] > $value - 1){
      if ($dep_count)
        $deps .= ",";

      $deps .= $key;
      $dep_count++;
    }
  }

  if ($dep_count){
    $query = "SELECT event_scienceID FROM Event_science " .
             "WHERE playerID = '$playerID' AND scienceID IN ($deps)";
    if (!($r = $db->query($query))) page_dberror();
    if (!$r->isEmpty()){
      return 5;
    }
  }



  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '$caveID'",
                   "{$science->dbFieldName} < $maxLevel");

  // get all the resource costs
  foreach ($science->resourceProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $resourceTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - ({$formula})");
      array_push($setBack, "{$dbField} = {$dbField} + ({$formula})");
      array_push($where,   "{$dbField} >= ({$formula})");
    }
  }

  // get all the unit costs
  foreach ($science->unitProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $unitTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }

  // get all the building costs
  foreach ($science->buildingProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);

      $dbField = $buildingTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }

  // get all the external costs
  foreach ($science->externalProductionCost as $key => $value){
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
  foreach($science->buildingDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$buildingTypeList[$key]->dbFieldName} >= $value");
  foreach($science->maxBuildingDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$buildingTypeList[$key]->dbFieldName} <= $value");

  foreach($science->defenseSystemDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} >= $value");
  foreach($science->maxDefenseSystemDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} <= $value");

  foreach($science->resourceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} >= $value");
  foreach($science->maxResourceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} <= $value");

  foreach($science->scienceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} >= $value");
  foreach($science->maxScienceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} <= $value");

  foreach($science->unitDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$unitTypeList[$key]->dbFieldName} >= $value");
  foreach($science->maxUnitDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$unitTypeList[$key]->dbFieldName} <= $value");

  $where   = implode(" AND ", $where);

  if (!$db->query($set.$where) || !$db->affected_rows() == 1) {
    return 2;
  }
  $prodTime = 0;

  // calculate the production time;
  if ($time_formula = $science->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$caveData');

    $time_eval_formula="\$prodTime=$time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= SCIENCE_TIME_BASE_FACTOR;
  $now = time();
  $query = sprintf("INSERT INTO Event_science (caveID, playerID, scienceID, ".
                   "`start`, `end`) VALUES (%d, %d, %d, '%s', '%s')",
                   $caveID, $playerID, $scienceID,
                   time_toDatetime($now),
                   time_toDatetime($now + $prodTime));
  if (!$db->query($query)){
    $db->query($setBack);
    return 2;
  }
  return 3;
}
?>
