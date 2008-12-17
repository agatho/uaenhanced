<?php 
  include "util.inc.php";

  global $config;
  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";

  if ($_SERVER['argc'] != 5) {
    echo "Usage: php createCave.php xMin xMax yMin yMax\n";
    exit(1);
  }

  $config = new Config();
  $db = new Db();  

  $query = "SELECT IF(ISNULL(max(caveID)), 0, max(caveID)) as maxCaveID FROM `Cave`";
  $db_result = $db->query($query);
  if (!$db_result) die("{$query}\nUnexpected db failure. Stopping. \n");
  
  $row = $db_result->nextrow(MYSQL_ASSOC);
  $caveID = $row['maxCaveID'] + 1;

  echo "Creating caves starting with caveID " . $caveID;

  for($i=$_SERVER['argv'][1]; $i < $_SERVER['argv'][2]; $i++){

    echo ".";
    for($j=$_SERVER['argv'][3]; $j < $_SERVER['argv'][4]; $j++){

      $query = "INSERT INTO Cave (caveID, xCoord, yCoord, name) VALUES ({$caveID}, {$i}, {$j}, '{$i}x{$j}')";
      if (!$db->query($query)){
        echo $query;
        echo "\nUnexpected db failure. Stopping. \n";
        exit(1);
      }
      $caveID++;            
    }
  }
  echo "\nCreated " . ($caveID - $row['maxCaveID'] - 1) . " caves.\n";
?>
