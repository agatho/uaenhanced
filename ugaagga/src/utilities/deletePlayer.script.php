<?php 
global $config;
include "util.inc.php";


include (INC_DIR."tribes.inc.php");
include INC_DIR."Player.php";
include INC_DIR."basic.lib.php";
include INC_DIR."time.inc.php";

#include INC_DIR."languages/de_DE.php";

if ($_SERVER['argc'] != 2) {
  echo "Usage: ".$_SERVER['argv'][0]." playerID\n";
  exit (1);
}

$playerID = $_SERVER['argv'][1];

echo "DELETE PLAYER $playerID: Starting...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";


$config = new Config();

if (!($db_login = 
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER, 
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "DELETE PLAYER $playerID: Failed to connect to login db.\n";
  exit(1);
}


if (!($r = $db_login->query("SELECT * FROM Login WHERE LoginID = '$playerID'"))
    || !$r->nextRow()) {  
  echo "DELETE PLAYER $playerID: No such Login\n";
  exit(1);
}

echo "DELETE PLAYER $playerID: Delete Login ";

if (!$db_login->query("DELETE FROM Login WHERE loginID = '$playerID' ")) {
  echo "FAILURE\n";
  exit(1);
}
echo "SUCCESS\n";

if (!($db_game = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "DELETE PLAYER $playerID: Failed to connect to game db.\n";
  exit(1);
}
$db = $db_game;

if ($tag = tribe_getTagOfPlayerID($playerID, $db_game)) {
  echo "DELETE PLAYER $playerID: Leave Player ";
  if (tribe_processLeave($playerID, $tag, $db_game, 1) != 2) {
    echo "FAILURE\n";
  }
  else {
    echo "SUCCESS\n";
  }
}

echo "DELETE PLAYER $playerID: Delete Player ";
if (!$db_game->query("DELETE FROM Player WHERE playerID = '$playerID' ")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Cave_takeover";
if (!$db_game->query("DELETE FROM Cave_takeover WHERE playerID = '$playerID' ")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Election";
if (!$db_game->query("DELETE FROM Election WHERE voterID = '$playerID' ")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Helden";
if (!$db_game->query("DELETE FROM Hero WHERE playerID = '$playerID' ")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}
echo "DELETE PLAYER $playerID: Delete Hero_tournament";
if (!$db_game->query("DELETE FROM Hero_tournament WHERE playerID = '$playerID' ")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}
echo "DELETE PLAYER $playerID: Delete Hero_Monster";
if (!$db_game->query("DELETE FROM Hero_Monster WHERE playerID = '$playerID' ")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}
echo "DELETE PLAYER $playerID: Delete Contacts";
if (!$db_game->query("DELETE FROM Contacts WHERE playerID = '$playerID' OR contactplayerID = '$playerID'")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}
echo "DELETE PLAYER $playerID: Delete CaveBookmarks";
if (!$db_game->query("DELETE FROM CaveBookmarks WHERE playerID = '$playerID'")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete caves...\n";
echo "DLELETE PLAYER $playerID: Retrieving caves ";
if (!($r=$db_game->query("SELECT caveID ".
			"FROM Cave WHERE playerID = '$playerID' "))) {
  echo "FAILURE\n";
  exit(1);
}
echo "SUCCESS\n";

while ($row = $r->nextRow()) {
  echo "DELETE PLAYER $playerID: Reset playerID at Cave {$row['caveID']}\n";
  if (!$db_game->query("UPDATE Cave SET playerID = 0, takeoverable = 2, protection_end = NOW()+0, secureCave=0 WHERE caveID = '{$row['caveID']}' ")) {
    echo "FAILURE\n";
  }
  else 
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete unit event ";
  if (!$db_game->query("DELETE FROM Event_unit ".
		      "WHERE caveID = '{$row['caveID']}' ")) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete improvement event ";
  if (!$db_game->query("DELETE FROM Event_expansion ".
		      "WHERE caveID = '{$row['caveID']}' ")) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete movement event ";
  if (!$db_game->query("DELETE FROM Event_movement ".
		      "WHERE caveID = '{$row['caveID']}' ")) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete science event ";
  if (!$db_game->query("DELETE FROM Event_science ".
		      "WHERE caveID = '{$row['caveID']}' ")) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

  echo "DELETE PLAYER $playerID: Delete defenseSystem event ";
  if (!$db_game->query("DELETE FROM Event_defenseSystem ".
		      "WHERE caveID = '{$row['caveID']}' ")) {
    echo "FAILURE\n";
  }
  else
    echo "SUCCESS\n";

}  

echo "DELETE PLAYER $playerID: Delete messages ";
if (!$db_game->query("UPDATE Message ".
                     "SET recipientDeleted = 1 ".
		     "WHERE recipientID = '$playerID' ") ||
    !$db_game->query("UPDATE Message ".
		     "SET senderDeleted = 1 ".
		     "WHERE senderID = '$playerID' ")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

echo "DELETE PLAYER $playerID: Delete Session ";
if (!$db_game->query("DELETE FROM `Session` WHERE playerID = '$playerID'")) {
  echo "FAILURE\n";
}
else {
  echo "SUCCESS\n";
}

?>
