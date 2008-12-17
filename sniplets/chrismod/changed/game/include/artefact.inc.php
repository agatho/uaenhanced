<?

define("ARTEFACT_INITIATING",  -1);
define("ARTEFACT_UNINITIATED",  0);
define("ARTEFACT_INITIATED",    1);


function artefact_getArtefactsReadyForMovement($caveID){
  global $db;

  $sql = 'SELECT * FROM Artefact a '.
         'LEFT JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID '.
//         'WHERE caveID = '.$caveID.' AND initiated = '.ARTEFACT_INITIATED;
// ADDED by chris--- for quests:
'WHERE (caveID = '.$caveID.' AND initiated = '.ARTEFACT_INITIATED.') OR (caveID = '.$caveID.' AND ac.quest_item = 1)';

  
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

function getArtefactMovement($artefactID, $showETA = false){
  global $db;

  $sql = 'SELECT source_caveID, target_caveID, movementID, '.
         'event_end '.
         'FROM Event_movement WHERE artefactID = ' . $artefactID;
  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }
  $result = $dbresult->nextrow(MYSQL_ASSOC);
  
  $result['event_end'] = date("d.m.Y H:i:s", time_timestampToTime($result['event_end']));

  $sql = "SELECT c.name AS source_cavename, c.xCoord AS source_xCoord, ".
         "c.yCoord AS source_yCoord, ".
         "IF(ISNULL(p.name), 'leere H&ouml;hle',p.name) AS source_name, ".
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
         "IF(ISNULL(p.name), 'leere H&ouml;hle',p.name) AS destination_name, ".
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

function artefact_getArtefactInitiationsForCave($caveID){
  global $db;

  $sql = 'SELECT * FROM `Event_artefact` WHERE `caveID` = ' . $caveID;
  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()){
    return array();
  }
  return $dbresult->nextrow(MYSQL_ASSOC);
}

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
    return "Dieses Artefakt kann nicht noch einmal eingeweiht werden.";
  }
  
  // Hol das Einweihungsritual
  $ritual = artefact_getRitualByID($artefact['initiationID']);
  if ($ritual === FALSE)
    return "Fehler: Ritual nicht gefunden.";
  
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
    return "Es fehlen die notwendigen Voraussetzungen.";
  }

  // register event
  $sql = "INSERT INTO Event_artefact " .
         "(caveID, artefactID, event_typeID, event_end) " .
         "VALUES ({$artefact['caveID']}, {$artefact['artefactID']}, 1, NOW() + INTERVAL {$ritual['duration']} SECOND)"; 

  //echo "try to register event:<br>" . $sql . "<br><br>";
  //echo "on failure:<br>" . $setBack . "<br><br>";

  if (!$db->query($sql)){
    $db->query($setBack);
    return "Sie weihen bereits ein anderes Artefakt ein.";
  }

  // finally set status to initiating
  $sql = "UPDATE Artefact SET initiated = " . ARTEFACT_INITIATING . " WHERE artefactID = {$artefact['artefactID']}";

  //echo "finally set status to initiating:<br>" . $sql . "<br><br>";
  $dbresult = $db->query($sql);
  if (!$dbresult) return "Fehler: Artefakt konnte nicht auf ARTEFACT_INITIATING gestellt werden.";
  return "erfolgreich eingeweiht";
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


/** Getting the destroy chance from artefact and returning it
 *  ADDED by chris--- for artefact destroying
 */
function artefact_getDestroyChance($artefactID) {
  global  $db;

  $sql="SELECT destroy_chance FROM Artefact a LEFT  JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID WHERE a.artefactID = ".$artefactID;

  $dbresult = $db->query($sql);
  if (!$dbresult || $db->affected_rows() != 1) return;

  $row = $dbresult->nextrow(MYSQL_ASSOC);

  $destroy_chance = $row[destroy_chance];
  if ($destroy_chance == 0) return;
  if ($destroy_chance < 0.11) return "Es gibt eine sehr kleine Chance, dass das Artefakt bei der Einweihung zerst&ouml;rt wird.";
  if ($destroy_chance < 0.31) return "Es gibt eine kleine Chance, dass das Artefakt bei der Einweihung zerst&ouml;rt wird.";
  if ($destroy_chance < 0.51) return "Es gibt eine reelle Chance, dass das Artefakt bei der Einweihung zerst&ouml;rt wird.";
  if ($destroy_chance < 0.71) return "Es gibt eine gro&szlig;e Chance, dass das Artefakt bei der Einweihung zerst&ouml;rt wird.";
  if ($destroy_chance < 1) return "Es gibt eine sehr gro&szlig;e Chance, dass das Artefakt bei der Einweihung zerst&ouml;rt wird.";

}

?>