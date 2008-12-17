<?
/*
 * tribes.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

// TODO: Wie sollen wir diese Datei I18n? Die Nachrichten gehen ja an den (potentiell gemischtsprachlichen) Stamm...

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

DEFINE("TRIBE_MESSAGE_WAR",      1);
DEFINE("TRIBE_MESSAGE_LEADER",   2);
DEFINE("TRIBE_MESSAGE_MEMBER",   3);
DEFINE("TRIBE_MESSAGE_RELATION", 4);
DEFINE("TRIBE_MESSAGE_INFO",    10);

function leaderDetermination_processChoiceUpdate($voterID, $playerID, $tribe, $db) {

  if ($playerID == 0) {
    if (! leaderDetermination_deleteChoiceForPlayer($voterID, $db)) {
      return -1;
    }
    return 1;
  }

  $player =new Player(getPlayerByID($playerID));

  if (!$player || strcasecmp ($player->tribe,$tribe)) {
    return -1;
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
  return $row['playerID'];
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
  if (!$oldGovernment['isChangeable']) {
    return -9;
  }

  if (!government_setGovernment($tag, $governmentData['governmentID'], $db)) {
    return -8;
  }

  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_LEADER,
       "Die Regierung wurde ge&auml;ndert",
       "Ihr Stammesanf&uuml;hrer hat die Regierung Ihres ".
       "Stammes auf ".
       $governmentList[$governmentData['governmentID']]['name'].
       " ge&auml;ndert.");

  return 4;
}

function relation_checkForRelationAttrib($tag_tribe1,$tag_tribe2,$attribArray,$db) {
  global $relationList;

  if (!is_array($attribArray)) {
    exit;
  }

  $relation = relation_getRelation($tag_tribe1,$tag_tribe2,$db);	
  $result = FALSE;
  foreach($attribArray as $attrib) {
    $result = ($relationList[$relation['own']['relationType']][$attrib]==1) &&	
              ($relationList[$relation['other']['relationType']][$attrib]==1); 
    if ($result) {
      break;
    }
  }
  return $result;    
}

function relation_areAllies($tag_tribe1,$tag_tribe2,$db) {
  $attribs = array();
  $attribs[] = 'isWarAlly';

  $res = relation_checkForRelationAttrib($tag_tribe1,$tag_tribe2,$attribs,$db);
  return $res;
}

function relation_areEnemys($tag_tribe1,$tag_tribe2,$db) {
  $attribs = array();
  $attribs[] = 'isWar';
//  $attribs[] = 'isPrepareForWar';
  $res = relation_checkForRelationAttrib($tag_tribe1,$tag_tribe2,$attribs,$db);	
  return $res;
}

function tribe_isAtWar($tag,$includePrepareForWar,$db) {
  global $relationList;

  $relations = relation_getRelationsForTribe($tag,$db);	
  $weAreAtWar = FALSE;
  foreach ($relations['own'] as $actRelation) { 	
    if ($relationList[$actRelation['relationType']]['isWar']) {
      $weAreAtWar = TRUE;
      break;     	
    };  
    if ($includePrepareForWar && ($relationList[$actRelation['relationType']]['isPrepareForWar'])) {
      $weAreAtWar = TRUE;
      break;
    };  
  }
  return $weAreAtWar;
}

function relation_haveSameEnemy($tag_tribe1,$tag_tribe2,$PrepareForWar,$War,$db) {
  global $relationList;
  // now we need the relations auf the two tribes
  $ownRelations = relation_getRelationsForTribe($tag_tribe1, $db);
  $targetRelations = relation_getRelationsForTribe($tag_tribe2, $db);

  foreach ($ownRelations['own'] as $actRelation) {
    foreach ($targetRelations['own'] as $actTargetRelation) {
      if (strcasecmp($actRelation['tribe_target'], $actTargetRelation['tribe_target']) == 0) {
  	    $ownType = $actRelation['relationType'];
  	    $targetType = $actTargetRelation['relationType'];

  	    $weHaveWar   = ($PrepareForWar && $relationList[$ownType]['isPrepareForWar']) ||
  	                   ($War && $relationList[$ownType]['isWar']);
  	    $theyHaveWar = ($PrepareForWar && $relationList[$targetType]['isPrepareForWar']) ||
  	                   ($War && $relationList[$targetType]['isWar']);

  	    if ($weHaveWar && $theyHaveWar) {
  	    	return TRUE;
  	    };	
      };
    };
  };
  return FALSE; 
}   

function tribe_isTopTribe($db,$tag) {
  $query =
    "SELECT rank".
    "FROM `RankingTribe`".
    "WHERE `tribe` = '$tag'".
    "LIMIT 0 , 30";

  if (!($result = $db->query($query))) {
    return false;
  }
  if (!($data = $result->nextRow(MYSQL_ASSOC))) {
    return false;
  }

  return $data['rank'] <= 10;
}


function relation_processRelationUpdate($tag, $relationData, $db, $FORCE = 0) {
  global $relationList;

  if (!$FORCE) { 
    if (strcasecmp($tag, $relationData['tag']) == 0) {
      return -7;
    }

    if (!($ownTribeInfo = tribe_getTribeByTag($tag, $db))) {
      return -6;
    }

    if (!($targetTribeInfo = tribe_getTribeByTag($relationData['tag'], $db))) {
      return -6;
    }

    if (!$ownTribeInfo['valid']) {
      return -17;
    }

    $relationType = $relationData['relationID'];
    $relationInfo = $relationList[$relationType];

    if (! ($relation = relation_getRelation($tag, $relationData['tag'], $db))) {
      return -3;
    }
    $relationTypeActual = $relation['own']['relationType'];

    if ( $relationTypeActual == $relationType ) { // change to actual relation?
      return -14;
    } 

    if (!$relation['own']['changeable']) {
      return -4;
    }

    // check if switching to same relation as target or relation is possible
    if ($relation['other']['relationType'] != $relationType &&
        ! relation_isPossible($relationType, //to
                              $relation['own']['relationType'])) {  //from 
      return -5;
    }

    $relationFrom = $relation['own']['relationType'];
    $relationTo   = $relationType;

    if (!$FORCE && ($relationList[$relationTo]['isWarAlly'])) {
      //generally allowes?
      if (! $relationList[$relationFrom]['isAlly']) 
        return -18;
      if (! $relationList[$relation['other']['relationType']]['isAlly']) 
        return -19;
      if (! relation_haveSameEnemy($ownTribeInfo['tag'],$targetTribeInfo['tag'],TRUE,FALSE,$db)) 
        return -20;
    };

    $relationTypeOtherActual = $relation['other']['relationType'];
    // check minimum size of target tribe if it´s not an ultimatum
    if ((($relationInfo['targetSizeDiffDown'] > 0) ||
         ($relationInfo['targetSizeDiffUp'] > 0)) &&
	 (!$relationList[$relationTypeOtherActual]['isUltimatum'])) {

      $from_points 	 = max(0, tribe_getMight($tag, $db));
      $target_points = max(0, tribe_getMight($relationData['tag'], $db));
    
      if (!tribe_isTopTribe($db,$relationData['tag'])) {
        if (($relationInfo['targetSizeDiffDown'] > 0) &&
            ($from_points - $relationInfo['targetSizeDiffDown'] > $target_points )) {
          return -12;
        }    	   
      } 

      if (!tribe_isTopTribe($db,$relationData['tag'])) {
        if (($relationInfo['targetSizeDiffUp'] > 0) &&
            ($from_points + $relationInfo['targetSizeDiffUp'] < $target_points )) {
          return -13;
        }    	   
      }
    }
  }
  // if switching to the same relation of other clan towards us,
  // use their treaty's end_time!
  if ($relationType == $relation['other']['relationType'] &&
      $relationType != 0) {
    $end_time = $relation['other']['duration'];
  }
  else {
    $duration =
      $relationList[$relationTypeActual]['transitions'][$relationType]['time'];
  }

  if ($relationList[$relationFrom]['isPrepareForWar'] && 
      $relationList[$relationTo]['isWar']) {
    $OurFame = $relation['own']['fame'];
    $OtherFame = $relation['other']['fame'];
  } else {
    $OurFame = 0;
    $OtherFame = 0;
  }    	     	

  if (!relation_setRelation($tag, $targetTribeInfo['tag'],
          $relationType,
          $duration, $db, $end_time,
          $relation['own']['tribe_rankingPoints'],
          $relation['own']['target_rankingPoints'],
	  $OurFame)) {
    return -3;
  }

  // calculate elo if war ended  
  if ($relationList[$relationType]['isWarWon']){
  	ranking_calculateElo($db, $tag, $relation['own']['tribe_rankingPoints'], $relationData['tag'], $relation['own']['target_rankingPoints']);
  }else if ($relationList[$relationType]['isWarLost']){
  	ranking_calculateElo($db, $relationData['tag'], $relation['own']['target_rankingPoints'], $tag, $relation['own']['tribe_rankingPoints']);
  }

  // insert history message
  if ($message = $relationList[$relationType]['historyMessage']) {
    relation_insertIntoHistory(
      $tag,
      relation_prepareHistoryMessage($tag,
             $targetTribeInfo['tag'],
             $message),
      $db);
  }

  $relationName = $relationList[$relationType]['name'];
  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_RELATION,
       "Haltung gegen&uuml;ber {$targetTribeInfo['tag']} ".
       "ge&auml;ndert",
       "Ihr Stammesanf&uuml;hrer hat die Haltung Ihres ".
       "Stammes gegen&uuml;ber dem Stamm ".
       "{$targetTribeInfo['tag']} auf $relationName ".
       "ge&auml;ndert.");

  tribe_sendTribeMessage($targetTribeInfo['tag'], TRIBE_MESSAGE_RELATION,
       "Der Stamm $tag &auml;ndert seine Haltung",
       "Der Stammesanf&uuml;hrer des Stammes $tag ".
       "hat die Haltung seines ".
       "Stammes ihnen gegen&uuml;ber ".
       "auf $relationName ".
       "ge&auml;ndert.");

  // switch other side if necessary (and not at this type already)
  if (!$end_time && ($oST = $relationInfo['otherSideTo']) >= 0) {
    if (!relation_setRelation($targetTribeInfo['tag'], $tag,
            $oST, $duration, $db, 0,
            $relation['other']['tribe_rankingPoints'],
            $relation['other']['target_rankingPoints'],
	    $OtherFame)) {
      return -3;
    }

    // insert history
    if ($message = $relationList[$oST]['historyMessage']) {
      relation_insertIntoHistory(
  $targetTribeInfo['tag'],
  relation_prepareHistoryMessage($tag,
               $targetTribeInfo['tag'],
               $message),
  $db);
    }


    $relationName = $relationList[$oST]['name'];
    tribe_sendTribeMessage($targetTribeInfo['tag'], TRIBE_MESSAGE_RELATION,
         "Haltung gegen&uuml;ber $tag ".
         "ge&auml;ndert",
         "Die Haltung Ihres ".
         "Stammes gegen&uuml;ber dem Stamm ".
         "$tag  wurde automatisch auf $relationName ".
         "ge&auml;ndert.");

    tribe_sendTribeMessage($tag, TRIBE_MESSAGE_RELATION,
         "Der Stamm {$targetTribeInfo['tag']} &auml;ndert ".
         "seine ".
         "Haltung",
         "Der Stamm {$targetTribeInfo['tag']} ".
         "hat die Haltung ihnen gegen&uuml;ber ".
         "automatisch auf $relationName ".
         "ge&auml;ndert.");
  }

  tribe_generateMapStylesheet();

  return 3;
}

function relation_leaveTribeAllowed($tag, $db) {

	global $relationList;

	$tribeRelations = relation_getRelationsForTribe($tag, $db);

  if (!$tribeRelations)
    return 0;

  foreach ($relationList as $relationTypeID => $relationType)
  	if ($relationType['dontLeaveTribe'])
  		foreach ($tribeRelations['own'] as $target => $relation)
  			if ($relation['relationType'] == $relationTypeID)
  				return 0;

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
  $month = getMonthName($time['month']);

  $query =
    "INSERT INTO TribeHistory ".
    "(tribe, ingameTime, message) ".
    "values ('$tribe', '{$time['day']}. $month<br>im Jahr {$time['year']}', ".
            "'$message')";
  return $db->query($query);
}

function relation_prepareHistoryMessage($tribe, $target, $message) {
  return str_replace("[TARGET]", $target,
         str_replace("[TRIBE]", $tribe, $message));
}

function tribe_validatePassword($password){
  return preg_match('/^\w{6,}$/', unhtmlentities($password));
}

function tribe_validateTag($tag){
  return preg_match('/^[a-zA-Z][a-zA-Z0-9\-]{0,7}$/', unhtmlentities($tag));
}

function tribe_SetTribeInvalid($tag, $db) {

  $tribeRelations = relation_getRelationsForTribe($tag, $db);
  if (!$tribeRelations)
    return 0;

  foreach ($tribeRelations['own'] as $target => $relation) {
    $relationData['tag']=$target;
    if ($relation['relationType']==2) {
      // 2 = Krieg => Kapi
      $relationData['relationID']=3;	
      relation_processRelationUpdate($tag,$relationData,$db,1);
    } 
    elseif ($relation['relationType']==3) {
      ;// 3 = kapi, hier machen wir NIX
    }
    else {
      // Alles andere stellen wir auf nix 
      $query = "DELETE FROM `Relation` ".
               "WHERE `relationID`=".$relation['relationID'];
      $db->query($query);
    }
  }

  $query = "UPDATE Tribe ".
           "SET valid = '0', ".
           "validatetime  = (NOW() + INTERVAL ".TRIBE_MINIMUM_LIVESPAN." SECOND) + 0 ".
           "WHERE tag = '$tag'";


  $result = $db->query($query);

  if ($result) {
    $result = tribe_sendTribeMessage($tag, TRIBE_MESSAGE_INFO,
              "Mitgliederzahl",
              "Ihr Stamm hat nicht mehr genug Mitglieder um Beziehungen eingehen ".
              "zu dürfen.");
  }
  return $result;
}

function tribe_SetTribeValid($tag, $db) {

  $query = "UPDATE `Tribe` ".
           "SET `valid` = '1' ".
           "WHERE `tag` = '$tag'";

  $result = $db->query($query);

  if ($result) {
    return tribe_sendTribeMessage($tag, TRIBE_MESSAGE_INFO,
           "Mitgliederzahl",
           "Ihr Stamm hat nun genug Mitglieder um Beziehungen eingehen ".
           "zu dürfen.");
  }
  return $result;
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
  return $row['points_rank'];
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
  return $row['points_rank'];
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
                              $end_time, $from_points_old, $target_points_old, $fame=0) {
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
      $relationList[$relation]['attackerReceivesFame']."', ".
      "defenderReceivesFame = '".
      $relationList[$relation]['defenderReceivesFame']."', ".
      "defenderMultiplicator = '".
      $relationList[$relation]['defenderMultiplicator']."', ".
      "attackerMultiplicator = '".
      $relationList[$relation]['attackerMultiplicator']."', ".
      ($end_time ?
       "duration = '$end_time' " :
       "duration = (NOW() + INTERVAL '$duration' HOUR) + 0 ").", ".
       "fame ='$fame'";
  }

  if (!$db->query($query)) {
    //echo $query;
    return 0;
  }

  // calculate the fame update if necessary
  if ($relationList[$relation]['fameUpdate'] != 0) {
    if ($relationList[$relation]['fameUpdate'] > 0) {
      $fame = relation_calcFame($from_points, $from_points_old,
                                $target_points, $target_points_old);
    }
    else if ($relationList[$relation]['fameUpdate'] < 0) {
      // calculate fame: first argument is winner!
      $fame = -1 * relation_calcFame($target_points, $target_points_old,
                                     $from_points, $from_points_old);
    }
    $query =
      "UPDATE Tribe ".
      "SET fame = fame + $fame ".
      "WHERE tag LIKE '$from'";

    if (!$db->query($query)) {
      //echo $query;
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
  return array_key_exists($to, $relationList[$from]['transitions']);
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
    $own[strtoupper($row['tribe_target'])] = $row;
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
    $other[strtoupper($row['tribe'])] = $row;
  }

  return array("own" => $own, "other" => $other);
}

function relation_getWarTargetsAndFame($tag, $db) {
  // returns an array of the current targets in war, the fame of both sides and both the actual percents and the estimated percents
  // content of the arrays are: target, fame_own, fame_target, percent_actual, percent_estimated, isForcedSurrenderTheoreticallyPossible, isForcedSurrenderPracticallyPossible, isForcedSurrenderPracticallyPossibleForTarget
  global $relationList;


  // first get the id of war
  $warId = 0;
  while( !($relationList[$warId]['isWar']) ){
    $warId++;
  }

  $prepareForWarId = 0;
//  while( !($relationList[$prepareForWarId]['isPrepareForWar']) ){
  while( !($relationList[$prepareForWarId]['isWar']) ){
    $prepareForWarId++;
  }


  $minTimeForForceSurrenderHours = $relationList[$warId]['minTimeForForceSurrenderHours'];
  $maxTimeForForceSurrenderHours = $relationList[$warId]['maxTimeForForceSurrenderHours'];

  // generate query for MySQL, get wars
  $query = 
    "SELECT r_target.tribe as target, ".
    "r_own.fame as fame_own, ".
    "r_target.fame as fame_target, ".
    "ROUND(((GREATEST(0, r_own.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame) + ( (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 ))) + (r_own.fame > r_target.fame AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= '$maxTimeForForceSurrenderHours' AND r_own.fame <= 0 AND r_target.fame <= 0)) * 100, 2) as percent_actual, ".
    "ROUND(GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp)) / 3600 - '$minTimeForForceSurrenderHours') / ( 2 * ('$maxTimeForForceSurrenderHours' - '$minTimeForForceSurrenderHours') )) * 100, 50) , 2) as percent_estimated, ".
    "((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= '$minTimeForForceSurrenderHours') as isForcedSurrenderTheoreticallyPossible, ".
    "((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= '$minTimeForForceSurrenderHours') AND ((GREATEST(0, r_own.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame) + ( (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 )) ) + (r_own.fame > r_target.fame AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= '$maxTimeForForceSurrenderHours' AND r_own.fame <= 0 AND r_target.fame <= 0) ) > GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 - '$minTimeForForceSurrenderHours') / ( 2 * ('$maxTimeForForceSurrenderHours' - '$minTimeForForceSurrenderHours') )), 0.5) as isForcedSurrenderPracticallyPossible, ".
    "((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= '$minTimeForForceSurrenderHours') AND ((GREATEST(0, r_target.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame) + ( (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 )) ) + (r_target.fame > r_own.fame AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 >= '$maxTimeForForceSurrenderHours' AND r_own.fame <= 0 AND r_target.fame <= 0) ) > GREATEST((1 - ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(r_own.timestamp))/3600 - '$minTimeForForceSurrenderHours') / ( 2 * ('$maxTimeForForceSurrenderHours' - '$minTimeForForceSurrenderHours') )), 0.5) as isForcedSurrenderPracticallyPossibleForTarget ".
    "FROM Relation r_own, Relation r_target ".
    "WHERE r_own.tribe LIKE r_target.tribe_target ".
    "AND r_target.tribe LIKE r_own.tribe_target ".
    "AND r_target.relationType = r_own.relationType ".
    "AND r_own.relationType = '$warId' ".
    "AND r_own.tribe LIKE '$tag' ".
    "ORDER BY r_own.timestamp ASC";

  $result = $db->query($query);

  // copy result into an array
  $warTargets = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    $warTargets[strtoupper($row['target'])] = $row;
  }

/*
  // generate query for MySQL, get prepare for wars
  $query = 
    "SELECT r_target.tribe as target, ".
    "r_own.fame as fame_own, ".
    "r_target.fame as fame_target, ".
    "ROUND((GREATEST(0, r_own.fame) / (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame) + ( (GREATEST(0, r_own.fame) + GREATEST(0, r_target.fame)) <= 0 ))) * 100, 2) as percent_actual, ".
    "ROUND(100 , 2) as percent_estimated, ".
    "0 as isForcedSurrenderTheoreticallyPossible, ".
    "0 as isForcedSurrenderPracticallyPossible, ".
    "0 as isForcedSurrenderPracticallyPossibleForTarget ".
    "FROM Relation r_own, Relation r_target ".
    "WHERE r_own.tribe LIKE r_target.tribe_target ".
    "AND r_target.tribe LIKE r_own.tribe_target ".
    "AND r_target.relationType = r_own.relationType ".
    "AND r_own.relationType = '$prepareForWarId' ".
    "AND r_own.tribe LIKE '$tag' ".
    "ORDER BY r_own.timestamp ASC";

  $result = $db->query($query);

  // copy result into an array
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    $warTargets[strtoupper($row['target'])] = $row;
  }
*/
  return $warTargets;
}

function relation_forceSurrender($tag, $relationData, $db){
  global $relationList;
  // check conditions

  if(!$relationData){
    return -3;
  }

  if (strcasecmp($tag, $relationData['tag']) == 0) {
    return -7;
  }

  if (!($ownTribeInfo = tribe_getTribeByTag($tag, $db))) {
    return -6;
  }

  if (!($targetTribeInfo = tribe_getTribeByTag($relationData['tag'], $db))) {
    return -6;
  }

  $target = $relationData['tag'];
  $tribeWarTargets = relation_getWarTargetsAndFame($tag, $db);

  if(!($relation = $tribeWarTargets[strtoupper($target)]))
    return -3; 

  if(!$relation['isForcedSurrenderPracticallyPossible'])
    return -25;

  // find surrender
  $surrenderId = 0;
  while( !($relationList[$surrenderId]['isWarLost']) ){
    $surrenderId++;
  }

  // tribe messages for forced surrender
  tribe_sendTribeMessage($ownTribeInfo['tag'], TRIBE_MESSAGE_RELATION,
       "Zwangskapitulation &uuml;ber $target", 
       "Ihr Stammesanf&uuml;hrer hat den Stamm $target zur Aufgabe gezwungen.");

  tribe_sendTribeMessage($targetTribeInfo['tag'], TRIBE_MESSAGE_RELATION,
       "Zwangskapitulation gegen $tag", 
       "Der Stammesanf&uuml;hrer des Stammes $tag hat ihren Stamm zur Aufgabe gezwungen.");

  $relationDataLooser = array('tag' => $tag,
                              'relationID' => $surrenderId);

  // refresh relations                              
  return relation_processRelationUpdate($target, $relationDataLooser, $db);
}

function tribe_processAdminUpdate($leaderID, $tag, $data, $db) {
  // list of fields, that should be inserted into the player record

  if (!tribe_isLeaderOrJuniorLeader($leaderID, $tag, $db)) {
    return 2;
  }

  if (!tribe_validatePassword($data["password"])){
  	return 2;
  }

  $fields =
    array("name", "password", "description");


  // first update data
  $data['description'] = nl2br($data['description']);

  if ($set = db_makeSetStatementSecure($data, $fields)) {
    if (!$db->query(
      "UPDATE Tribe ".
      "SET ".$set." ".
      "WHERE tag = '$tag'"))
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
    "SELECT *, ".
    "(validatetime < NOW() + 0) AS ValidationTimeOver ".
    "FROM Tribe ";

  $db_all_tribes = $db->query($query);
  if (!$db_all_tribes){
    return -1;
  }

  $tribes = array();
  while ($row = $db_all_tribes->nextRow(MYSQL_ASSOC)) {
    $tribes[$row['tag']] = $row;
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

  // GOD ALLY is not deletable
  if (!strcmp($tag, GOD_ALLY))
    return 0;

  $query =
    "SELECT * ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tag' ".
    "AND validatetime  < (NOW() - INTERVAL ".TRIBE_MINIMUM_LIVESPAN." SECOND) + 0";


//Nebrot: relations are deleted in tribes.php 
// if relationList instantiatetd, check Relations
//  if ($relationList && ! relation_leaveTribeAllowed($tag, $db) ) {
//    return 0;
//  }

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
    return $row_count['members'];
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
    "WHERE tag LIKE '$tag' ";

  if (! $db->query($query) || ! $db->affected_Rows() > 0) {
    return 0;
  }
  return 1;
}

function tribe_makeJuniorLeader($playerID, $tag, $db) {
  $query =
    "UPDATE Tribe ".
    "SET juniorLeaderID = '$playerID' ".
    "WHERE tag LIKE '$tag' ";

  if (! $db->query($query) || ! $db->affected_Rows() > 0) {
    return 0;
  }
  return 1;
}

function tribe_unmakeJuniorLeader($playerID, $tag, $db) {
  $query =
    "UPDATE Tribe ".
    "SET juniorLeaderID = 0 ".
    "WHERE tag LIKE '$tag' ";

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

function tribe_joinTribe($playerID, $tag, $db) {
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
    "SET tribe = '' ".
    //"fame = fame - ".TRIBE_LEAVE_FAME_COST." ".
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

function ranking_sort($db){
  $query = 
    "SELECT rankingID FROM RankingTribe ORDER BY points_rank DESC, -1*(1+playerAverage)";
  $db_rank = $db->query($query);
  if(!$db_rank){
    return 0;
  }
  $count = 1;
  while($row = $db_rank->nextrow()){
    $query = "UPDATE RankingTribe SET rank = ".$count++." WHERE rankingID = ".$row['rankingID'];
    if(!$db->query($query)){
      return 0;
    }
  }
}

function tribe_restoreOldRanking($tag, $pw, $db) {
  $query =
    "SELECT * FROM `OldTribes` ".
    "WHERE `tag` LIKE '$tag' AND `password`='$pw' AND `used`=0 ".
    "LIMIT 1";
  $result = $db->query($query);
  if (!$result) return 0;
  $row = $result->nextRow();
  if (!$row) return 1; // bail out if no tribe is found, but with positive return value
  $query =
    "UPDATE `RankingTribe` ".
    "SET `points_rank`={$row['points_rank']} ".
    "WHERE `tribe` LIKE '$tag'";
  if (!$db->query($query)) return 0;
  return 1;
}

function tribe_removeTribeFromOldRanking($tag, $db) {
  $query =
    "UPDATE `OldTribes` ".
    "SET `used` = 1 ".
    "WHERE `tag`='$tag'";
  if (!$db->query($query)) return 0;
  return 1;
}

function tribe_createRanking($tag, $db){
  $query = 
    "INSERT INTO RankingTribe ".
    "(tribe, rank, points_rank) ".
    "VALUES ('$tag', 0, 1500)";
  if(! $db->query($query)){
    return 0;
  }
  return 1;
}

function tribe_createTribe($tag, $name, $leaderID, $db) {
  $query =
    "INSERT INTO Tribe ".
    "(tag, name, leaderID, created, governmentID, validatetime, valid) ".
    "values ('$tag', '$name', 0, NOW() + 0, 1,((NOW() + INTERVAL ".TRIBE_MINIMUM_LIVESPAN." SECOND ) + 0),0)";

  if (! $db->query($query)) {
    return 0;
  }

  if(!tribe_createRanking($tag, $db)){
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
  global
    $relationList;

  if (! $FORCE && ! relation_leaveTribeAllowed($tag, $db) ) {
    return 0;
  }
  if (!($tribe = tribe_getTribeByTag($tag, $db))) {
    return 0;
  }

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

  // get relations
  if (!($tribeRelations = relation_getRelationsForTribe($tag, $db))) {
    return 0;
  }
  // end others relations
  foreach ($tribeRelations['other'] AS $otherTag => $relation){
    $relationType = $relationList[$relation['relationType']];
    $oDST = $relationType['onDeletionSwitchTo'];
    if ($oDST >= 0){

      // die raltion umschalten und zielrelation temporär eintragen; sie wird
      // am ende dieser funktion ohne weiteres umschalten geloescht. Das
      // temporaere umschalten ist aber noetig, um zum beispiel die
      // ruhmberechnung im siegfall oder aehnliche effekte, die an
      // relation_setRelation haengen abzuarbeiten.

      if (!relation_setRelation($otherTag, $tag, $oDST, 0, $db, 0,
                                $relation['tribe_rankingPoints'],
                                $relation['target_rankingPoints']))
        return 0;

      // insert history
      if ($message = $relationList[$oDST]['historyMessage']){
        relation_insertIntoHistory($otherTag,
          relation_prepareHistoryMessage($tag, $otherTag, $message), $db);
      }
      // insert tribe message
      $relationName = $relationList[$oDST]['name'];
      tribe_sendTribeMessage($otherTag, TRIBE_MESSAGE_RELATION,
        "Haltung gegen&uuml;ber $tag ge&auml;ndert",
        "Die Haltung Ihres Stammes gegen&uuml;ber dem Stamm $tag  wurde ".
        "automatisch auf $relationName ge&auml;ndert.");
    }
  }

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

  if ($tribe['leaderID'] &&
      ! tribe_unmakeLeader($tribe['leaderID'], $tag, $db))
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

    if (! tribe_setBlockingPeriodPlayerID($playerID, $db))
    {
    	return 0;
    }

    messages_sendSystemMessage($playerID,
                               8,
            "Aufl&ouml;sung des Stammes",
            "Ihr Stamm $tag wurde soeben aufgel&ouml;st. ".
            "Sollten Sie Probleme mit dem ".
            "Stammesmen&uuml; haben, loggen Sie sich ".
            "bitte neu ein.",
            $db);

    Player::addHistoryEntry($db, $playerID,
                            sprintf(_('verläßt den Stamm \'%s\''), $tag));
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

  $query =
    "SELECT rank ".
    "FROM RankingTribe ".
    "WHERE tribe LIKE '$tag'";

  if (!($result=$db->query($query))) {
    return 0;
  }

  if (!($row = $result->nextRow())) {
    return 0;
  }
  $rank = $row['rank'];

  $query = 
    "DELETE FROM RankingTribe ".
    "WHERE tribe LIKE '$tag'";
  if (!$db->query($query)) {
      return 0;
  }

  $query = 
    "UPDATE RankingTribe SET rank = rank - 1 ".
    "WHERE rank > '$rank'";
  if (!$db->query($query)) {
      return 0;
  }

  Player::addHistoryEntry($db, $tribe['leaderID'],
                          sprintf(_('löst den Stamm \'%s\' auf'), $tag));

  return 1;
}

function tribe_recalcLeader($tag, $oldLeaderID, $oldJuniorLeaderID, $db) {
  global
    $governmentList;

  // find the new leader

  if(!($government =
       government_getGovernmentForTribe($tag, $db))) {
    return -1;
  }

  $det = $governmentList[$government['governmentID']]['leaderDeterminationID'];

  switch ($det) {
    case 1:
      $newLeadership = tribe_recalcLeader1($tag, $db);
      break;
    case 2:
      $newLeadership = tribe_recalcLeader2($tag, $db);
      if ($newLeadership[0]==$oldLeaderID) {
        $newLeadership[1]=$oldJuniorLeaderID;
      }

      break;
  }
  if (!is_array($newLeadership)) {
    return $newLeadership;
  }
  //wihthout a Leader also  no JuniorLeader
  if ($newLeadership[0] == 0) {
    $newLeadership[1] =0; 
  }
  // change the leader
  return tribe_ChangeLeader($tag, $newLeadership, $oldLeaderID, $oldJuniorLeaderID , $db);
}

function tribe_ChangeLeader($tag, $newLeadership, $oldLeaderID, $oldJuniorLeaderID, $db) {
  if (($newLeadership[0] == $oldLeaderID) && ($newLeadership[1] == $oldJuniorLeaderID)) {
    return 0;  //nothing changed
  }

  if ($newLeadership[0] <> $oldLeaderID) {
    if ($oldLeaderID && !tribe_unmakeLeader($oldLeaderID, $tag, $db))
	  {
	    return -2;
	  }
	  if ($newLeadership[0] && !tribe_makeLeader($newLeadership[0], $tag, $db))
	  {
	    return -3;
    }
  }  

  if ($newLeadership[1] <> $oldJuniorLeaderID) {
    if ($oldJuniorLeaderID && !tribe_unmakeJuniorLeader($oldJuniorLeaderID, $tag, $db))
	  {
	    return -4;
	  }
	  if ($newLeadership[1] && !tribe_makeJuniorLeader($newLeadership[1], $tag, $db))
	  {
	    return -5;
    }
  }  

  tribe_SendMessageLeaderChanged($tag, $newLeadership);

  return $newLeadership;
}


/**
 * Send Message for Tribe Leadership change
 */
function tribe_SendMessageLeaderChanged($tag, $newLeadership) {
  if (!$newLeadership[0]) {
    tribe_sendTribeMessage($tag,
         TRIBE_MESSAGE_LEADER,
         "Stammesf&uuml;hrung",
         "Ihr Stamm hat momentan keinen Anf&uuml;hrer ".
         "mehr");
  }
  $player = getPlayerByID($newLeadership[0]);
  $newLeadershipName = $player ? $player['name'] : $newLeadership[0];
  if ($newLeadership[0] && !$newLeadership[1]) {
    tribe_sendTribeMessage($tag,
         TRIBE_MESSAGE_LEADER,
         "Stammesf&uuml;hrung",
         "Ihr Stamm hat eine neue Stammesf&uuml;hrung:<br>".
         "Stammesanf&uuml;hrer: ".$newLeadershipName."<br>".
         "Stellvertreter:  <i>nicht vorhanden</i>");
  }
  $player = getPlayerByID($newLeadership[1]);
  $newJuniorLeaderName = $player ? $player['name'] : $newLeadership[1];
  if ($newLeadership[0] && $newLeadership[1]) {
    tribe_sendTribeMessage($tag,
         TRIBE_MESSAGE_LEADER,
         "Stammesf&uuml;hrung",
         "Ihr Stamm hat eine neue Stammesf&uuml;hrung:<br>".
         "Stammesanf&uuml;hrer: ".$newLeadershipName."<br>".
         "Stellvertreter:  ".$newJuniorLeaderName);
  }     
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
    "LIMIT 0, 2";

  if (!($mysql_result = $db->query($query)))
  {
    return -1;
  }

  $result = array();
  $result[0]=0;
  $result[1]=0;
  $i=0;
  while ($row = $mysql_result->nextRow()) {
    $result[$i]=$row['playerID'];
    $i+=1;	
  }
  return $result;
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
    return array( 0 => 0, 1 => 0); // no leader!
  }
  if ($row['votes'] <=  tribe_getNumberOfMembers($tag, $db) / 2)
  {          // more than 50% ?
    return array( 0 => 0, 1 => 0); // no leader!
  }

  return array( 0 => $row['playerID'], 1 => 0);
}


function tribe_processJoin($playerID, $tag, $password, $db) {

  if (! tribe_changeTribeAllowedForPlayerID($playerID, $db)) {
    return -11;
  }
  if (! relation_leaveTribeAllowed($tag, $db) ) {
    return -12;
  }

  $query =
    "SELECT tag ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tag' ".
    "AND password = '$password' ";

  if (!($result=$db->query($query))) {
    return -1;
  }

  if (!($row = $result->nextRow())) {
    return -2;
  }

  $tag = $row['tag'];

  if (!($player=getPlayerByID($playerID))) {
    return -3;
  }
  if (!tribe_joinTribe($playerID, $tag, $db)) {
    return -3;
  }

  tribe_setBlockingPeriodPlayerID($playerID, $db);

  Player::addHistoryEntry($db, $playerID,
                          sprintf(_('tritt dem Stamm \'%s\' bei'), $tag));

  tribe_sendTribeMessage($tag,
       TRIBE_MESSAGE_MEMBER,
       "Spielerbeitritt",
       "Der Spieler {$player['name']} ist soeben dem ".
       "Stamm beigetreten.");

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
  return $row['blocked'] != 1;
}


function tribe_processLeave($playerID, $tag, $db, $FORCE = 0) {
  if (! $FORCE && ! relation_leaveTribeAllowed($tag, $db)) {
    return -10;
  }

  if (! $FORCE && ! tribe_changeTribeAllowedForPlayerID($playerID, $db)) {
    return -11;
  }

  if (tribe_isLeaderOrJuniorLeader($playerID, $tag, $db)) {
     if (tribe_isLeader($playerID, $tag, $db)) {
       if (! $FORCE && !tribe_unmakeLeader($playerID, $tag, $db)) {
         return -8;
       }   
     } else {
       if (! $FORCE && !tribe_unmakeJuniorLeader($playerID, $tag, $db)) {
        return -8;
       }
     }  
  }

  if (!($player=getPlayerByID($playerID))) {
    return -4;
  }
  if (!tribe_leaveTribe($playerID, $tag, $db)) {
    return -4;
  }

  Player::addHistoryEntry($db, $playerID,
                          sprintf(_('verläßt den Stamm \'%s\''), $tag));

  tribe_setBlockingPeriodPlayerID($playerID, $db);

  tribe_sendTribeMessage($tag,
       TRIBE_MESSAGE_MEMBER,
       "Spieleraustritt",
       "Der Spieler {$player['name']} ist soeben aus dem ".
       "Stamm ausgetreten.");

  if (tribe_getNumberOfMembers($tag, $db) == 0) {  // tribe has to be deleted
    tribe_deleteTribe($tag, $db, $FORCE);
    return 4;
  }

  return 2;
}

function tribe_processKickMember($playerID, $tag, $db) {

  // leader must not be kicked
  if (tribe_isLeaderOrJuniorLeader($playerID, $tag, $db))
    return -2;

  // do not kick in wartime
  if (!relation_leaveTribeAllowed($tag, $db))
    return -15;

  // blocked
  if (!tribe_changeTribeAllowedForPlayerID($playerID, $db))
    return -16;

  // get player
  $player = getPlayerByID($playerID);

  // no such player
  if (!$player)
    return -1;

  // remove player
  if (!tribe_leaveTribe($playerID, $tag, $db))
    return -1;

  Player::addHistoryEntry($db, $playerID,
                          sprintf(_('wird aus dem Stamm \'%s\' geworfen'), $tag));

  // block player
  tribe_setBlockingPeriodPlayerID($playerID, $db);

  tribe_sendTribeMessage($tag, TRIBE_MESSAGE_MEMBER, "Spieler rausgeschmissen",
    "Der Spieler {$player['name']} wurde soeben vom Anführer aus dem Stamm ".
    "ausgeschlossen.");

  messages_sendSystemMessage($playerID, 8, "Clanausschluss.",
    "Sie wurden aus dem Clan $tag ausgeschlossen. Bitte loggen Sie sich aus ".
    "und melden Sie sich wieder an, damit das Stammesmenü bei Ihnen wieder ".
    "richtig funktioniert.", $db);

  return 1;
}

function tribe_processSendTribeIngameMessage($leaderID, $tag, $message, $db) {
  if (!tribe_isLeaderOrJuniorLeader($leaderID, $tag, $db)) {
    return -9;
  }

  $message = nl2br($message);

  // get alle members
  $query = "SELECT p.name AS name FROM Player p ".
           "WHERE p.tribe LIKE '$tag'";

  if(!$members = $db->query($query)) {
    return -9;
  }

  while ($member = $members->nextRow(MYSQL_ASSOC)) {
    if(!messages_insertMessageIntoDB($member['name'], "Nachricht vom Stammesanf&uuml;hrer", $message)) {
      return -9;
    }
  }

  return 5;
}

function tribe_processSendTribeMessage($leaderID, $tag, $message, $db) {

  if (!tribe_isLeaderOrJuniorLeader($leaderID, $tag, $db)) {
    return -9;
  }

  if (!tribe_sendTribeMessage($tag,
                              TRIBE_MESSAGE_LEADER,
                              "Nachricht vom Stammesanf&uuml;hrer",
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
    $messages[$row['tribeMessageID']] = $row;
  }
  return $messages;
}

function tribe_processCreate($leaderID,
           $tag,
           $password,
           $db,
           $restore_rank = false)
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
  if ($restore_rank) {
    if (!tribe_restoreOldRanking($tag, $password, $db))
    {
      return -1;
    }
  }
  if (!tribe_removeTribeFromOldRanking($tag, $db))
  {
    return -1;
  }

  ranking_sort($db);
  
  if (!tribe_setPassword($tag, $password, $db)) {
    return -7;
  }

  Player::addHistoryEntry($db, $leaderID, sprintf(_('gründet den Stamm \'%s\''),
                          $tag));

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
  $query=
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

function tribe_getLeaderID($tribe, $db) {
  $query =
    "SELECT leaderID ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tribe' ";

  if (!($result=$db->query($query))) {
    return 0;
  }
  if (!$row = $result->nextRow()) {
    return 0;
  }
  return $row['leaderID'];
}

function tribe_getJuniorLeaderID($tribe, $db) {
  $query =
    "SELECT juniorLeaderID ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tribe' ";

  if (!($result=$db->query($query))) {
    return 0;
  }
  if (!$row = $result->nextRow()) {
    return 0;
  }
  return $row['juniorLeaderID'];
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

function tribe_isLeaderOrJuniorLeader($playerID, $tribe, $db) {
  $query =
    "SELECT name ".
    "FROM Tribe ".
    "WHERE tag LIKE '$tribe' ".
    "AND". 
    "(leaderID = '$playerID'".
    "OR juniorLeaderID = '$playerID')";

  if (!($result=$db->query($query))) {
    return 0;
  }
  if (!$result->nextRow()) {
    return 0;
  }
  return 1;
}

function tribe_generateMapStylesheet() {
  global $db, $params;

  if ($params->SESSION->player->tribe == '')
    return;

  $outfilename = "./images/temp/tribe_".$params->SESSION->player->tribe.".css";
  $outfile     = @fopen($outfilename, "wb");

  if (!$outfile)
    die("Could not create file!");

  $query = "SELECT * ".
           "FROM `Relation` ".
           "WHERE `tribe` = '".$params->SESSION->player->tribe."' ".
           "OR `tribe_target` =  '".$params->SESSION->player->tribe."';";

  $result = $db->query($query);

  fwrite($outfile, "a.t_".$params->SESSION->player->tribe." {\n");
  fwrite($outfile, "  width: 100%;\n");
  fwrite($outfile, "  border-top: 2px solid darkgreen;\n");
  fwrite($outfile, "}\n\n");

  if ($result) {
    while ($row = $result->nextRow(MYSQL_ASSOC)) {
      fwrite($outfile, "a.t_".($row['tribe'] == $params->SESSION->player->tribe ? $row['tribe_target'] : $row['tribe'])." {\n");
      fwrite($outfile, "  width: 100%;\n");
      fwrite($outfile, "  border-top: ");
      switch ($row['relationType']) {
        case 0:  // keine
          fwrite($outfile, "0px solid transparent");
          break;
        case 1:  // Ulti
          fwrite($outfile, "2px dotted red");
          break;
        case 2:  // Krieg
          fwrite($outfile, "2px solid red");
          break;
        case 3:  // Kapitulation
          fwrite($outfile, "0px solid transparent");
          break;
        case 4:  // Besatzung
          fwrite($outfile, "0px solid transparent");
          break;
        case 5:  // Waffenstillstand
          fwrite($outfile, "2px dashed blue");
          break;
        case 6:  // NAP
          fwrite($outfile, "2px solid blue");
          break;
        case 7:  // Bündnis
          fwrite($outfile, "2px solid green");
          break;
      }
      fwrite($outfile, "\n}\n\n");
    }
  }
  fclose($outfile);
}

function relation_deleteRelations($tag, $db) {

  $query =
    "DELETE FROM Relation ".
    "WHERE tribe LIKE '$tag' ".
    "OR tribe_target LIKE '$tag'";

  return $db->query($query);
}
function ranking_calculateElo($db, $winnertag, $winnerpoints, $losertag, $loserpoints){
  // get actual points
  $winnerpoints_actual = tribe_getMight($winnertag, $db);
  $loserpoints_actual = tribe_getMight($losertag, $db);
  $faktor = 10;
  
  //k faktor bestimmen
  echo($winnertag. " ". $winnerpoints." ". $loser." ". $loserpoints);
  $k = 10;
  if($winnerpoints < 2400){
    $query = 
      "SELECT calculateTime from RankingTribe where tribe like '$winnertag'";
    $res = $db->query($query);
    if(!$res)
      return 0;
    $res = $res->nextRow(MYSQL_ASSOC);
    if($res['calculateTime'] > 30)
      $k = 15;
    else
      $k = 25;
  }
  $eloneu = $winnerpoints_actual + $k * $faktor * (1 - (1/(1+pow(10, ($loserpoints - $winnerpoints)/400))));
  $query = 
    "UPDATE RankingTribe SET points_rank=".(int)$eloneu.", calculateTime = calculateTime+1 WHERE tribe like '$winnertag'";
  if(!$db->query($query))
    return 0;
  $k = 10;
  if($loserpoints < 2400){
    $query =
      "SELECT calculateTime from RankingTribe where tribe like '$losertag'";
    $res = $db->query($query);
    if(!$res)
      return 0;
    $res = $res->nextRow(MYSQL_ASSOC);
    if($res['calculateTime'] > 30)
      $k = 15;
    else
      $k = 25;
  }
  $eloneu = $loserpoints_actual + $k * $faktor * (0 - (1/(1+pow(10, ($winnerpoints - $loserpoints)/400))));
  $query =
    "UPDATE RankingTribe SET points_rank=".(int)$eloneu.", calculateTime = calculateTime+1 WHERE tribe like '$losertag'";
  if(!$db->query($query))
    return 0;
 
  return ranking_sort($db);  
}
?>
