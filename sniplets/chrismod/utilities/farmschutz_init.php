<?PHP

// This script initializes the farmschutz for players already playing.
// You just need to run this script once
// You need to alter the player table:
/*
ALTER TABLE `player` ADD `farmschutz` INT UNSIGNED DEFAULT '90' NOT NULL AFTER `template` ;
*/

echo "FARMSCHUTZ INIT: (".date("d.m.Y H:i:s",time()).") Starting...\r\n";


$percent = 1.25; // -percent per every 6th hour


define("INC_DIR", "../game/include/");

if ($_SERVER[argc] != 1) {
  echo "Usage: ".$_SERVER[argv][0]."\n";
  exit (1);
}

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$config = new Config();


if (!($db = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "FARMSCHUTZ INIT: Failed to connect to game db.\r\n";
  exit(1);
}


// get db login
if (!($db_login =
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER,
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "PASSWORD CHECK: Failed to connect to login db.\r\n";
  exit(1);
}

// ------------------------------------------------



// get LoginID, user and creation from Login

$r = $db_login->query("SELECT LoginID, user, creation FROM login");
if (!$r)
{
  echo "FARMSCHUTZ INIT: Couldn't retrieve users\r\n";
  exit(1);
}

$a = 0;
while ($row = $r->nextRow()) {
  $creation[$a] = $row[creation];
  $user[$a] = $row[user];
  $id[$a] = $row[LoginID];

  $a++;
}


// get game db
if (!($db = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "FARMSCHUTZ INIT: Failed to connect to game db.\r\n";
  exit(1);
}



for ($a=0;$a<count($id);$a++){

  echo "UserID: ".$id[$a].", ".$user[$a].", ".$creation[$a].": ";

// converting creation stamp to unix time

$untime = mktime(substr($creation[$a],8,2),substr($creation[$a],10,2),substr($creation[$a],12,2),substr($creation[$a],4,2),substr($creation[$a],6,2),substr($creation[$a],0,4));

$now = time();

// calc time since creation

$diff = $now - $untime;

// we want hours

$diff = round($diff / (60*60));

echo $diff." hours since creation, ";

// :6 cause every 6 hours the value is decreased

$diff = floor($diff/6);

echo $diff." rounds, ";

$diff = $diff * $percent;

$diff = 90-$diff;

if ($diff < 10) $diff = 10;
if ($diff > 90) $diff = 90;

echo $diff." percent\r\n";


// Updating player table

echo "updating player table: ";

$query = "UPDATE player SET farmschutz = ".$diff." WHERE playerID = ".$id[$a];

  if (!($db->query($query))) {
    echo "FAILURE\r\n";
    echo $query."\r\n\r\n";
  } else {
    echo "SUCCESS!\r\n\r\n";
  }



} // end for


echo "finished.";






?>