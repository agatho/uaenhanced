<?
/*
 * digest.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Movement.php');

/*****************************************************************************/
/*                                                                          **/
/*      MOVEMENTS                                                           **/
/*                                                                          **/
/*****************************************************************************/

function digest_getMovements($meineHoehlen, $doNotShow, $showDetails){
  global $resourceTypeList, $unitTypeList, $params, $db;
  global $EXPOSEINVISIBLE;

  // get movements
  $ua_movements = Movement::getMovements();

  // caveIDs einsammeln
  $caveIDs = array();
  foreach ($meineHoehlen as $caveID => $value) $caveIDs[] = $caveID;
  $str_caveIDs = implode(",", $caveIDs);

  // Bewegungen besorgen
  $query = "SELECT * " .
           "FROM Event_movement " .
           "WHERE (source_caveID IN ({$str_caveIDs}) OR (target_caveID IN ({$str_caveIDs}))) " .
           "ORDER BY end ASC, event_movementID ASC";
  $dbresult = $db->query($query);
  if (!$dbresult) return array();

  // bewegungen durchgehen
  $result = array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){

    // "do not show" movements should not be shown
    if (in_array($row['movementID'], $doNotShow)) continue;

    // is own movement?
    $row['isOwnMovement'] = in_array($row['caveID'], $caveIDs);

    /////////////////////////////////
    // SICHTWEITE BESCHRÄNKEN

/* We got some problems, as reverse movements should not ALWAYS be visible.
 * For example a transport reverse movement should be visible, but a
 * spy reverse movement should not...
 * As a work around we will fix it by not showing any adverse reverse movement.
 *
 * The original code is following...

    if (!$row['isOwnMovement']){

      if ($ua_movements[$row['movementID']]->returnID == -1){
        $sichtweite = getVisionRange($meineHoehlen[$row['source_caveID']]) * $row['speedFactor'];
        $distance = time() - (time_fromDatetime($row['end']) - getDistanceByID($srcID, $destID) * $row['speedFactor']);
      } else {
        $sichtweite = getVisionRange($meineHoehlen[$row['target_caveID']]) * $row['speedFactor'];
        $distance = ceil((time_fromDatetime($row['end']) - time())/60);
      }

      if ($sichtweite < $distance) continue;
    }
 */
    // compute visibility
    if (!$row['isOwnMovement']){
      // don't show adverse reverse movements
      if ($ua_movements[$row['movementID']]->returnID == -1) continue;

      $sichtweite = getVisionRange($meineHoehlen[$row['target_caveID']]) * $row['speedFactor'];
      $distance = ceil((time_fromDatetime($row['end']) - time())/60);
      if ($sichtweite < $distance) continue;
    }
  /////////////////////////////////


    // ***** fremde unsichtbare bewegung *****
    if ($row['isOwnMovement'] == 0)
      if ($ua_movements[$row['movementID']]->mayBeInvisible){
        $anzahl_sichtbarer_einheiten = 0;
        foreach ($unitTypeList as $unitType)
          if ($unitType->visible)
            $anzahl_sichtbarer_einheiten += $row[$unitType->dbFieldName];
        if ($anzahl_sichtbarer_einheiten == 0) continue;
      }

    $tmp = array('eventID'                => $row['event_movementID'],
                 'caveID'                 => $row['caveID'],
                 'source_caveID'          => $row['source_caveID'],
                 'target_caveID'          => $row['target_caveID'],
                 'movementID'             => $row['movementID'],
                 'event_start'            => time_formatDatetime($row['start']),
                 'event_end'              => time_formatDatetime($row['end']),
                 'isOwnMovement'          => intval($row['isOwnMovement']),
                 'seconds_before_end'     => time_fromDatetime($row['end']) - time(),
                 'movementID_description' => $ua_movements[$row['movementID']]->description);

    // Quelldaten
    $source = digest_getCaveNameAndOwnerByCaveID($row['source_caveID']);
    foreach ($source AS $key => $value)
      $tmp['source_'.$key] = $value;

    // Zieldaten
    $target = digest_getCaveNameAndOwnerByCaveID($row['target_caveID']);
    foreach ($target AS $key => $value)
      $tmp['target_'.$key] = $value;


    // ***** Einheiten, Rohstoffe und Artefakte *****
    if ($showDetails){

      // show artefact
      if ($row['artefactID'])
        $tmp['ARTEFACT'] = artefact_getArtefactByID($row['artefactID']);

      // eval(ExposeInvisible)
      // FIXME (mlunzena): oben holen wir schon bestimmte Höhlendaten,
      //                   das sollte man zusammenfassen..
      $target = getCaveByID($row['target_caveID']);
      $expose = eval('return '.formula_parseToPHP($EXPOSEINVISIBLE.";", '$target'));

      // show units
      $units = array();
      foreach ($unitTypeList as $unit){

        // this movement does not contain units of that type
        if (!$row[$unit->dbFieldName]) continue;

        // expose invisible units
        //   if it is your own move
        //   if unit is visible
        if (!$row['isOwnMovement'] && !$unit->visible){

          // if target cave's EXPOSEINVISIBLE is > than exposeChance
          if ($expose <= $row['exposeChance']){
            // do not expose
            continue;
          } else {
            // do something
            // for example:
            // $row[$unit->dbFieldName] *= 2.0 * (double)rand() / (double)getRandMax();
          }
        }

        $units[] = array('name'  => $unit->name,
                         'value' => $row[$unit->dbFieldName]);
      }
      if (sizeof($units)) $tmp['UNITS'] = $units;
      $resources = array();
      foreach ($resourceTypeList as $resource){
        if (!$row[$resource->dbFieldName]) continue;
        $resources[] = array('name'  => $resource->name,
                             'value' => $row[$resource->dbFieldName]);
      }
      if (sizeof($resources)) $tmp['RESOURCES'] = $resources;

      if ($row['isOwnMovement'] &&
          $ua_movements[$row['movementID']]->returnID != -1 &&
          !$row['artefactID'] &&
          !$row['blocked'])
        $tmp['CANCEL'] = array("modus" => UNIT_MOVEMENT,
                               "eventID" => $row['event_movementID']);
    }
    $result[] = $tmp;
  }
  return $result;
}

// FIXME: I guess there must be another function like this anywhere...
function digest_getCaveNameAndOwnerByCaveID($caveID){
  global $db;

  $query = "SELECT c.name AS cave_name, p.name AS player_name, ".
           "p.tribe AS player_tribe, c.xCoord, c.yCoord FROM Cave c " .
           "LEFT JOIN Player p ON c.playerID = p.playerID ".
           "WHERE c.caveID = " . ((int)$caveID);

  $dbresult = $db->query($query);
  if (!$dbresult) return array();

  return $dbresult->nextrow(MYSQL_ASSOC);
}


/*****************************************************************************/
/*                                                                          **/
/*      INITIATIONS                                                         **/
/*                                                                          **/
/*****************************************************************************/


function digest_getInitiationDates($meineHoehlen){
  global $db;

  $caveIDs = array();
  foreach ($meineHoehlen as $caveID => $value){
    array_push($caveIDs, "e.caveID = " . $caveID);
  }
  $caveIDs = implode(" OR ", $caveIDs);

  $query = "SELECT e.event_artefactID, e.caveID, e.artefactID, e.event_typeID, e.start, e.end, ac.name ".
           "FROM Event_artefact e ".
           "LEFT JOIN Artefact a ON e.artefactID = a.artefactID ".
           "LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID ".
           "WHERE " . $caveIDs . " ORDER BY e.end ASC, e.event_artefactID ASC";

  $dbresult = $db->query($query);
  if (!$dbresult){
    return array();
  }

  $result = array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $result[] = array('eventID'            => $row['event_artefactID'],
                      'event_typeID'       => $row['event_typeID'],
                      'name'               => $meineHoehlen[$row['caveID']]['name'],
                      'caveID'             => $row['caveID'],
                      'artefactID'         => $row['artefactID'],
                      'artefactName'       => $row['name'],
                      'event_start'        => time_formatDatetime($row['start']),
                      'event_end'          => time_formatDatetime($row['end']),
                      'seconds_before_end' => time_fromDatetime($row['end']) - time());
  }

  return $result;
}


/*****************************************************************************/
/*                                                                          **/
/*      APPOINTMENTS                                                        **/
/*                                                                          **/
/*****************************************************************************/


function digest_getAppointments($meineHoehlen){
  global $buildingTypeList,
         $scienceTypeList,
         $defenseSystemTypeList,
         $unitTypeList,
         $db;

  $caveIDs = array();
  foreach ($meineHoehlen as $caveID => $value)
    $caveIDs[] = $caveID;

  $caveIDs = "caveID IN (" . implode(", ", $caveIDs) . ")";

  $result = array();


  $query = sprintf('SELECT * FROM Event_unit WHERE %s ORDER BY end ASC, '.
                   'event_unitID ASC', $caveIDs);
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $result[] = array(
      'event_name'         => $row['quantity'] . "x " . $unitTypeList[$row['unitID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'unit',
      'modus'              => UNIT_BUILDER,
      'eventID'            => $row['event_unitID'],
      'event_end'          => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time());
  }

  $query = sprintf('SELECT * FROM Event_expansion WHERE %s ORDER BY end ASC, '.
                   'event_expansionID ASC', $caveIDs);
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $result[] = array(
      'event_name'         => $buildingTypeList[$row['expansionID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'building',
      'modus'              => IMPROVEMENT_DETAIL,
      'eventID'            => $row['event_expansionID'],
      'event_end'          => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time());
  }

  $query = sprintf('SELECT * FROM Event_defenseSystem WHERE %s ORDER BY end '.
                   'ASC, event_defenseSystemID ASC', $caveIDs);
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $result[] = array(
      'event_name'         => $defenseSystemTypeList[$row['defenseSystemID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'defense',
      'modus'              => EXTERNAL_BUILDER,
      'eventID'            => $row['event_defenseSystemID'],
      'event_end'          => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time());
  }

  $query = sprintf('SELECT * FROM Event_science WHERE %s ORDER BY end ASC, '.
                   'event_scienceID ASC', $caveIDs);
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $result[] = array(
      'event_name'         => $scienceTypeList[$row['scienceID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'science',
      'modus'              => SCIENCE,
      'eventID'            => $row['event_scienceID'],
      'event_end'          => time_formatDatetime($row['end']),
      'seconds_before_end' => time_fromDatetime($row['end']) - time());
  }
  usort($result, "datecmp");
  return $result;
}

// for comparing the dates of appointments
function datecmp($a, $b){
  if ($a['seconds_before_end'] == $b['seconds_before_end'])
    return 0;
  return ($a['seconds_before_end'] < $b['seconds_before_end']) ? -1 : 1;
}
?>
