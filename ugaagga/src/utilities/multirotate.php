<?php 
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."game_rules.php";
include INC_DIR."time.inc.php";
global $config;



echo "---------------------------------------------------------------------\n";
echo "- MULTI ROTATE  LOG FILE --------------------------------------------\n";
echo "  vom " . date("r") . "\n";



$config = new Config();

if (!($db_login = 
      new Db($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER, 
             $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME))) {
  echo "Rotate Multi : Failed to connect to login db.\n";
  exit(1);
}
if (!($db_game =
      new Db($config->DB_GAME_HOST, $config->DB_GAME_USER,
             $config->DB_GAME_PWD, $config->DB_GAME_NAME))) {
  echo "Rotate Multi : Failed to connect to game db.\n";
  exit(1);
}

//alte multies als gelöscht markieren
if (!($db_login->query("UPDATE Login SET deleted = 1 where multi = 66 AND lastChange < NOW() - INTERVAL 14 DAY"))) {  
  echo "Rotate Multi : Failed to mark old multis deleted.\n";
  exit(1);
}

//multi mit stati 65 in den stati 66 packen und in den stamm multi packen

$query = "SELECT LoginID,user FROM Login where multi = 65 and deleted = 0";
if(!$r=$db_login->query($query)){
  echo "Rotate Multi : Failed to get multis to rotate.\n";
  exit(1);
}
while($row = $r->nextRow()){
  echo "Verschiebe Spieler mit der ID ".$row["LoginID"].": ".$row["user"]."\n";

  $query = "SELECT tribe from Player where name = '".$row['user']."'";  
  if(!$r2=$db_game->query($query)){
    echo "Rotate Multi: Failed to get old tribe from Player\n";
    exit(1);
  }
  //Nachricht für den alten Stamm erzeugen
  if($row2 = $r2->nextRow()){
    if($row2['tribe'] != ""){
      $time = getUgaAggaTime(time());
      $month = getMonthName($time['month']);
      $query = "INSERT INTO TribeHistory (tribe, timestamp, ingameTime, message) VALUES ('".$row2['tribe']."', NOW(), '{$time['day']}. $month<br>im Jahr {$time['year']}', 'Spieler ".$row['user']." wurde in den Stamm Multi überführt')";
      if(!($db_game->query($query))){
        echo "Rotate Multi: Failed to update old tribehistory\n";
        exit(1);
      }
    }
  }
  //Player in Multistamm packen
  $query = "UPDATE Player set tribe = 'multi' WHERE name = '".$row['user']."'";
  if(!($db_game->query($query))){
    echo "Rotate Multi: Failed to update Player\n";
    exit(1);
  }
  //Ue-Hoehlen loeschen
  $query = "UPDATE Cave  SET playerID = 0 where secureCave = 0 AND playerID = '".$row['LoginID']."'";
  if(!($db_game->query($query))){
    echo "Rotate Multi: Failed to delete non secure caves\n";
    exit(1);
  }
  //Noobschutz weg
  $query = "UPDATE Cave  SET protection_end = 20070128222056 where playerID = '".$row['LoginID']."'";
  if(!($db_game->query($query))){
    echo "Rotate Multi: Failed to update protection_end\n";
    exit(1);
  }  
  //Login auf 66 stellen
  $query = "UPDATE Login SET multi = 66 where user = '".$row['user']."'";
  if(!($db_login->query($query))){
    echo "Rotate Multi: Failed to update Login\n";
    exit(1);
  }
} 
?>
