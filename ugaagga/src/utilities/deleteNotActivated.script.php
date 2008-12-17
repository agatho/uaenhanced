<?php
include "util.inc.php";

global $config;

$DELETE_SCRIPT = "deletePlayer.script.php";
$MAX_ACTIVATE_DURATION = 62 * 60 * 60;   // days * minutes * seconds

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

echo "DELETE NOT ACTIVATED: (".date("d.m.Y H:i:s",time()).") Starting...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";


$config = new Config();

if (!($db_login =
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER,
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "DELETE NOT ACTIVATED: Failed to connect to login db.\n";
  exit(1);
}


if (!($r = $db_login->query(
   "SELECT * ".
   "FROM Login ".
   "WHERE activated = 0 ".
   "AND creation < (NOW() - INTERVAL '$MAX_ACTIVATE_DURATION' SECOND) +0"))) {
  echo "DELETE NOT ACTIVATED: Couldn't retrieve logins\n";
  exit(1);
}

echo "DELETE NOT ACTIVATED: Delete players...\n";
$count = 0;

while ($row = $r->nextRow()) {
  echo "DELETE NOT ACTIVATEDPLAYER: Call $DELETE_SCRIPT\n";
  echo "for user: {$row['LoginID']}, {$row['user']}, {$row['email']}, ".
       "{$row['countResend']} resends, {$row['creation']} \n\n";

  system("\${PHP-php} $DELETE_SCRIPT {$row['LoginID']}");

  echo "\n\n";
  $count++;
}

echo "DELETE NOT ACTIVATED: Deleted $count users.\n";
echo "DELETE NOT ACTIVATED: Done.\n";

?>
