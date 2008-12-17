<?
/*
 * artefact.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define("ARTEFACT_INITIATING",  -1);
define("ARTEFACT_UNINITIATED",  0);
define("ARTEFACT_INITIATED",    1);

function artefact_getArtefactsReadyForMovement($caveID){
  global $db;

  $sql = 'SELECT * FROM Artefact a '.
         'LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID '.
         'WHERE caveID = '.$caveID.' AND initiated = '.ARTEFACT_INITIATED;

  $dbresult = $db->query($sql);
  if (!$dbresult){
    return array();
  }

  $result = array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    array_push($result, $row);
  }
  return $result;
}

function getArtefactList(){
  global $db, $params;

  $sql = 'SELECT '.
         'a.artefactID, a.caveID, a.initiated, '.
         'ac.name as artefactname, ac.initiationID, '.
         'c.name AS cavename, c.xCoord, c.yCoord, '.
         'p.playerID, p.name, p.tribe ' .
         'FROM Artefact a '.
         'LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID '.
         'LEFT JOIN Cave c ON a.caveID = c.caveID ' .
         'LEFT JOIN Player p ON c.playerID = p.playerID';

  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }

  $result = array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    array_push($result, $row);
  }
  return $result;
}

function getArtefactMovement($artefactID){
  global $db;

  $sql = 'SELECT source_caveID, target_caveID, movementID, end '.
         'FROM Event_movement WHERE artefactID = ' . $artefactID;
  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty())
    return array();
  $result = $dbresult->nextrow(MYSQL_ASSOC);

  $result['event_end'] = time_formatDatetime($result['end']);

  $sql = "SELECT c.name AS source_cavename, c.xCoord AS source_xCoord, ".
         "c.yCoord AS source_yCoord, ".
         "IF(ISNULL(p.name), '" . _('leere Höhle') . "',p.name) AS source_name, ".
         "p.tribe AS source_tribe, p.playerID AS source_playerID ".
         "FROM Cave c LEFT JOIN Player p ON c.playerID = p.playerID ".
         "WHERE c.caveID = " . $result['source_caveID'];

  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }
  $result += $dbresult->nextrow(MYSQL_ASSOC);


  $sql = "SELECT c.name AS destination_cavename, c.xCoord AS destination_xCoord, ".
         "c.yCoord AS destination_yCoord, ".
         "IF(ISNULL(p.name), '" . _('leere Höhle') . "',p.name) AS destination_name, ".
         "p.tribe AS destination_tribe, p.playerID AS destination_playerID ".
         "FROM Cave c LEFT JOIN Player p ON c.playerID = p.playerID ".
         "WHERE c.caveID = " . $result['target_caveID'];

  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }
  $result += $dbresult->nextrow(MYSQL_ASSOC);

  return $result;
}

function artefact_getArtefactMovements(){
  global $db;

  // prepare query
  $sql = 'SELECT artefactID, source_caveID, target_caveID, movementID, end '.
         'FROM Event_movement WHERE artefactID != 0';
  
  // send it
  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty())
    return array();
  
  // collect movements
  $moves = array();
  while ($row = $dbresult->nextrow(MYSQL_ASSOC)){
    // format time
    $row['event_end'] = time_formatDatetime($row['end']);

    // prepare query
    $sql = "SELECT c.name AS source_cavename, c.xCoord AS source_xCoord, ".
           "c.yCoord AS source_yCoord, p.name AS source_name, ".
           "p.tribe AS source_tribe, p.playerID AS ".
           "source_playerID FROM Cave c LEFT JOIN Player p ON c.playerID = ".
           "p.playerID WHERE c.caveID = " . $row['source_caveID'];

    // send query
    $innerresult = $db->query($sql);
    if (!$innerresult || $innerresult->isEmpty())
      continue;
    $row += $innerresult->nextrow(MYSQL_ASSOC);
    
    // prepare query
    $sql = "SELECT c.name AS destination_cavename, c.xCoord AS ".
           "destination_xCoord, c.yCoord AS destination_yCoord, ".
           "p.name AS destination_name, p.tribe AS ".
           "destination_tribe, p.playerID AS destination_playerID FROM Cave c ".
           "LEFT JOIN Player p ON c.playerID = p.playerID WHERE c.caveID = " .
           $row['target_caveID'];

    // send query
    $innerresult = $db->query($sql);
    if (!$innerresult || $innerresult->isEmpty())
      continue;
    $row += $innerresult->nextrow(MYSQL_ASSOC);

    $moves[$row['artefactID']] = $row;
  }
  return $moves;
}

function artefact_getArtefactInitiationsForCave($caveID){
  global $db;

  $sql = 'SELECT * FROM `Event_artefact` WHERE `caveID` = ' . $caveID;
  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }
  return $dbresult->nextrow(MYSQL_ASSOC);
}

/** get artefact by its id
 */
function artefact_getArtefactByID($artefactID){
  global $db;

  $sql = 'SELECT * FROM Artefact a '.
         'LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID '.
         'WHERE a.artefactID = ' . $artefactID;
  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }
  return $dbresult->nextrow(MYSQL_ASSOC);
}


/** get artefacts by caveID
 */
function artefact_getArtefactByCaveID($caveID){
  global $db;

  // init result
  $result = array();

  // prepare statement
  $sql = sprintf('SELECT * FROM Artefact a LEFT JOIN Artefact_class ac ' .
                 'ON a.artefactClassID = ac.artefactClassID ' .
                 'WHERE a.caveID = %d', $caveID);
  
  // execute it
  $dbresult = $db->query($sql);

  // return an empty result on error or an empty row set
  if (!$dbresult || $dbresult->isEmpty())
    return $result;
  
  // collect all the rows
  while ($row = $dbresult->nextrow(MYSQL_ASSOC))
    $result[] = $row;
   
  return $result;
}

/** get ritual
 */
function artefact_getRitualByID($ritualID){
  global $db;
  // get ritual
  $sql = "SELECT * FROM Artefact_rituals WHERE ritualID = {$ritualID}";
  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return FALSE;
  }
  return $dbresult->nextrow(MYSQL_ASSOC);
}

/** put artefact into cave after finished movement.
 */
function artefact_putArtefactIntoCave($artefactID, $caveID){
  global  $db;

  $sql = "UPDATE Artefact SET caveID = {$caveID} WHERE artefactID = {$artefactID}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  $sql = "UPDATE Cave SET artefacts = artefacts + 1 WHERE caveID = {$caveID}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  return TRUE;
}

/** user wants to initiate artefact. thus he has first to pay the fee successfully
 *  then the status of the artefact can be set to ARTEFACT_INITIATING.
 */
function artefact_beginInitiation($artefact){
  global $db,
         $resourceTypeList,
         $buildingTypeList,
         $unitTypeList,
         $scienceTypeList,
         $defenseSystemTypeList;

  // Artefakt muss einweihbar sein
  if ($artefact['initiated'] != ARTEFACT_UNINITIATED){
    return _('Dieses Artefakt kann nicht noch einmal eingeweiht werden.');
  }

  // Hol das Einweihungsritual
  $ritual = artefact_getRitualByID($artefact['initiationID']);
  if ($ritual === FALSE)
    return _('Fehler: Ritual nicht gefunden.');

  // get initiation costs
  $costs = array();
  $temp = array_merge($resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList);
  foreach($temp as $val)
    if ($ritual[$val->dbFieldName])
      $costs[$val->dbFieldName] = $ritual[$val->dbFieldName];

  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '{$artefact['caveID']}'");

  // get all the costs
  foreach ($costs as $key => $value){
    array_push($set,     "{$key} = {$key} - ({$value})");
    array_push($setBack, "{$key} = {$key} + ({$value})");
    array_push($where,   "{$key} >= ({$value})");
  }

  // generate SQL
  if (sizeof($set)){
    $set     = implode(", ", $set);
    $set     = "UPDATE Cave SET $set ";
    $setBack = implode(", ", $setBack);
    $setBack = "UPDATE Cave SET $setBack WHERE caveID = '{$artefact['caveID']}'";
  }

  $where   = implode(" AND ", $where);

  // substract costs

  //echo "try to substract costs:<br>" . $set.$where . "<br><br>";
  if (!$db->query($set.$where) || !$db->affected_rows() == 1) {
    return _('Es fehlen die notwendigen Voraussetzungen.');
  }

  // register event
  $now = time();
  $sql = sprintf("INSERT INTO Event_artefact " .
                 "(caveID, artefactID, event_typeID, `start`, `end`) " .
                 "VALUES (%d, %d, 1, '%s', '%s')",
                 $artefact['caveID'],
                 $artefact['artefactID'],
                 time_toDatetime($now),
                 time_toDatetime($now + $ritual['duration']));

  if (!$db->query($sql)){
    $db->query($setBack);
    return _('Sie weihen bereits ein anderes Artefakt ein.');
  }

  // finally set status to initiating
  $sql = "UPDATE Artefact SET initiated = " . ARTEFACT_INITIATING . " WHERE artefactID = {$artefact['artefactID']}";

  $dbresult = $db->query($sql);
  if (!$dbresult) return _('Fehler: Artefakt konnte nicht auf ARTEFACT_INITIATING gestellt werden.');
  return _('erfolgreich eingeweiht');
}

/** initiating finished. now set the status of the artefact to ARTEFACT_INITIATED.
 */
function artefact_initiateArtefact($artefactID){
  global $db;

  $sql = "UPDATE Artefact SET initiated = " . ARTEFACT_INITIATED . " WHERE artefactID = {$artefactID}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;
  else return TRUE;
}

/** status already set to ARTEFACT_INITIATED, now apply the effects
 */
function artefact_applyEffectsToCave($artefactID){
  global  $db, $effectTypeList;

  $artefact = artefact_getArtefactByID($artefactID);
  if (sizeof($artefact) == 0) return FALSE;
  if ($artefact['caveID'] == 0) return FALSE;

  $effects = array();
  foreach ($effectTypeList as $effect){
    array_push($effects, "{$effect->dbFieldName} = {$effect->dbFieldName} + {$artefact[$effect->dbFieldName]}");
  }

  if (sizeof($effects)){
    $effects = implode(", ", $effects);
    $sql = "UPDATE Cave SET {$effects} WHERE caveID = {$artefact['caveID']}";
    $dbresult = $db->query($sql);
    if (!$dbresult || $db->affected_rows() != 1) return FALSE;
  }

  return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the effects.
 */
function artefact_removeEffectsFromCave($artefactID){
  global  $db, $effectTypeList;

  $artefact = artefact_getArtefactByID($artefactID);
  if (sizeof($artefact) == 0) return FALSE;
  if ($artefact['initiated'] != ARTEFACT_INITIATED) return TRUE;
  if ($artefact['caveID'] == 0) return FALSE;

  $effects = array();
  foreach ($effectTypeList as $effect){
    if ($artefact[$effect->dbFieldName] != 0){
      array_push($effects, "{$effect->dbFieldName} = {$effect->dbFieldName} - {$artefact[$effect->dbFieldName]}");
    }
  }

  if (sizeof($effects)){
    $effects = implode(", ", $effects);
    $sql = "UPDATE Cave SET {$effects} WHERE caveID = {$artefact['caveID']}";
    $dbresult = $db->query($sql);
    if (!$dbresult || $db->affected_rows() != 1) return FALSE;
  }

  return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  uninitiate this artefact
 */
function artefact_uninitiateArtefact($artefactID){
  global $db;

  $sql = "UPDATE Artefact SET initiated = " . ARTEFACT_UNINITIATED . " WHERE artefactID = {$artefactID}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  $sql = "DELETE FROM `Event_artefact` WHERE `artefactID` = '$artefactID'";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;
  else return TRUE;
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the artefact from its cave
 */
function artefact_removeArtefactFromCave($artefactID){
  global  $db;

  $artefact = artefact_getArtefactByID($artefactID);
  if (sizeof($artefact) == 0) return FALSE;

  $sql = "UPDATE Artefact SET caveID = 0 WHERE artefactID = {$artefact['artefactID']}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  $sql = "UPDATE Cave SET artefacts = artefacts - 1 WHERE caveID = {$artefact['caveID']}";
  $dbresult = $db->query($sql);
  if (!$dbresult) return FALSE;

  return TRUE;
}

/** recalculate artefacts' effects for a given cave
 */
function artefact_recalculateEffects($caveID) {
  global $db, $effectTypeList;
  
  // init result
  $result = array();
  foreach ($effectTypeList as $effectID => $effect)
    $result[$effectID] = 0;
      
  // get artefacts
  $artefacts = artefact_getArtefactByCaveID($caveID);
  if (!sizeof($artefacts))
    return $result;
  
  // iterate through the effects
  foreach ($effectTypeList as $effectID => $effect)
    // iterate through the artefacts
    foreach ($artefacts as $artefact)
      // consider only initiated artefacts
      if (ARTEFACT_INITIATED == $artefact['initiated'])
        $result[$effectID] += $artefact[$effect->dbFieldName];
    
  return $result;
}

?>
