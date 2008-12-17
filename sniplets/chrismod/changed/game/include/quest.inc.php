<?php


function getActiveQuests($playerID, $db) {
  $query =
    "SELECT * ".
    "FROM quests_active ".
    "WHERE playerID = '$playerID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return ;
  }

  $activeQuests = array();
  while($row = $result->nextRow(MYSQL_ASSOC)) {
    array_push($activeQuests, $row);
  }

  return $activeQuests;
}


function getSucceededQuests($playerID, $db) {
  $query =
    "SELECT * ".
    "FROM quests_succeeded ".
    "WHERE playerID = '$playerID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return ;
  }

  $succeededQuests = array();
  while($row = $result->nextRow(MYSQL_ASSOC)) {
    array_push($succeededQuests, $row);
  }

  return $succeededQuests;
}


function getFailedQuests($playerID, $db) {
  $query =
    "SELECT * ".
    "FROM quests_failed ".
    "WHERE playerID = '$playerID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return ;
  }

  $failedQuests = array();
  while($row = $result->nextRow(MYSQL_ASSOC)) {
    array_push($failedQuests, $row);
  }

  return $failedQuests;
}


function getAbortedQuests($playerID, $db) {
  $query =
    "SELECT * ".
    "FROM quests_aborted ".
    "WHERE playerID = '$playerID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return ;
  }

  $abortedQuests = array();
  while($row = $result->nextRow(MYSQL_ASSOC)) {
    array_push($abortedQuests, $row);
  }

  return $abortedQuests;
}




function getQuestTitle($questID, $db) {
  $query =
    "SELECT title ".
    "FROM quests ".
    "WHERE questID = '$questID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty()) || ! ($row = $result->nextRow())) {
    return;
  }

  return $row[title];
}


function getQuestDescription($questID, $db) {
  $query =
    "SELECT description ".
    "FROM quests ".
    "WHERE questID = '$questID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty()) || ! ($row = $result->nextRow())) {
    return ;
  }

  return $row[description];
}


function getQuestToDo($questID, $db) {
  $query =
    "SELECT todo ".
    "FROM quests ".
    "WHERE questID = '$questID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty()) || ! ($row = $result->nextRow())) {
    return ;
  }

  return $row[todo];
}


function getQuestAbortMsg($questID, $db) {
  $query =
    "SELECT abort_msg ".
    "FROM quests ".
    "WHERE questID = '$questID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty()) || ! ($row = $result->nextRow())) {
    return ;
  }

$message = $row[abort_msg];

if (stristr($message, "%playername%")) {

// hm we need to get the player who has won the quest
  $query =
    "SELECT playerID ".
    "FROM quests_succeeded ".
    "WHERE questID = '$questID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty()) || ! ($row = $result->nextRow())) {
    echo "Database failure! Could not get player who won $questID";
    return;
  }

  if ($row[playerID] != 0) $playername = getPlayerFromID($row[playerID]);
    else $playername[name] = "Unbekannt";

  $message = str_replace ('%playername%', "<b>".$playername[name]."</b>", $message);

}

  return $message;
}





function questKnownByPlayer($questID, $playerID, $db) {

// a quest could either be active, failed, won or aborted
// if neither is the case the quest must be unknown to the player
// so we need to iterate through the quests

// Is the quest active?
  $query =
    "SELECT * ".
    "FROM quests_active ".
    "WHERE playerID = '$playerID' AND questID = '$questID' ";

  if (!($result = $db->query($query))) {
    echo "Database failure!";
  }
  if (!$result->isEmpty()) return TRUE;


// Is the quest won?
  $query =
    "SELECT * ".
    "FROM quests_succeeded ".
    "WHERE playerID = '$playerID' AND questID = '$questID' ";

  if (!($result = $db->query($query))) {
    echo "Database failure!";
  }
  if (!$result->isEmpty()) return TRUE;


// Is the quest failed?
  $query =
    "SELECT * ".
    "FROM quests_failed ".
    "WHERE playerID = '$playerID' AND questID = '$questID' ";

  if (!($result = $db->query($query))) {
    echo "Database failure!";
  }
  if (!$result->isEmpty()) return TRUE;


// Is the quest aborted?
  $query =
    "SELECT * ".
    "FROM quests_aborted ".
    "WHERE playerID = '$playerID' AND questID = '$questID' ";

  if (!($result = $db->query($query))) {
    echo "Database failure!";
  }
  if (!$result->isEmpty()) return TRUE;



return FALSE;
}



function questAbortedToPlayer($questID, $playerID, $db) {
  $query =
    "SELECT * ".
    "FROM quests_aborted ".
    "WHERE playerID = '$playerID' AND questID = '$questID' ";

  if (!($result = $db->query($query))) {
    echo "Database failure!";
  }

  if (!$result->isEmpty()) return TRUE;
    else return FALSE;

}




function isCaveQuestCave($targetCaveID, $db) {
  $query =
    "SELECT quest_cave ".
    "FROM cave ".
    "WHERE caveID = '$targetCaveID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty()) || ! ($row = $result->nextRow())) {
    echo "Database failure!";
    return;
  }

return $row[quest_cave];

}


function isCaveInvisibleToPlayer($targetCaveID, $playerID, $db) {

// Is the cave invisible to non quest players
  $query =
    "SELECT invisible_to_non_quest_players ".
    "FROM cave ".
    "WHERE caveID = '$targetCaveID' ";

  if (!($result = $db->query($query)) || ($result->isEmpty()) || ! ($row = $result->nextRow())) {
    echo "Database failure!";
    return TRUE;
  } else {
$res = $row[invisible_to_non_quest_players];


if (!$res) return FALSE;
}

// Is the player aware of the quest? If yes he can see the cave
  $query =
    "SELECT * ".
    "FROM quests_vis_to_player ".
    "WHERE caveID = '$targetCaveID' AND playerID = '$playerID'";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return TRUE;
  }





return FALSE;

}


?>
