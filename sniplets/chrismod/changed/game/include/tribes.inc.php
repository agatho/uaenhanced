<?php


DEFINE("TRIBE_MESSAGE_WAR",      1);
DEFINE("TRIBE_MESSAGE_LEADER",   2);
DEFINE("TRIBE_MESSAGE_MEMBER",   3);
DEFINE("TRIBE_MESSAGE_RELATION", 4);
DEFINE("TRIBE_MESSAGE_INFO",    10);


function leaderDetermination_processChoiceUpdate($voterID,
						 $playerID,
						 $tribe,
						 $db) 
{
  if ($playerID == 0) {
    if (! leaderDetermination_deleteChoiceForPlayer($voterID, $db)) {
      return -1;
    }
    return 1;
  }
  else {
    $query =
      "REPLACE Election ".
      "SET voterID = '$voterID', ".
      "playerID = '$playerID', ".
      "tribe = '$tribe' ";

    if (! $db->query($query) ) {
      return -1;
    }
    return 1;    
  }
}


function leaderDetermination_deleteChoiceForPlayer($voterID, $db) {
    $query =
      "DELETE FROM Election ".
      "WHERE voterID = '$voterID' ";

    if (! $db->query($query) ) {
      return 0;
    }
    return 1;
}
  

function leaderDetermination_getElectionResultsForTribe($tribe, $db) {
  $query =
    "SELECT p.name, COUNT(e.voterID) AS votes ".
    "FROM Election e ".
    "LEFT JOIN Player p ON p.playerID = e.playerID ".
    "WHERE e.tribe like '$tribe' ".
    "GROUP BY e.playerID, p.name";

  if (!($result = $db->query($query))) {
    return 0;
  }

  $votes = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    array_push($votes, $row);
  }
  return $votes;
}

function leaderDetermination_getVoteOf($playerID, $db) {
  $query = 
    "SELECT playerID ".
    "FROM Election ".
    "WHERE voterID = '$playerID'";

  if (!($result = $db->query($query))) {
    return 0;
  }

  if (!($row = $result->nextRow(MYSQL_ASSOC))) {
    return 0;
  }
  return $row[playerID];
}

function government_getGovernmentForTribe($tag, $db) {
  $query =
    "SELECT governmentID,  ".
    "DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, ".
    "duration, ".
    "duration < NOW()+0 AS isChangeable ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tag' ";

  if (!($result = $db->query($query))) {
    return 0;
  }
  if (!($data = $result->nextRow(MYSQL_ASSOC))) {
    return 0;
  }
  return $data ;

}

function government_setGovernment($tag, $governmentID, $db) {

  $query = 
    "UPDATE Tribe ".
    "SET governmentID = '$governmentID', ".
    "duration = (NOW() + INTERVAL ".GOVERNMENT_CHANGE_TIME_HOURS." HOUR)+0 ".
    "WHERE tag LIKE '$tag'";

  if (!$db->query($query)) {
    return 0;
  }
  return 1;
} 

function government_processGovernmentUpdate($tag, $governmentData, $db) {
  global $governmentList;
  
  if (!($oldGovernment = government_getGovernmentForTribe($tag, $db))) {
    return -8;
  }
  if (!$oldGovernment[isChangeable]) {
    return -9;
  }
  
  if (!government_setGovernment($tag, $governmentData[governmentID], $db)) {
    return -8;
  }

  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_LEADER,
			 "Die Regierung wurde ge&auml;ndert",
			 "Ihr Clananf&uuml;hrer hat die Regierung Ihres ".
			 "Clans auf ".
			 $governmentList[$governmentData[governmentID]][name].
			 " ge&auml;ndert.");

  return 4;
}

function relation_processForceRelation($tag, $forceData, $db) {
  global $relationList;

  // schauen, ob Beziehungstyp zwischen $tag und $forceData[$tag] 
  // == RELATION_FORCE_FROM_ID
  // und überprüfen, das gegnerische Moral niedrig ist

  $query =
    "SELECT r.*, ".
    "(NOW()+0) > r.duration AS changeable, ".
    "r2.moral <= ".RELATION_FORCE_MORAL_THRESHOLD." AS forceable ".
    "FROM Relation r ".
    "LEFT JOIN Relation r2 ".
    "ON r2.tribe LIKE r.tribe_target AND r2.tribe_target LIKE r.tribe ".
    "WHERE r.tribe LIKE '$tag' ".
    "AND r.tribe_target LIKE '".$forceData[tag]."' ".
    "AND r.relationType = '".RELATION_FORCE_FROM_ID."' ";

  if (!($result = $db->query($query))) {
    echo $query;
    return -3;
  }

  if (!($relation = $result->nextRow())) {
    return -10;
  }

  if (!$relation[changeable]) {
    return -4;
  }

  if (!$relation[forceable]) { // muss die mitgliederbedingung geprüft werden?
    $target_members = tribe_getNumberOfMembers($forceData[tag], $db);
    
    // nicht mehr member als absoluter threshold weg ||
    // nicht mehr member als relativer threshold * ursprungsgroesse weg
    if ($relation[target_members] - $target_members <
	RELATION_FORCE_MEMBERS_LOST_ABSOLUT ||
	$relation[target_members] - $target_members <
	RELATION_FORCE_MEMBERS_LOST_RELATIVE * $relation[target_members] ){
      return -11;
    }
  }
    
  // Bedingungen erfüllt, Kapitulation kann erzwungen werden!
  // dazu die normale Funktion so aufrufen, als wenn der Kriegsgegner die
  // Kapitulation (RELATION_FORCE_TO_ID) angeklickt haette
  return 
    relation_processRelationUpdate($forceData[tag],
				   array("tag"        => $tag,
					 "relationID" => RELATION_FORCE_TO_ID),
				   $db);  
}

function relation_processRelationUpdate($tag, $relationData, $db) {
  global $relationList;

  if (strcasecmp($tag, $relationData[tag]) == 0) {
    return -7;
  }

  if (!($targetTribeInfo = tribe_getTribeByTag($relationData[tag], $db))) {
    return -6;
  }

  $relationType = $relationData[relationID];
  $relationInfo = $relationList[$relationType];

  if (! ($relation = relation_getRelation($tag, $relationData[tag], $db))) {
    return -3;
  }
  $relationTypeActual = $relation[own][relationType];

  if ( $relationTypeActual == $relationType ) { // change to actual relation?
    return -13;
  }

  if (! $relation[own][changeable]) {
    return -4;
  }

  // check if switching to same relation as target or relation is possible
  if ($relation[other][relationType] != $relationType &&
      ! relation_isPossible($relationType,
                            $relation[own][relationType])) {
    return -5;
  }

  // check minimum size of target tribe
  // BUT: relation of target tribe to us is always ok!
  if ($relationInfo[targetMinSize] > 0.0 &&
      $relation[other][relationType] != relationType ) {
    if (($from_points   = tribe_getMight($tag, $db)) < 0) {
      $from_points = 0;
    }
    if (($target_points = tribe_getMight($relationData['tag'], $db)) < 0) {
      $target_points = 0;
    }
    if ($from_points * $relationInfo[targetMinSize] > $target_points) {
      return -12;
    }
  }
  
  // if switching to the same relation of other clan towards us, 
  // use their treaty's end_time!
  if ($relationType == $relation[other][relationType] &&
      $relationType != 0) {
    $end_time = $relation[other][duration];
  }
  else {   
    $duration = 
      $relationList[$relationTypeActual][transitions][$relationType][time];
  }

  if (!relation_setRelation($tag, $targetTribeInfo[tag], 
			    $relationType,
			    $duration, $db, $end_time,
			    $relation[own][tribe_rankingPoints],
			    $relation[own][target_rankingPoints])) {
    return -3;
  }

  // insert history message
  if ($message = $relationList[$relationType][historyMessage]) {
    relation_insertIntoHistory(
      $tag, 
      relation_prepareHistoryMessage($tag,
				     $targetTribeInfo[tag],
				     $message),
      $db);
  }
  
  $relationName = $relationList[$relationType][name];
  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_RELATION,
			 "Haltung gegen&uuml;ber $targetTribeInfo[tag] ".
			 "ge&auml;ndert",
			 "Ihr Clananf&uuml;hrer hat die Haltung Ihres ".
			 "Clans gegen&uuml;ber dem Clan ".
			 "$targetTribeInfo[tag] auf $relationName ".
			 "ge&auml;ndert.");

  tribe_sendTribeMessage($targetTribeInfo[tag], TRIBE_MESSAGE_RELATION,
			 "Der Clan $tag &auml;ndert seine Haltung",
			 "Der Clananf&uuml;hrer des Clans $tag ".
			 "hat die Haltung seines ".
			 "Clans ihnen gegen&uuml;ber ".
			 "auf $relationName ".
			 "ge&auml;ndert.");

  // switch other side if necessary (and not at this type already)
  if (!$end_time && ($oST = $relationInfo[otherSideTo]) >= 0) {
    if (!relation_setRelation($targetTribeInfo[tag], $tag,
			      $oST, $duration, $db, 0,
			      $relation[other][tribe_rankingPoints],
			      $relation[other][target_rankingPoints])) {
      return -3;
    } 

    // insert history
    if ($message = $relationList[$oST][historyMessage]) {
      relation_insertIntoHistory(
	$targetTribeInfo[tag], 
	relation_prepareHistoryMessage($tag,
				       $targetTribeInfo[tag],
				       $message),
	$db);
    }
    
    
    $relationName = $relationList[$oST][name];
    tribe_sendTribeMessage($targetTribeInfo[tag], TRIBE_MESSAGE_RELATION,
			   "Haltung gegen&uuml;ber $tag ".
			   "ge&auml;ndert",
			   "Die Haltung Ihres ".
			   "Clans gegen&uuml;ber dem Clan ".
			   "$tag  wurde automatisch auf $relationName ".
			   "ge&auml;ndert.");
    
    tribe_sendTribeMessage($tag, TRIBE_MESSAGE_RELATION,
			   "Der Clan $targetTribeInfo[tag] &auml;ndert ".
			   "seine ".
			   "Haltung",
			   "Der Clan $targetTribeInfo[tag] ".
			   "hat die Haltung ihnen gegen&uuml;ber ".
			   "automatisch auf $relationName ".
			   "ge&auml;ndert.");
  }
  return 3; 
}

/*
 * at the moment, it is allowed to leave the tribe, if there is another
 * member
 */
function relation_leaveTribeAllowed($tag, $db) {
  global
    $relationList;

  if (!($tribeRelations = relation_getRelationsForTribe($tag, $db))) {
    return 0;
  }
  
  foreach($relationList AS $relationTypeID => $relationType) {
    if ($relationType[dontLeaveTribe]) {
      foreach($tribeRelations[own] AS $target => $relation) {
	if ($relation[relationType] == $relationTypeID) {
	  return 0;
	}
      }
    }
  }
  return 1;
}


function relation_getTribeHistory($tribe, $db) {
  $query =
    "SELECT * ".
    "FROM TribeHistory ".
    "WHERE tribe LIKE '$tribe' ".
    "ORDER BY timestamp ASC";

  if (!($result = $db->query($query))) {
    return 0;
  }
  $history = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    array_push($history, $row);
  }
  return $history;
}

function relation_insertIntoHistory($tribe, $message, $db) {
  $time = getUgaAggaTime(time());
  $month = getMonthName($time[month]);

  $query =
    "INSERT INTO TribeHistory ".
    "(tribe, ingameTime, message) ".
    "values ('$tribe', '$time[day]. $month<br>im Jahr $time[year]', ".
            "'$message')";
  return $db->query($query);
}

function relation_prepareHistoryMessage($tribe, $target, $message) {
  return str_replace("[TARGET]", $target, 
		     str_replace("[TRIBE]", $tribe, $message));
}

function tribe_getPoints($tag, $db) {
  $query =
    "SELECT * ".
    "FROM RankingTribe ".
    "WHERE tribe LIKE '$tag'";
  
  if (!($result = $db->query($query))) {
    return -1;
  }
  if (!($row = $result->nextRow(MYSQL_ASSOC))) {
    return 0;
  }
  return $row[points];
}

/*
 * this function returns the might (points_rank) for the given tribe.
 * the might are the tribe points WITHOUT fame.
 */
function tribe_getMight($tag, $db) {
  $query =
    "SELECT points_rank ".
    "FROM RankingTribe ".
    "WHERE tribe LIKE '$tag'";
  
  if (!($result = $db->query($query))) {
    return -1;
  }
  if (!($row = $result->nextRow(MYSQL_ASSOC))) {
    return 0;
  }
  return $row[points_rank];
}

/**
 * calculate the fame according to the following formula:
 * basis * (V/S) * (V/S) * (S'/V')
 * this is bigger: if, winner had more points,
 * winner gained more points during the battle compared to looser
 */
function relation_calcFame($winner, $winnerOld, $looser, $looserOld) {
  $winner = $winner ? $winner : 1;
  $winner_old = $winner ? $winner : 1;
  $looser = $looser ? $looser : 1;
  $looser_old = $looser_old ? $looser_old : 1;
  
  return 
    (100 + ($winnerOld + $looserOld) / 200) *         // basis points
    max(.125, min(8, ($looser / $winner) * ($looser / $winner) * ($winner_old / $looser_old)));
}

function relation_setRelation($from, $target, $relation, $duration, $db,
                              $end_time, $from_points_old, $target_points_old) {
  global
    $relationList;

  if (($from_points = tribe_getMight($from, $db)) < 0) {
    $from_points = 0;
  }
  if (($target_points = tribe_getMight($target, $db)) < 0) {
    $from_points = 0;
  }

  // have to remember the number of members of the other side?
  if ($relationList[$relation]['storeTargetMembers']) {
    $target_members = tribe_getNumberOfMembers($target, $db);
  }

  if ($relation == 0) {
    $query =
      "DELETE FROM Relation ".
      "WHERE tribe = '$from' ".
      "AND tribe_target = '$target'";
  }
  else {
    $query = 
      "REPLACE Relation ".
      "SET tribe = '$from', ".
      ($target_members != 0 ? "target_members = '$target_members', " : "").
      "tribe_target = '$target', ".
      "timestamp = NOW() +0, ".
      "relationType = '$relation', ".
      "tribe_rankingPoints = '$from_points', ".
      "target_rankingPoints = '$target_points', ".
      "attackerReceivesFame = '".
      $relationList[$relation][attackerReceivesFame]."', ".
      "defenderReceivesFame = '".
      $relationList[$relation][defenderReceivesFame]."', ".
      "defenderMultiplicator = '".
      $relationList[$relation][defenderMultiplicator]."', ".
      "attackerMultiplicator = '".
      $relationList[$relation][attackerMultiplicator]."', ".
      ($end_time ? 
       "duration = '$end_time' " :
       "duration = (NOW() + INTERVAL '$duration' HOUR) + 0 ");
  }

  if (!$db->query($query)) {
    echo $query;
    return 0;
  }

  // calculate the fame update if necessary
  if ($relationList[$relation][fameUpdate] != 0) {
    if ($relationList[$relation][fameUpdate] > 0) {
      $fame = relation_calcFame($from_points, $from_points_old, 
                                $target_points, $target_points_old);
    }
    else if ($relationList[$relation][fameUpdate] < 0) { 
      // calculate fame: first argument is winner!
      $fame = -1 * relation_calcFame($target_points, $target_points_old, 
                                     $from_points, $from_points_old);
    }
    $query = 
      "UPDATE Tribe ".
      "SET fame = fame + $fame ".
      "WHERE tag LIKE '$from'";

    if (!$db->query($query)) {
      echo $query;
      return 0;
    }
  }

  return 1;
}

function relation_getRelation($from, $target, $db) {
  $query =
    "SELECT *, ".
    "DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, ".
    "(NOW()+0) > duration AS changeable ".
    "FROM Relation ".
    "WHERE tribe LIKE '$from' ".
    "AND tribe_target LIKE '$target'";
  
  if (!($relations = $db->query($query))) {
    return 0;
  }

  if (!($own = $relations->nextRow(MYSQL_ASSOC))) {
    $own = array("tribe"        => $from,
		 "tribe_target" => $target,
                 "changeable"   => 1,
		 "relationType" => 0);
  }

  $query =
    "SELECT *, ".
    "DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, ".
    "(NOW()+0) > duration AS changeable ".
    "FROM Relation ".
    "WHERE tribe LIKE '$target' ".
    "AND tribe_target LIKE '$from'";
  
  if (!($relations = $db->query($query))) {
    return 0;
  }

  if (!($other = $relations->nextRow(MYSQL_ASSOC))) {
    $other = array("tribe"        => $target,
		   "tribe_target" => $from,
                   "changeable"   => 1,
		   "relationType" => 0);
  }
  
  return array("own" => $own, "other" => $other);
}

  

function relation_isPossible($to, $from) {
  global $relationList;
  return array_key_exists($to, $relationList[$from][transitions]);
}



function relation_getRelationsForTribe($tag, $db) {
  // get relations from $tag to other tribes
  
  $query =
    "SELECT *, ".
    "DATE_FORMAT(duration, '%d.%m.%Y %H:%i:%s') AS time, ".
    "(NOW()+0) > duration AS changeable ".
    "FROM Relation ".
    "WHERE tribe LIKE '$tag'";
  
  if (!($relations = $db->query($query))) {
    return 0;
  }

  $own=array();
  while ($row = $relations->nextRow(MYSQL_ASSOC)) {
    $own[strtoupper($row[tribe_target])] = $row;
  }

  // get relations from other tribes to $tag

  $query =
    "SELECT * ".
    "FROM Relation ".
    "WHERE tribe_target LIKE '$tag'";
  
  if (!($relations = $db->query($query))) {
    return 0;
  }

  $other=array();
  while ($row = $relations->nextRow(MYSQL_ASSOC)) {
    $other[strtoupper($row[tribe])] = $row;
  }

  return array("own" => $own, "other" => $other);
}
  

function tribe_processAdminUpdate($leaderID, $tag, $data, $db) {
  // list of fields, that should be inserted into the player record
  $fields = 
    array("name", "password", "description");  
		   

  // first update data
  $data[description] = nl2br($data[description]); 

  if ($set = db_makeSetStatementSecure($data, $fields)) {
    if (!$db->query($query=
      "UPDATE Tribe ".
      "SET ".$set." ".
      "WHERE tag = '$tag' ".
      "AND leaderID = '$leaderID'")) 
    {
      return 2;
    }
  }
 
  return 0;
}

/**
 * returns all tribes in an associative array (tag => data_array)
 */
function tribe_getAllTribes($db) {
  $query = 
    "SELECT * ".
    "FROM Tribe ";
  
  $db_all_tribes = $db->query($query);
  if (!$db_all_tribes){
    return -1;
  }

  $tribes = array();
  while ($row = $db_all_tribes->nextRow(MYSQL_ASSOC)) {
    $tribes[$row[tag]] = $row;
  }
  return $tribes;  
}

function tribe_getAllMembers($tag, $db) {
  $query = "SELECT p.playerID, p.name, s.lastAction ".
           "FROM Player p ".
           "LEFT  JOIN  `Session` s ON s.playerID = p.playerID ".
           "WHERE tribe LIKE '$tag' ".
           "ORDER BY name ASC";

  $result = $db->query($query);
  if (!$result) {
    return -1;
  }

  $members = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    $row['lastAction'] = date("d.m.Y H:i:s", time_timestampToTime($row['lastAction']));
    $members[$row['playerID']] = $row;
  }
  return $members;
}

/**
 * is the tribe old enough, to be deleted?
 */
function tribe_isDeletable($tag, $db) {
  global $relationList; 

  $query = 
    "SELECT * ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tag' ".
    "AND created < (NOW() - INTERVAL ".TRIBE_MINIMUM_LIVESPAN." SECOND) + 0";

  
  // if relationList instantiatetd, check Relations
  if ($relationList && ! relation_leaveTribeAllowed($tag, $db) ) {
    return 0;
  }

  $result = $db->query($query);
  if (!$result){
    return 0;
  }

  return ($result->nextRow() ? 1 : 0);  
}

/**
 * returns the number of the members of a given clan
 * -1 => ERROR !!!!
 */
function tribe_getNumberOfMembers($tag, $db) {
   $query = 
      "SELECT COUNT(playerID) AS members ".
      "FROM Player ".
      "WHERE tribe LIKE '$tag' ";

    $db_members = $db->query($query);
    if (!$db_members || !$row_count = $db_members->nextRow()){
      return -1;
    } 
    return $row_count[members];
}

function tribe_getTribeByTag($tag, $db) {
  $query = 
    "SELECT t.*, p.name AS leaderName ".
    "FROM Tribe t ".
    "LEFT JOIN Player p ON t.leaderID =p. playerID ".
    "WHERE t.tag LIKE '$tag'";

  if (!($result = $db->query($query))) {
    return 0;
  }
  if (!($row = $result->nextRow(MYSQL_ASSOC))) {
    return 0;
  }
  return $row;
}

function tribe_makeLeader($playerID, $tag, $db) {
  $query = 
    "UPDATE Tribe ".
    "SET leaderID = '$playerID' ".
    "WHERE tag LIKE '$tag' ".
    "AND leaderID = 0";

  if (! $db->query($query) || ! $db->affected_Rows() > 0) {
    return 0;
  }
  return 1;
}

function tribe_unmakeLeader($playerID, $tag, $db) {
  $query = 
    "UPDATE Tribe ".
    "SET leaderID = 0 ".
    "WHERE tag LIKE '$tag' ";

  if (! $db->query($query) || ! $db->affected_Rows() > 0) {
    return 0;
  }
  return 1;
}

Function tribe_joinTribe($playerID, $tag, $db) {
  $query = 
    "UPDATE Player ".
    "SET tribe = '$tag' ".
    "WHERE playerID = '$playerID' ".
    "AND tribe LIKE ''";

  if (! $db->query($query) || ! $db->affected_Rows() > 0) {
    return 0;
  }
  return 1;
}

function tribe_leaveTribe($playerID, $tag, $db) {
  $query = 
    "UPDATE Player ".
    "SET tribe = '', ".
    "fame = fame - ".TRIBE_LEAVE_FAME_COST." ".
    "WHERE playerID = '$playerID' ".
    "AND tribe LIKE '$tag'";

  if (! $db->query($query) || ! $db->affected_Rows() > 0) {
    return 0;
  }

  $query = 
    "DELETE FROM Election ".
    "WHERE voterID = '$playerID' ".
    "OR playerID LIKE '$playerID'";

  if (! $db->query($query)) {
    return 0;
  }

  return 1;
}

function tribe_createTribe($tag, $name, $leaderID, $db) {
  $query = 
    "INSERT INTO Tribe ".
    "(tag, name, leaderID, created, governmentID) ".
    "values ('$tag', '$name', 0, NOW() + 0, 1)";

// ADDED by chris--- cause this is nicer
// -------------------------------------------------------------------------------

$leadername = getPlayerFromID($leaderID);
$message = $leadername['name'] . " gr&uuml;ndet den Clan " . $tag . ".";
relation_insertIntoHistory($tag, $message, $db);

tribe_sendTribeMessage($tag, TRIBE_MESSAGE_INFO, "Clan gegr&uuml;ndet!", $message);

// -------------------------------------------------------------------------------

  if (! $db->query($query)) {
    return 0;
  }

  if ($leaderID && ! tribe_joinTribe($leaderID, $tag, $db)) {
    return 0;
  }

  if ($leaderID && ! tribe_makeLeader($leaderID, $tag, $db)) {
    tribe_leaveTribe($leaderID, $tag, $db);
    return 0;
  }
  return 1;
}

function tribe_deleteTribe($tag, $db, $FORCE = 0) {
  if (! $FORCE && ! relation_leaveTribeAllowed($tag, $db) ) {
    return 0;
  }
  if (!($tribe = tribe_getTribeByTag($tag, $db))) {
    return 0;
  }
  if ($tribe[leaderID] &&  
      ! tribe_unmakeLeader($tribe[leaderID], $tag, $db))
  {
    return 0;
  }
  if (($members = tribe_getAllMembers($tag, $db)) < 0) {
    return 0;
  }

  foreach ($members AS $playerID => $playerData) {
    if (! tribe_leaveTribe($playerID, $tag, $db))
    {
      return 0;
    }
    messages_sendSystemMessage($playerID,
                               8,
			      "Aufl&ouml;sung des Clans",
			      "Ihr Clan $tag wurde soeben aufgel&ouml;st. ".
			      "Sollten Sie Probleme mit dem ".
			      "Clanmen&uuml; haben, loggen Sie sich ".
			      "bitte neu ein.",
			      $db);
  }

  $query = 
    "DELETE FROM Tribe ".
    "WHERE tag LIKE '$tag'";
 
  if (!$db->query($query)) {
    return 0;
  }

  $query = 
    "DELETE FROM Relation ".
    "WHERE tribe LIKE '$tag' ".
    "OR tribe_target LIKE '$tag'";
 
  if (!$db->query($query)) {
    return 0;
  }

  $query = 
    "DELETE FROM TribeMessage ".
    "WHERE tag LIKE '$tag'";
 
  if (!$db->query($query)) {
    return 0;
  }

  $query = 
    "DELETE FROM TribeHistory ".
    "WHERE tribe LIKE '$tag'";
 
  if (!$db->query($query)) {
    return 0;
  }

  $query = 
    "DELETE FROM Election ".
    "WHERE tribe LIKE '$tag'";
 
  if (!$db->query($query)) {
    return 0;
  }

  return 1;
}

function tribe_recalcLeader($tag, $oldLeaderID, $db) {
  global
    $governmentList;

  // find the new leader

  if(!($government =
       government_getGovernmentForTribe($tag, $db))) {
    return -1;
  }

  $det = $governmentList[$government[governmentID]][leaderDeterminationID];

  switch ($det) {
    case 1:
      $newLeader = tribe_recalcLeader1($tag, $db);
      break;
    case 2:
      $newLeader = tribe_recalcLeader2($tag, $db);
      break;
  }
  if ($newLeader < 0) {
    return $newLeader;
  }

  // change the leader

  if ($newLeader == $oldLeaderID) {
    return 0;
  }
  if ($oldLeaderID && !tribe_unmakeLeader($oldLeaderID, $tag, $db))
  {
    return -1;
  }
  if ($newLeader && !tribe_makeLeader($newLeader, $tag, $db))
  {
    return -1;
  }

  if (!$newLeader) {
    tribe_sendTribeMessage($tag,
			   TRIBE_MESSAGE_LEADER,
			   "Anf&uuml;hrerwechsel",
			   "Ihr Clan hat momentan keinen Anf&uuml;hrer ".
			   "mehr");
  }
  else {
    $player = getPlayerFromID($newLeader);
    $newLeaderName = $player ? $player[name] : $newLeader;
    tribe_sendTribeMessage($tag,
			   TRIBE_MESSAGE_LEADER,
			   "Anf&uuml;hrerwechsel",
			   "Der Spieler $newLeaderName ist soeben neuer ".
			   "Anf&uuml;hrer des Clans geworden.");
  }

  return $newLeader;
}

/**
 * recalc the leader for government ID 1
 */
function tribe_recalcLeader1($tag, $db) {
  $query = 
    "SELECT p.playerID, p.name ".
    "FROM Player p ".
    "LEFT JOIN Ranking r ON p.playerID = r.playerID ".
    "WHERE p.tribe LIKE '$tag' ".
    "AND r.playerID IS NOT NULL ".
    "ORDER BY r.rank ASC ".
    "LIMIT 0, 1";

  if (!($result = $db->query($query))) 
  {
    return -1;
  }
  if (!($row = $result->nextRow())) {
    return 0; // no leader!
  }
  return $row[playerID];
}

/**
 * recalc the leader for government ID 2
 */
function tribe_recalcLeader2($tag, $db) {
  $query =
    "SELECT e.playerID, COUNT(e.voterID) AS votes ".
    "FROM Election e ".
    "LEFT JOIN Player p ON p.playerID = e.playerID ".
    "WHERE e.tribe like '$tag' ".
    "GROUP BY e.playerID, p.name ".
    "ORDER BY votes DESC ".
    "LIMIT 0,1" ;

  if (!($result = $db->query($query))) 
  {
    return -1;
  }
  if (!($row = $result->nextRow())) {
    return 0; // no leader!
  }
  if ($row[votes] <=  tribe_getNumberOfMembers($tag, $db) / 2) 
  {          // more than 50% ?
    return 0;
  }

  return $row[playerID];
}


function tribe_processJoin($playerID, $tag, $password, $db) {

  if (! tribe_changeTribeAllowedForPlayerID($playerID, $db)) {
    return -11;
  }
  if (! relation_leaveTribeAllowed($tag, $db) ) {
    return -12;
  }

  $query = 
    "SELECT name ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tag' ".
    "AND password = '$password' ";

  if (!($result=$db->query($query))) {
    return -1;
  }

  if (!($row = $result->nextRow())) {
    return -2;
  }

  if (!($player=getPlayerFromID($playerID))) {
    return -3;
  }
  if (!tribe_joinTribe($playerID, $tag, $db)) {
    return -3;
  }

  tribe_setBlockingPeriodPlayerID($playerID, $db);
 
  tribe_sendTribeMessage($tag,
			 TRIBE_MESSAGE_MEMBER,
			 "Spielerbeitritt",
			 "Der Spieler $player[name] ist soeben dem ".
			 "Clan beigetreten.");

  return 1;
}

function tribe_setBlockingPeriodPlayerID($playerID, $db) {
  $query =
    "UPDATE Player ".
    "SET tribeBlockEnd = (NOW() + INTERVAL ".
    TRIBE_BLOCKING_PERIOD_PLAYER." SECOND)+0 ".
    "WHERE playerID = '$playerID'";

  return $db->query($query);
}

function tribe_changeTribeAllowedForPlayerID($playerID, $db) {
  $query =
    "SELECT (tribeBlockEnd > NOW()+0) AS blocked ".
    "FROM Player ".
    "WHERE playerID = '$playerID'";

  if (!($result = $db->query($query)) || !($row = $result->nextRow())) {
    return 0;
  } 
  return $row[blocked] != 1;
}


function tribe_processLeave($playerID, $tag, $db, $FORCE = 0) {
  if (! $FORCE && ! relation_leaveTribeAllowed($tag, $db)) {
    return -10;
  }

  if (! $FORCE && ! tribe_changeTribeAllowedForPlayerID($playerID, $db)) {
    return -11;
  }

  if (tribe_isLeader($playerID, $tag, $db)) {
    if (! $FORCE && !tribe_unmakeLeader($playerID, $tag, $db)) {
      return -8;
    }
  }

  if (!($player=getPlayerFromID($playerID))) {
    return -4;
  }   
  if (!tribe_leaveTribe($playerID, $tag, $db)) {
    return -4;
  }


  tribe_setBlockingPeriodPlayerID($playerID, $db);

  tribe_sendTribeMessage($tag,
			 TRIBE_MESSAGE_MEMBER,
			 "Spieleraustritt",
			 "Der Spieler $player[name] ist soeben aus dem ".
			 "Clan ausgetreten.");
  
  if (tribe_getNumberOfMembers($tag, $db) == 0) {  // tribe has to be deleted
    tribe_deleteTribe($tag, $db, $FORCE);
    return 4;
  }

  return 2;
}

function tribe_processKickMember($playerID,
				 $tag,
				 $db) 
{
  if (tribe_isLeader($playerID, $tag, $db)) {
    return -2;
  }

  if (!($player=getPlayerFromID($playerID))) {
    return -1;
  }  
  if (!tribe_leaveTribe($playerID, $tag, $db)) {
    return -1;
  }

  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_MEMBER,
			 "Spieler rausgeschmissen",
			 "Der Spieler $player[name] wurde soeben vom ".
			 "Anf&uuml;hrer aus dem Clan ausgeschlossen.");

  messages_sendSystemMessage($playerID,
                             8, 
			    "Clanausschluss.",
			    "Sie wurden aus dem Clan $tag ".
			    "ausgeschlossen. Bitte loggen Sie sich aus und ".
			    "melden Sie sich wieder an, damit das ".
			    "Clanmen&uuml; bei Ihnen wieder richtig ".
			    "funktioniert.",
			    $db);

  return 1;
}

function tribe_processSendTribeMessage($leaderID, $tag, $message, $db) {

  if (!tribe_isLeader($leaderID, $tag, $db)) {
    return -9;
  }

  if (!tribe_sendTribeMessage($tag,
                              TRIBE_MESSAGE_LEADER,
                              "Nachricht vom Clananf&uuml;hrer",
                              nl2br($message))) {
    return -9;
  }
  return 5;
}

function tribe_sendTribeMessage($tag, $type, $heading, $message) {
  global $db;
  
  $query =
    "INSERT INTO TribeMessage ".
    "(tag, messageClass, messageSubject, messageText, messageTime) ".
    "values( '$tag', '$type', '$heading', '$message', NOW()+0 )";

  return $db->query($query);
}

function tribe_getTribeMessages($tag, $db) {
  $query = 
    "SELECT *, DATE_FORMAT(messageTime, '%d.%m.%Y %H:%i') AS date ".
    "FROM TribeMessage ".
    "WHERE tag LIKE '$tag' ".
    "ORDER BY messageTime DESC ".
    "LIMIT 0, 30";

  $result = $db->query($query);
  if (!$result){
    return 0;
  }

  $messages = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    $messages[$row[tribeMessageID]] = $row;
  }
  return $messages;
}

function tribe_processCreate($leaderID,
			     $tag,
			     $password,
			     $db)
{
  if (! tribe_changeTribeAllowedForPlayerID($leaderID, $db)) {
    return -11;
  }

  $query = 
    "SELECT name ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tag'";

  if (!($result = $db->query($query)))
  {
    return -1;
  }

  if ($result->nextRow()) {
    return -5;
  }
  if (!tribe_createTribe($tag, $tag, $leaderID,  $db))
  {
    return -6;
  }      
  if (!tribe_setPassword($tag, $password, $db)) {
    return -7;
  }
  return 3;
}

function tribe_setPassword($tag, $password, $db) {
  $query =
    "UPDATE Tribe ".
    "SET password = '$password' ".
    "WHERE tag LIKE '$tag'";

  if (!$db->query($query)) {
    return 0;
  }

  return 1;
}

function tribe_processChangePassword($tag, $password, $db) {
  return tribe_setPassword($tag, $password, $db) ? 0 : -7;
}

function tribe_getTagOfPlayerID($playerID, $db) {
  $qeury=
    "SELECT tribe ".
    "FROM Player ".
    "WHERE playerID = '$playerID' ";

  if (!($result=$db->query($query))) {
    return 0;
  }
  if (!($row = $result->nextRow())) {
    return 0;
  }
  return $row['tribe'];
}

function tribe_isLeader($playerID, $tribe, $db) {
  $query = 
    "SELECT name ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tribe' ".
    "AND leaderID = '$playerID'";

  if (!($result=$db->query($query))) {
    return 0;
  }
  if (!$result->nextRow()) {
    return 0;
  }
  return 1;
}

?>
