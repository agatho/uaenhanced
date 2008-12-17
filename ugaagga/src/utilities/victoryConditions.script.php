<?php 
global $config, $unitTypeList;

include("util.inc.php");

include (INC_DIR."game_rules.php");

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

$today = date("d.m.Y");

echo "RUNNING UNIT STATS ON $today...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";


$config = new Config();

if (!($db = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "VICTORY CONDITIONS: Failed to connect to game db.\n";
  exit(1);
}

//////////////// Single Player Ranking ////////////////
// 1. Spieler mehr als doppelt so viele Punkte wie der 2. ?

$query =
  "SELECT playerID, name, average ".
  "FROM Ranking ".
  "ORDER BY average DESC ".
  "LIMIT 0,2";

if (!($result=$db->query($query)) || !($first=$result->nextRow()) || !($second=$result->nextRow()))
{
  echo "$today: TESTING SINGLE PLAYER DOMINATION FAILED\n";
}
else if ($first['average'] > $second['average'] * 2)
{
  echo "$today PLAYER DOMINATION: {$first['name']}({$first['playerID']}) {$first['average']}/{$second['average']}\n";
}

//////////////// Tribe Ranking Domination ////////////////
// 1. Stamm mehr Punkte als Stamm 2.-10. zusammen?

$query =
  "SELECT tribe, points ".
  "FROM RankingTribe ".
  "ORDER BY points DESC ".
  "LIMIT 0,10";

if (!($result=$db->query($query)) || !($first=$result->nextRow()))
{
  echo "$today: TESTING TRIBE DOMINATION FAILED\n";
}
else {
  while($row=$result->nextRow()) {
    $sum += $row['points'];
  }

  if ($first['points'] > $sum)
  {
    echo "$today TRIBE DOMINATION: {$first['tribe']} {$first['points']}/$sum\n";
  }
}


?>
