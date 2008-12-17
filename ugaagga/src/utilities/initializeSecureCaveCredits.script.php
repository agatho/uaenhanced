<?php 
global $config;

include ("util.inc.php");

include (INC_DIR."db.inc.php");
include (INC_DIR."config.inc.php");

$config = new Config();

if (!($db_game = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "Failed to connect to game db.\n";
  exit(1);
}

$query =
  "SELECT COUNT(caveID) as count, playerID ".
  "FROM Cave ".
  "WHERE playerID != 0 ".
  "GROUP BY playerID";
  
if (!($r = $db_game->query($query))) {
  echo "Error: $query\n";
  exit;
}

if ( $r->isEmpty()) {
  echo "FAILED: no caves.\n";
  exit;
}

while($row = $r->nextRow()) {
  $query =
    "UPDATE Player ".
    "SET secureCaveCredits = 4-'".$row['count']."' ".
    "WHERE playerID = '{$row['playerID']}' ";

  if (!$db_game->query($query)) {
   echo "FAILED: to set player.\n";
   exit;
  }
  echo "SET: player {$row['playerID']} to 4-{$row['count']}\n";
}

?>
