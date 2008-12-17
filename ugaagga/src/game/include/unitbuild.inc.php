<?
/*
 * unitbuild.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function unit_getUnitQueueForCave($playerID, $caveID) {
  $db = new DB();

  if (!($r=$db->query($query="SELECT e.* ".
          "FROM Event_unit e ".
          "LEFT JOIN Cave c ON c.caveID = e.caveID ".
          "WHERE c.caveID IS NOT NULL ".
          "AND c.playerID = '$playerID' ".
          "AND e.caveID = '$caveID'"))) {
    //echo $query;
    return 0;
  }
  if ($r->isEmpty()) {
    $r->free();
    return 0;
  }
  return $r;
}


function unit_processOrderCancel($event_unitID, $caveID, $db) {
  if($db->query("DELETE FROM Event_unit ".
    "WHERE event_unitID = '".$event_unitID."' ".
    "AND caveID = '".$caveID."'")) {
    return 0;                     // order canceled
  }
  else {
    return 1;                     // order NOT cancled or not found
  }
}

function unit_processOrder($unitID, $quantity, $caveID, $db, $details) {

  global $defenseSystemTypeList,
         $unitTypeList,
         $buildingTypeList,
         $scienceTypeList,
         $resourceTypeList;

  if ($quantity > MAX_SIMULTAN_BUILDED_UNITS || $quantity <= 0 )
    return 4;

  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '$caveID'");

  // get all the resource costs
  foreach ($unitTypeList[$unitID]->resourceProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);
      $formula *= $quantity;

      $dbField = $resourceTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }

  // get all the unit costs
  foreach ($unitTypeList[$unitID]->unitProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);
      $formula *= $quantity;

      $dbField = $unitTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }

  // get all the building costs
  foreach ($unitTypeList[$unitID]->buildingProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);
      $formula *= $quantity;

      $dbField = $buildingTypeList[$key]->dbFieldName;

      array_push($set,     "{$dbField} = {$dbField} - {$formula}");
      array_push($setBack, "{$dbField} = {$dbField} + {$formula}");
      array_push($where,   "{$dbField} >= {$formula}");
    }
  }

  // get all the external costs
  foreach ($unitTypeList[$unitID]->externalProductionCost as $key => $value){
    if ($value != "" && $value != "0"){
      $formula = formula_parseToSQL($value);
      $formula *= $quantity;

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
  foreach($unitTypeList[$unitID]->buildingDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$buildingTypeList[$key]->dbFieldName} >= $value");
  foreach($unitTypeList[$unitID]->maxBuildingDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$buildingTypeList[$key]->dbFieldName} <= $value");

  foreach($unitTypeList[$unitID]->defenseSystemDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} >= $value");
  foreach($unitTypeList[$unitID]->maxDefenseSystemDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$defenseSystemTypeList[$key]->dbFieldName} <= $value");

  foreach($unitTypeList[$unitID]->resourceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} >= $value");
  foreach($unitTypeList[$unitID]->maxResourceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$resourceTypeList[$key]->dbFieldName} <= $value");

  foreach($unitTypeList[$unitID]->scienceDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} >= $value");
  foreach($unitTypeList[$unitID]->maxScienceDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$scienceTypeList[$key]->dbFieldName} <= $value");

  foreach($unitTypeList[$unitID]->unitDepList as $key => $value)
    if ($value != "" && $value != "0")
      array_push($where, "{$unitTypeList[$key]->dbFieldName} >= $value");
  foreach($unitTypeList[$unitID]->maxUnitDepList as $key => $value)
    if ($value != -1)
      array_push($where, "{$unitTypeList[$key]->dbFieldName} <= $value");

  $where   = implode(" AND ", $where);

  if (!$db->query($set.$where) || !$db->affected_rows() == 1){
    return 2;
  }

  $prodTime = 0;
  // calculate the production time;
  if ($time_formula = $unitTypeList[$unitID]->productionTimeFunction){
    $time_eval_formula = formula_parseToPHP($time_formula, '$details');
    eval('$prodTime=' . $time_eval_formula . ';');
  }
  $prodTime *= BUILDING_TIME_BASE_FACTOR * $quantity;
  $now = time();
  $query = sprintf("INSERT INTO Event_unit (caveID, unitID, quantity, ".
                   "`start`, `end`) VALUES (%d, %d, %d, '%s', '%s')",
                   $caveID, $unitID, $quantity,
                   time_toDatetime($now),
                   time_toDatetime($now + $prodTime));
  if (!$db->query($query)){
    $db->query($setBack);
    return 2;
  }
  return 3;
}
?>
