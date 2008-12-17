<?

/* **** MOVEMENTS ***** ******************************************************/

function digest_getMovements($meineHoehlen, $doNotShow, $showDetails){
  global $resourceTypeList,
         $unitTypeList,
         $params,
         $db,
         $ua_movements;

  // caveIDs einsammeln
  $caveIDs = array();
  foreach ($meineHoehlen as $caveID => $value) $caveIDs[] = $caveID;
  $str_caveIDs = implode(",", $caveIDs);

  // Bewegungen besorgen
  $query = "SELECT * " .
           "FROM Event_movement " .
           "WHERE (source_caveID IN ({$str_caveIDs}) OR (target_caveID IN ({$str_caveIDs}))) " .
           "ORDER BY event_end ASC";
  $dbresult = $db->query($query);
  if (!$dbresult) return array();

  // bewegungen durchgehen
  $result = array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){

    // "do not show" movements should not be shown
// ADDED by chris--- for returns
if (!$params->SESSION->user['show_returns']) {
    if (in_array($row['movementID'], $doNotShow)) continue;
}


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
        $distance = time() - (time_timestampToTime($row['event_end']) - getDistanceByID($srcID, $destID) * $row['speedFactor']);
      } else {
        $sichtweite = getVisionRange($meineHoehlen[$row['target_caveID']]) * $row['speedFactor'];
        $distance = ceil((time_timestampToTime($row['event_end']) - time())/60);
      }

      if ($sichtweite < $distance) continue;
    }
*/
    // compute visibility
    if (!$row['isOwnMovement']){
      // don't show adverse reverse movements
      if ($ua_movements[$row['movementID']]->returnID == -1) continue;

      $sichtweite = getVisionRange($meineHoehlen[$row['target_caveID']]) * $row['speedFactor'];
      $distance = ceil((time_timestampToTime($row['event_end']) - time())/60);
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
                 'event_start'            => date("d.m.Y H:i:s", time_timestampToTime($row['event_start'])),
                 'event_end'              => date("d.m.Y H:i:s", time_timestampToTime($row['event_end'])),
                 'isOwnMovement'          => intval($row['isOwnMovement']),
                 'seconds_before_end'     => time_timestampToTime($row['event_end']) - time(),
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
      if ($row['artefactID'])
        $tmp['ARTEFACT'] = artefact_getArtefactByID($row['artefactID']);

      $units = array();
      foreach ($unitTypeList as $value){
        if (!$row[$value->dbFieldName]) continue;
        if (!$row['isOwnMovement'] && !$value->visible) continue;
        $units[] = array('name'  => $value->name,
                         'value' => $row[$value->dbFieldName]);
      }
      if (sizeof($units)) $tmp['UNITS'] = $units;
      $resources = array();
      foreach ($resourceTypeList as $value){
        if (!$row[$value->dbFieldName]) continue;
        $resources[] = array('name'  => $value->name,
                             'value' => $row[$value->dbFieldName]);
      }
      if (sizeof($resources)) $tmp['RESOURCES'] = $resources;

      if ($row['isOwnMovement'] &&
          $ua_movements[$row['movementID']]->returnID != -1 &&
          !$row['artefactID'] &&
          !$row['blocked'])
        $tmp['CANCEL'] = array("modus" => MOVEMENT,
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

/* **** INITIATIONS ***** ****************************************************/
function digest_getInitiationDates($meineHoehlen){
  global $db;

  $caveIDs = array();
  foreach ($meineHoehlen as $caveID => $value){
    array_push($caveIDs, "e.caveID = " . $caveID);
  }
  $caveIDs = implode(" OR ", $caveIDs);

  $query = "SELECT e.event_artefactID, e.caveID, e.artefactID, e.event_typeID, e.event_start, e.event_end, ac.name ".
           "FROM Event_artefact e ".
           "LEFT JOIN Artefact a ON e.artefactID = a.artefactID ".
           "LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID ".
           "WHERE " . $caveIDs . " ORDER BY e.event_end ASC";

  $dbresult = $db->query($query);
  if (!$dbresult){
    return array();
  }

  $result = array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $temp_time = time_timestampToTime($row['event_end']);
    $result[] = array('eventID'            => $row['event_artefactID'],
                      'event_typeID'       => $row['event_typeID'],
                      'name'               => $meineHoehlen[$row['caveID']]['name'],
                      'caveID'             => $row['caveID'],
                      'artefactID'         => $row['artefactID'],
                      'artefactName'       => $row['name'],
                      'event_start'        => date("d.m.Y H:i:s", time_timestampToTime($row['event_start'])),
                      'event_end'          => date("d.m.Y H:i:s", $temp_time),
                      'seconds_before_end' => $temp_time - time());
  }

  return $result;
}

/* **** APPOINTMENTS ***** ***************************************************/

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


  $query = "SELECT * FROM Event_unit WHERE " . $caveIDs;
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $temp_time = time_timestampToTime($row['event_end']);
    $result[] = array(
      'event_name'         => $row['quantity'] . "x " . $unitTypeList[$row['unitID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'unit',
      'modus'              => UNIT_BUILDER,
      'eventID'            => $row['event_unitID'],
      'event_end'          => date("d.m.Y H:i:s", $temp_time),
      'seconds_before_end' => $temp_time - time());
  }

  $query = "SELECT * FROM Event_expansion WHERE " . $caveIDs;
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $temp_time = time_timestampToTime($row['event_end']);
    $result[] = array(
      'event_name'         => $buildingTypeList[$row['expansionID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'building',
      'modus'              => IMPROVEMENT_DETAIL,
      'eventID'            => $row['event_expansionID'],
      'event_end'          => date("d.m.Y H:i:s", $temp_time),
      'seconds_before_end' => $temp_time - time());
  }

  $query = "SELECT * FROM Event_defenseSystem WHERE " . $caveIDs;
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $temp_time = time_timestampToTime($row['event_end']);
    $result[] = array(
      'event_name'         => $defenseSystemTypeList[$row['defenseSystemID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'defense',
      'modus'              => DEFENSESYSTEM,
      'eventID'            => $row['event_defenseSystemID'],
      'event_end'          => date("d.m.Y H:i:s", $temp_time),
      'seconds_before_end' => $temp_time - time());
  }

  $query = "SELECT * FROM Event_science WHERE " . $caveIDs;
  if (!($dbresult = $db->query($query))) return array();
  while($row = $dbresult->nextrow(MYSQL_ASSOC)){
    $temp_time = time_timestampToTime($row['event_end']);
    $result[] = array(
      'event_name'         => $scienceTypeList[$row['scienceID']]->name,
      'cave_name'          => $meineHoehlen[$row['caveID']]['name'],
      'caveID'             => $row['caveID'],
      'category'           => 'science',
      'modus'              => SCIENCE,
      'eventID'            => $row['event_scienceID'],
      'event_end'          => date("d.m.Y H:i:s", $temp_time),
      'seconds_before_end' => $temp_time - time());
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
