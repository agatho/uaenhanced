<?php
include "util.inc.php";

global $config;

#
# Since the original "one-query" version had performance problems,
# this version calls the "last login check" query for every activated
# user seperately.
#

function markDelete($playerID, $db) {
  return $db->query("UPDATE Login SET deleted=1 WHERE LoginID = '$playerID'");
}


$DELETE_SCRIPT = "deletePlayer.script.php";
$MAX_INACTIVE_DURATION = 5 * 24 * 60 * 60;   // days * hours * minutes * seconds

if ($_SERVER[argc] != 1) {
  echo "Usage: ".$_SERVER[argv][0]."\r\n";
  exit (1);
}

echo "DELETE INACTIVES: (".date("d.m.Y H:i:s",time()).") Starting...\r\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$config = new Config();

if (!($db_login =
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER,
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "DELETE INACTIVES: Failed to connect to login db.\r\n";
  exit(1);
}


/*
 * Find the following activated(!) players:
 * 1. All with the deleted mark set
 * 2. All, that have logged in, but longer than $MAX_INACTIVE_DURATION seconds ago
 * 3. All, that have never logged in, and have been created longer ago than $MAX_INAC... seconds ago
 */

echo "DELETE INACTIVES: Retrieve all user names\r\n";

// ADDED by chris--- for urlaub

if (!($r = $db_login->query(
   "SELECT user, LoginID, email, countResend, creation, deleted, urlaub, ".
   "(creation < (NOW() -INTERVAL $MAX_INACTIVE_DURATION SECOND)+0) AS creationOld ".
   "FROM Login ".
   "WHERE activated = 1 AND urlaub = 0")))
{
  echo "DELETE INACTIVES: Couldn't retrieve users\r\n";
  exit(1);
}

echo "DELETE INACTIVES: Checking all users for activity\r\n";

$count = 0;
while ($row = $r->nextRow()) {
#
# Check deletion flag.
#
  if ($row[deleted] == 1) {
    echo "DELETE INACTIVESPLAYER: Player has deleted himself. Call $DELETE_SCRIPT ";
    echo "for user: $row[LoginID], $row[user], $row[email], ".
       "$row[countResend] resends, $row[creation] \r\n";

   if (!markDelete($row[LoginID], $db_login)) {
	echo "Fehler markDelete";
   }

    system("php $DELETE_SCRIPT $row[LoginID]");

    $count++;

    continue;                // check next player!
  }

#
# Check if players has been active.
#
  if (!($rLogin = $db_login->query($query=       // get a login in the interval
     "SELECT ll.user ".
     "FROM LoginLog ll ".
     "WHERE ll.user = '$row[user]' ".
     "AND ll.success = 1 ".
     "AND ll.stamp > (NOW() - INTERVAL $MAX_INACTIVE_DURATION SECOND)+0")))

  {
    echo "\r\n$query\r\n ";
    echo "DELETE INACTIVES: Couldn't retrieve last login\r\n";
    exit(1);
  }


  if ($rLogin->numRows() == 0 && $row[creationOld] == 1) {    // not logged in and too old

    echo "DELETE INACTIVESPLAYER: Player has been inactive. Call $DELETE_SCRIPT ";
    echo "for user: $row[LoginID], $row[user], $row[email], ".
       "$row[countResend] resends, $row[creation] \r\n";

// ADDED by chris---: do not delete npcs
if ($row[LoginID] != 2 && $row[LoginID] != 3 && $row[LoginID] != 4 && $row[LoginID] != 5 && $row[LoginID] != 6 && $row[LoginID] != 7 && $row[LoginID] != 1) {

   if (!markDelete($row[LoginID], $db_login)) {
	echo "Fehler markDelete";
   }

    system("php $DELETE_SCRIPT $row[LoginID]");

    $count++;

} // end if
else {
  echo "Oh no, this is a npc!\r\n\r\n";
}
// -----------

  }
}

echo "DELETE INACTIVES: Deleted $count users.\r\n";
echo "DELETE INACTIVES: Done.\r\n";

?>
