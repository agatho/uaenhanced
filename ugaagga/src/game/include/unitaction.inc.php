<?
/*
 * unitaction.inc.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Movement.php');

function reverseMovementEvent($caveID, $eventID){
  global $db;

  // get movements
  $ua_movements = Movement::getMovements();

  // get current time
  $now = time();

  // get movement
  $query = sprintf("SELECT * FROM Event_movement WHERE event_movementID = %d",
                   $eventID);
  $result = $db->query($query);
  if (!$result || $result->isEmpty())
    return 1;
  $move = $result->nextRow(MYSQL_ASSOC);

  // check movement

  // blocked
  if ($move['blocked'])
    return 1;

  // not reversable
  if ($ua_movements[$move['movementID']]->returnID == -1)
    return 1;

  // not own movement
  if ($caveID != $move['caveID'])
    return 1;

  // expired
  if (time_fromDatetime($move['end']) < $now)
    return 1;

  // build query
  $start = time_fromDatetime($move['start']);
  $end   = time_fromDatetime($move['end']);
  $diff  = $now - $start;

  $query = sprintf("UPDATE Event_movement SET source_caveID = target_caveID, ".
                   "target_caveID = caveID, ".
                   "movementID = %d, end = '%s', start = '%s' ".
                   "WHERE blocked = 0 AND caveID = source_caveID AND ".
                   "caveID = %d AND event_movementID = %d",
                   $ua_movements[$move['movementID']]->returnID,
                   time_toDatetime($now + $diff), time_toDatetime($now),
                   $caveID, $eventID);

  $db->query($query);
  return intval($db->affected_rows() != 1);
}

// hilfsfkt
function filterZeros($val){
  return !empty( $val );
}

// hilfsfkt
function checkFormValues($val){
  return (int) $val;
}

function setMovementEvent($caveID, $caveData,
                          $targetX, $targetY,
                          $unit, $resource,
                          $movementID, $reqFood, $absDuration,
                          $artefactID, $caveSpeedFactor){
  global $db, $unitTypeList, $resourceTypeList;

  // ziel-hoehlenID holen
  $res = $db->query("SELECT caveID FROM Cave WHERE xCoord = " . $targetX . " AND yCoord = " . $targetY);
  if ($db->affected_rows() < 1) return 1;

  $row = $res->nextRow();
  $targetCaveID = $row['caveID'];

  // updates fuer cave basteln
  $update = "UPDATE Cave ";
  $updateRollback = "UPDATE Cave ";

  $set = "SET caveID = $caveID ";
  $setRollback = "SET caveID = $caveID ";
  $where = "WHERE caveID = $caveID ";
  $whereRollback = "WHERE caveID = $caveID ";

  foreach ($unit as $unitID => $value)
    if( !empty( $value )){
      $set .= ", ".$unitTypeList[$unitID]->dbFieldName." = ".$unitTypeList[$unitID]->dbFieldName." - $value ";
      $where .= "AND ".$unitTypeList[$unitID]->dbFieldName." >= $value ";
                        $where .= "AND $value >= 0 "; // check for values bigger 0!
      $setRollback .= ", ".$unitTypeList[$unitID]->dbFieldName." = ".$unitTypeList[$unitID]->dbFieldName." + $value ";
    }

  foreach ($resource as $resourceID => $value){
    $value_to_check = $value;
    if ($resourceID == $GLOBALS['FUELRESOURCEID'])
      $value += $reqFood;

    if (!empty($value) || !empty($value_to_check)){
      $set .= ", ".$resourceTypeList[$resourceID]->dbFieldName." = ".$resourceTypeList[$resourceID]->dbFieldName." - $value ";
      $where .= "AND ".$resourceTypeList[$resourceID]->dbFieldName." >= $value ";
      if (!empty($value_to_check))
        $where .= "AND $value_to_check >= 0 ";

      $setRollback .= ", ".$resourceTypeList[$resourceID]->dbFieldName." = ".$resourceTypeList[$resourceID]->dbFieldName." + $value ";
    }
  }

  $update = $update.$set.$where;
  $updateRollback = $updateRollback.$setRollback.$whereRollback;

  if (!$db->query($update) || $db->affected_rows() < 1)
    return 2;

  // remove the artefact if any
  if ($artefactID > 0){

    // TODO: what should happen, if the first succedes but one of the other fails afterwards
    if (!artefact_removeEffectsFromCave($artefactID)){
      $db->query($updateRollback);
      return 3;
    }

    if (!artefact_uninitiateArtefact($artefactID)){
      $db->query($updateRollback);
      return 3;
    }

    if (!artefact_removeArtefactFromCave($artefactID)){
      $db->query($updateRollback);
      return 3;
    }
  }

  // insert fuer movement_event basteln
  $now = time();
  $insert = "INSERT INTO Event_movement (caveID, source_caveID, ".
            "target_caveID, movementID, `start`, `end`, ".
            "artefactID, speedFactor, exposeChance, ";

  $i = 0;
  foreach ($unit as $uID => $val)
    if (!empty($val)){
      if ($i++ != 0) $insert .= " , ";
      $insert .= $unitTypeList[$uID]->dbFieldName;
    }

  foreach ($resource as $rID => $val)
    if (!empty($val))
      $insert .= " , ".$resourceTypeList[$rID]->dbFieldName;

  $speedFactor = getMaxSpeedFactor($unit) * $caveSpeedFactor;

  // determine expose chance
  $exposeChance = (double)rand() / (double)getRandMax();

  $insert .= sprintf(" ) VALUES (%d, %d, %d, %d, ".
                     "'%s', '%s', %d, %f, %f, ",
                     $caveID, $caveID, $targetCaveID, $movementID,
                     time_toDatetime($now),
                     time_toDatetime($now + $absDuration * 60),
                     $artefactID, $speedFactor, $exposeChance);

  $i = 0;
  foreach($unit as $val)
    if (!empty($val)){
      if ($i++ != 0) $insert .= " , ";
      $insert .= $val;
    }

  foreach ($resource as $val)
    if (!empty($val)) $insert .= " , ".$val;

  $insert .= " )";

  if(!$db->query($insert)){
    // update rueckgaengig machen
    $db->query( $updateRollback );
    return 3;
  }
  
  return 0;
}
?>
