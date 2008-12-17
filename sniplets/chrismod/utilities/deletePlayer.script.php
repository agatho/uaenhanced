<?php 
global $config;
include "util.inc.php";


include (INC_DIR."tribes.inc.php");

if ($_SERVER[argc] != 2) {
  echo "Usage: ".$_SERVER[argv][0]." playerID\r\n";
  exit (1);
}

$playerID = $_SERVER[argv][1];

echo "DELETE PLAYER $playerID: Starting...\r\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";


$config = new Config();

if (!($db_login = 
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER, 
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "DELETE PLAYER $playerID: Failed to connect to login db.\r\n";
  exit(1);
}


if (!($r = $db_login->query("SELECT * FROM Login WHERE LoginID = '$playerID'"))
    || !$r->nextRow()) {  
  echo "DELETE PLAYER $playerID: No such Login\r\n";
  exit(1);
}

echo "DELETE PLAYER $playerID: Delete Login ";

if (!$db_login->query("DELETE FROM Login WHERE loginID = '$playerID' ")) {
  echo "FAILURE\r\n";
  exit(1);
}
echo "SUCCESS\r\n";


if (!($db_game = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "DELETE PLAYER $playerID: Failed to connect to game db.\r\n";
  exit(1);
}

if ($tag = tribe_getTagOfPlayerID($playerID, $db_game)) {
  echo "DELETE PLAYER $playerID: Leave Player ";
  if (tribe_processLeave($playerID, $tag, $db_game, 1) != 2) {
    echo "FAILURE\r\n";
  }
  else {
    echo "SUCCESS\r\n";
  }
}

echo "DELETE PLAYER $playerID: Delete Player ";
if (!$db_game->query("DELETE FROM Player WHERE playerID = '$playerID' ")) {
  echo "FAILURE\r\n";
}
else {
  echo "SUCCESS\r\n";
}

echo "DELETE PLAYER $playerID: Delete Cave_takeover";
if (!$db_game->query("DELETE FROM Cave_takeover WHERE playerID = '$playerID' ")) {
  echo "FAILURE\r\n";
}
else {
  echo "SUCCESS\r\n";
}

echo "DELETE PLAYER $playerID: Delete Election";
if (!$db_game->query("DELETE FROM Election WHERE voterID = '$playerID' ")) {
  echo "FAILURE\r\n";
}
else {
  echo "SUCCESS\r\n";
}

echo "DELETE PLAYER $playerID: Delete Helden";
if (!$db_game->query("DELETE FROM Helden WHERE playerID = '$playerID' ")) {
  echo "FAILURE\r\n";
}
else {
  echo "SUCCESS\r\n";
}
echo "DELETE PLAYER $playerID: Delete HeldenTurnier";
if (!$db_game->query("DELETE FROM HeldenTurnier WHERE playerID = '$playerID' ")) {
  echo "FAILURE\r\n";
}
else {
  echo "SUCCESS\r\n";
}


echo "DELETE PLAYER $playerID: Delete caves...\r\n";
echo "DLELETE PLAYER $playerID: Retrieving caves ";
if (!($r=$db_game->query("SELECT caveID ".
			"FROM Cave WHERE playerID = '$playerID' "))) {
  echo "FAILURE\r\n";
  exit(1);
}
echo "SUCCESS\r\n";

while ($row = $r->nextRow()) {
  echo "DELETE PLAYER $playerID: Reset playerID at Cave $row[caveID]\r\n";
  if (!$db_game->query("UPDATE Cave SET playerID = 0, takeoverable = 2, protection_end = NOW()+0, secureCave=0 WHERE caveID = '$row[caveID]' ")) {
    echo "FAILURE\r\n";
  }
  else 
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete unit event ";
  if (!$db_game->query("DELETE FROM Event_unit ".
		      "WHERE caveID = '$row[caveID]' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete improvement event ";
  if (!$db_game->query("DELETE FROM Event_expansion ".
		      "WHERE caveID = '$row[caveID]' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete movement event ";
  if (!$db_game->query("DELETE FROM Event_movement ".
		      "WHERE caveID = '$row[caveID]' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete science event ";
  if (!$db_game->query("DELETE FROM Event_science ".
		      "WHERE caveID = '$row[caveID]' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete defenseSystem event ";
  if (!$db_game->query("DELETE FROM Event_defenseSystem ".
		      "WHERE caveID = '$row[caveID]' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

}  

echo "DELETE PLAYER $playerID: Delete messages ";
if (!$db_game->query("UPDATE Message ".
                     "SET recipientDeleted = 1 ".
		     "WHERE recipientID = '$playerID' ") ||
    !$db_game->query("UPDATE Message ".
		     "SET senderDeleted = 1 ".
		     "WHERE senderID = '$playerID' ")) {
  echo "FAILURE\r\n";
}
else {
  echo "SUCCESS\r\n";
}

// ADDED by chris--- for Quests
  echo "DELETE PLAYER $playerID: Delete quests_active ";
  if (!$db_game->query("DELETE FROM quests_active ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete quests_failed ";
  if (!$db_game->query("DELETE FROM quests_failed ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete quests_aborted ";
  if (!$db_game->query("DELETE FROM quests_aborted ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

// We should not delete this cause we might need this
// Instead we set the player to 0 which means unknown
  echo "DELETE PLAYER $playerID: Delete quests_succeeded ";
  if (!$db_game->query("UPDATE quests_succeeded SET playerID = 0 ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

  echo "DELETE PLAYER $playerID: Delete quests_vis_to_player ";
  if (!$db_game->query("DELETE FROM quests_vis_to_player ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

// ADDED by chris--- for rank_history:
  echo "DELETE PLAYER $playerID: Delete rank_history ";
  if (!$db_game->query("DELETE FROM rank_history ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

// ADDED by chris--- for addressbook:
  echo "DELETE PLAYER $playerID: Delete addressbook ";
  if (!$db_game->query("DELETE FROM adressbook ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

// ADDED by chris--- for cavebook:
  echo "DELETE PLAYER $playerID: Delete cavebook ";
  if (!$db_game->query("DELETE FROM cavebook ".
		      "WHERE playerID = '$playerID' ")) {
    echo "FAILURE\r\n";
  }
  else
    echo "SUCCESS\r\n";

?>
