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

function reverseMovementEvent($caveID, $eventID){
  global $db;

  $query = "UPDATE Event_movement SET " .
           "source_caveID = target_caveID, " .
           "target_caveID = caveID, " .
           "event_end = FROM_UNIXTIME(2*UNIX_TIMESTAMP(NOW()+0)-UNIX_TIMESTAMP(event_start))+0, " .
           "event_start = NOW()+0, " .
           "movementID = 5 " .
           "WHERE blocked = 0 " .
           "AND event_movementID = $eventID ".
           "AND caveID = source_caveID ".
           "AND caveID = $caveID " .
           "AND event_end > NOW() + 0";

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
    if ($resourceID == 1)
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

  // insert fuer movement_event basteln
  $insert = "INSERT INTO Event_movement (caveID, source_caveID, ".
            "target_caveID, movementID, event_start, event_end, ".
            "artefactID, speedFactor, ";

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

  $insert .= " ) VALUES ( $caveID , $caveID , $targetCaveID , $movementID , ".
         "NOW()+0 , (NOW() + INTERVAL $absDuration MINUTE)+0 , $artefactID, ".
         "$speedFactor, ";

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
  } else {

    // remove the artefact if any
    if ($artefactID > 0){

      // TODO: what should happen, if the first if succedes but one of the other fails afterwards
      if (!artefact_removeEffectsFromCave($artefactID))
        die("Fehler beim Artefakteffekte entfernen. Bitte an einen Admin mailen!");

      if (!artefact_uninitiateArtefact($artefactID))
        die("Fehler beim Artefakt deinitialisieren. Bitte an einen Admin mailen!");

      if (!artefact_removeArtefactFromCave($artefactID))
        die("Fehler beim Artefakt aus der Höhle entfernen. Bitte an einen Admin mailen!");
    }
    return 0;
  }
}


// ADDED by chris--- for farmschutz
// -----------------------------------------------------------------------------

function farmschutz ($x, $y, $playerID, $db) {

global $db;

// Diese funktion gibt 0 zurück wenn kein Farmschutz besteht, 1 wenn der Schutz für den
// target besteht, 2 wenn der Schutz für den attacker besteht
// und -1 bei Fehlern


// Was loggen?
// 0 - gar nichts
// 1 - alles
// 2 - nur wenn Schutz besteht
// 3 - alles aber keine Einöden

$logging = 3;



$query = "SELECT round( sum( r.average ) / count( r.average ) / 1.5 ) AS grenze, s.playerID AS targetID, s.lastAction AS target_lastAction, p.playerID AS targetID, p.tribe AS target_tribe, p.farmschutz AS target_protection, r3.playerID AS targetID, r3.average AS targetPoints, c.xCoord AS target_x, c.yCoord AS target_y, c.playerID AS targetID, p2.playerID AS attackerID, p2.tribe AS attacker_tribe, p2.farmschutz AS attacker_protection, r2.playerID AS attackerID, r2.average AS attackerPoints, ".
  "rel.relationType AS relation_target, rel.tribe, rel.tribe_target, ".
  "IF ( ".
  "r3.average > round( sum( r.average ) / count( r.average ) / 1.5 ) , 1, 0 ".
  ") AS target_over, ".
  "IF ( ".
  "r2.average > round( sum( r.average ) / count( r.average ) / 1.5 ) , 1, 0 ".
  ") AS attacker_over ".
  "FROM ranking r ".
  "LEFT JOIN cave c ON c.xCoord = ".$x." AND c.yCoord = ".$y." ".
  "LEFT JOIN player p2 ON p2.playerID = ".$playerID." ".
  "LEFT JOIN session s ON s.playerID = c.playerID ".
  "LEFT JOIN player p ON p.playerID = c.playerID ".
  "LEFT JOIN ranking r2 ON r2.playerID = p2.playerID ".
  "LEFT JOIN ranking r3 ON r3.playerID = s.playerID ".
  "LEFT  JOIN relation rel ON (rel.tribe = p.tribe AND rel.tribe_target = p2.tribe) ".
  "OR (rel.tribe = p2.tribe AND rel.tribe_target = p.tribe) ".
  "GROUP BY c.playerID";

  if (!($result = $db->query($query))) {
    echo "Fehler in der Datenbank! getting result";
    return -1;
  }
  if (!($row = $result->nextRow())) {
    echo "Warnung: Datenbank (getting rows): <b>Dieser Fehler ist nicht schlimm. Verge&szlig;t den einfach!</b>";
    return -1;
  }

$grenze = $row[grenze];
$targetID = $row[targetID];
$target_lastAction = $row[target_lastAction];
$target_protection = $row[target_protection];
$target_points = $row[targetPoints];
$target_tribe = $row[target_tribe];
$target_over = $row[target_over];

$attackerID = $row[attackerID];
$attacker_protection = $row[attacker_protection];
$attacker_points = $row[attackerPoints];
$attacker_tribe = $row[attacker_tribe];
$attacker_over = $row[attacker_over];

$relation_target = $row[relation_target];

if (!$grenze) $grenze = 0;
if (!$targetID) $targetID = 0;
if (!$target_lastAction) $target_lastAction = "";
if (!$target_protection) $target_protection = -1;
if (!$target_points) $target_points = -1;
if (!$target_tribe) $target_tribe = "";

if (!$attackerID) $attackerID = 0;
if (!$attacker_protection) $attacker_protection = -1;
if (!$attacker_points) $attacker_points = -1;
if (!$attacker_tribe) $attacker_tribe = "";

// We insert this stuff in a table for testing and debugging and logging
// if you want this, create the following table:
/*
CREATE TABLE `farmschutz` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`timestamp` VARCHAR( 14 ),
`grenze` INT UNSIGNED,
`targetID` INT UNSIGNED,
`target_x` INT UNSIGNED NOT NULL,
`target_y` INT UNSIGNED NOT NULL,
`target_lastAction` VARCHAR( 14 ),
`target_protection` INT,
`target_points` INT,
`target_max_points` INT NOT NULL,
`target_tribe` VARCHAR( 8 ) NOT NULL,
`target_over` TINYINT,
`attackerID` INT UNSIGNED,
`attacker_protection` INT,
`attacker_points` INT,
`attacker_max_points` INT NOT NULL,
`attacker_tribe` VARCHAR( 8 ) NOT NULL,
`tribe_relation` INT UNSIGNED NOT NULL,
`attacker_over` TINYINT,
`Einoede` VARCHAR( 10 ) NOT NULL,
`Gott` VARCHAR( 10 ) NOT NULL,
`beide_ueber_grenze` VARCHAR( 10 ) NOT NULL,
`target_inactive` VARCHAR( 8 ) NOT NULL,
`can_attack` VARCHAR( 20 ) NOT NULL,
PRIMARY KEY ( `id` ) 
);
*/
// -------------------------------------------------------


$timestamp = date("YmdHis",time());

$can_attack = "FALSE";

// ------------------------------------
// ANALIZING
// ------------------------------------

// Simple calcs

// Einöde? Immer erlauben
if ($targetID == 0) {
  $einoede = "TRUE";
  $can_attack = "Yes, target waste";
  $abort = TRUE;
} else {
  $einoede = "FALSE";
}

// attacker ein Gott? immer erlauben
if ($attacker_tribe == GOD_ALLY) {
  $gott = "TRUE, ATT";
  $can_attack = "Yes, attacker god";
  $abort = TRUE;
} else {
  $gott = "FALSE";
}

// target ein gott? immer erlauben
if ($target_tribe == GOD_ALLY) {
  $gott = "TRUE, TAR";
  $can_attack = "Yes, target god";
  $abort = TRUE;
} else {
  $gott = "FALSE";
}

// Beide über der Grenze? Dann erlauben
if ($target_over == 1 && $attacker_over == 1) {
  $beide = "TRUE";
  $can_attack = "Yes, both over";
  $abort = TRUE;
} else {
  $beide = "FALSE";
}

// Stammesbeziehung Krieg oder Ulti? erlauben
if ($relation_target == 1 || $relation_target == 2) {
  $can_attack = "Yes, War or Ulti";
  $abort = TRUE;
}



if (!$abort) {

  // target inaktiv? immer erlauben
  $inactive_time = 2*24*60*60; // 2 Tage

  // Zeit berechnen

  $untime = mktime(substr($target_lastAction,8,2),substr($target_lastAction,10,2),substr($target_lastAction,12,2),substr($target_lastAction,4,2),substr($target_lastAction,6,2),substr($target_lastAction,0,4));

  $now = time();
  $zeit = $now-$inactive_time;

  if ($untime < $zeit) {
    $inactive = "TRUE";
    $can_attack = "Yes, inactive";
    $abort = TRUE;
  } else {
    $inactive = "FALSE";
  }

} // end if



if (!$abort) {

  // we need to claculate the max and min points depending on the protection percentage
  // for the attacker and the target

  $target_max = 100/$target_protection*$target_points;
  $attacker_max = 100/$attacker_protection*$attacker_points;



  $flag1 = FALSE;
  $flag2 = FALSE;


  // target zu klein und unter der grenze?
  if ($attacker_points > $target_max && $target_over != 1) {
    $flag1 = TRUE;
    $can_attack = "No, too small";
  }

  // target zu groß und angreifer unter der grenze?
  if ($target_points > $attacker_max && $attacker_over != 1) {
    $flag2 = TRUE;
    $can_attack = "No, too big";
  }


  // target nicht zu groß und nicht zu klein
  if ($flag1 == FALSE && $flag2 == FALSE) {
    $can_attack = "Yes";
    $abort = TRUE;
  }

} // end if
  else {
  $target_max = 0;
  $attacker_max = 0;
}


// --------------------------------------------------------------------

if ($logging == 1 || ($logging == 2 && $abort != TRUE) || ($logging == 3 && $einoede != "TRUE")) {

$query = "INSERT INTO farmschutz SET".
" timestamp = '".$timestamp."'".
", grenze = ".$grenze.
", targetID = ".$targetID.
", target_x = ".$x.
", target_y = ".$y.
", target_lastAction = '".$target_lastAction."'".
", target_protection = ".$target_protection.
", target_points = ".$target_points.
", target_max_points = ".$target_max.
", target_tribe = '".$target_tribe."'".
", target_over = ".$target_over.

", attackerID = ".$attackerID.
", attacker_protection = ".$attacker_protection.
", attacker_points = ".$attacker_points.
", attacker_max_points = ".$attacker_max.
", attacker_tribe = '".$attacker_tribe."'".
", attacker_over = ".$attacker_over.

", einoede = '".$einoede."'".
", gott = '".$gott."'".
", beide_ueber_grenze = '".$beide."'".
", target_inactive = '".$inactive."'".
", can_attack = '".$can_attack."'";


  if (!($db->query($query))) {
    echo "Fehler beim Eintragen in die Datenbank!";
    echo $query;
    return -1;
  }

} // end if logging

//return 0; // TEMP!!!!!!!!!!!!!!!!!!!!!!!

if ($abort) return 0;
  else {
    if ($flag1 == TRUE) return 1;
    if ($flag2 == TRUE) return 2;
      else return -1;
  }

// --------------------------------------------------------------------



}

// -------------------------------------------

?>
