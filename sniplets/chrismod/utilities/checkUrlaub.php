<?php
include "util.inc.php";

global $config;

#
# Since the original "one-query" version had performance problems,
# this version calls the "last login check" query for every activated
# user seperately.
#

function reset_urlaub($username){
  global $cfg;

  // get link to DB 'game'
  $db_game = new DB($cfg['DB_GAME']['HOST'], $cfg['DB_GAME']['USER'],
                    $cfg['DB_GAME']['PWD'], $cfg['DB_GAME']['NAME']);
  if (!$db_game) return FALSE;

  // playerID holen
  $query = "SELECT playerID FROM Player WHERE Name = '".$username."'";
  if (!($result=$db_game->query($query))) {echo $query."\r\n";return FALSE;}
  if ($result->isEmpty()) {echo $query."\r\n";return FALSE;}
  $game = $result->nextRow();
  $playerID = $game['playerID'];

  // Reset Player Table
  $query = "UPDATE Player SET urlaub = 0 WHERE playerID = ".$playerID;
  if (!$db_game->query($query)) {echo $query."\r\n";return FALSE;}

  // Reset Cave Table
  $query = "UPDATE Cave SET secureCave = secureCave_was WHERE playerID = ".$playerID;
  if (!$db_game->query($query)) {echo $query."\r\n";return FALSE;}
  $endtime = date("YmdHis", time());
  $query = "UPDATE Cave SET urlaub = 0, protection_end = ".$endtime." WHERE playerID = ".$playerID;
  if (!$db_game->query($query)) {echo $query."\r\n";return FALSE;}

  // get link to DB 'login'
  $db_login = new DB($cfg['DB_LOGIN']['HOST'], $cfg['DB_LOGIN']['USER'],
                    $cfg['DB_LOGIN']['PWD'], $cfg['DB_LOGIN']['NAME']);
  if (!$db_login) return FALSE;

  // Reset login table
  $query = "UPDATE login SET urlaub=0, urlaub_end=".time()." WHERE user='".$username."'";
  if (!$db_login->query($query)) {echo $query."\r\n";return FALSE;}

echo "Alles ok!\r\n";
return TRUE;
}

$MAX_URLAUB_DURATION = 30 * 24 * 60 * 60;   // days * hours * minutes * seconds
$NOW = time();

if ($_SERVER[argc] != 1) {
  echo "Usage: ".$_SERVER[argv][0]."\r\n";
  exit (1);
}

echo "CHECK URLAUB: (".date("d.m.Y H:i:s",time()).") Starting...\r\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$config = new Config();

if (!($db_login =
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER,
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "CHECK URLAUB: Failed to connect to login db.\r\n";
  exit(1);
}

$THETIME = $NOW-$MAX_URLAUB_DURATION;

if (!($r = $db_login->query(
   "SELECT * FROM Login WHERE urlaub = 1 AND urlaub_begin < ".$THETIME)))
{
  echo "CHECK URLAUB: Couldn't retrieve users\r\n";
  exit(1);
}


$count = 0;
while ($row = $r->nextRow()) {

    echo "RESETTING URLAUBSMODE: ";
    echo "for user: $row[LoginID], $row[user], $row[email], ".
       "$row[urlaub_begin] Urlaubsbeginn, $row[creation] \r\n";

    if(!reset_urlaub($row[user])) echo "Fehler beim reset. ".$user."\r\n";

    $count++;

    continue;                // check next player!
  }

echo "CHECK URLAUB: resetted $count users.\r\n";
echo "CHECK URALUB: Done.\r\n";

?>
