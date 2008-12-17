<?php 
global $config;

include "util.inc.php";

include (INC_DIR."tribes.inc.php");


include (INC_DIR."config.inc.php");
include (INC_DIR."db.inc.php");


$config = new Config();

if (!($db_login = 
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER, 
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "DELETE PLAYER $playerID: Failed to connect to login db.\n";
  exit(1);
}


if (!($r = $db_login->query("SELECT LoginID, user FROM Login "))) {  
  echo "DELETE PLAYER $playerID: No such Logins\n";
  exit(1);
}

if (!($db_game = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "DELETE PLAYER $playerID: Failed to connect to game db.\n";
  exit(1);
}

while ($row = $r->nextRow()) {
$query =
    "SELECT * FROM Player ".
    "WHERE (playerID = '{$row['LoginID']}' ".
    "AND name NOT LIKE '{$row['user']}') OR ".
    "(playerID != '{$row['LoginID']}' AND name LIKE '{$row['user']}')";
  $r2 = $db_game->query($query);

  if (! $r2->isEmpty()) {
    echo "FAILED: {$row['user']}\n";

  }
  
$query =
    "SELECT * FROM Player ".
    "WHERE playerID = '{$row['LoginID']}' ".
    "AND name LIKE '{$row['user']}'";
  $r2 = $db_game->query($query);

  if ( $r2->isEmpty()) {
    echo "DELETE: {$row['user']} \n";
    $query =
      "DELETE FROM Login ".
      "WHERE loginID = '{$row['LoginID']}' ";
    if (!$db_login->query($query)) echo "FAILED!\n";
  }

}

?>
