<?php
  include "util.inc.php";

  global $config;
  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";

  ini_set("memory_limit", "32M");
    
  $config = new Config();
  $db     = new Db();

  $names = swapshuffle(createNames());
  
  $query = "SELECT COUNT(*) AS num_caves FROM Cave GROUP BY NULL";
  
  $db_result = $db->query($query);
  if (!$db_result || $db_result->isEmpty()){
    echo "Fehler bei der Abfrage der Anzahl der Höhlen. (1.a.)\n";
    return -1;
  }
  $row = $db_result->nextrow(MYSQL_ASSOC);
  $num_caves = $row['num_caves'];

  if ($num_caves > sizeof($names)){
    echo "Zu wenig Namen für alle Höhlen. (2.a.)\n";
    return -2;
  }
  
  // hier wird davon ausgegangen, dass die Höhlen mit 1 beginnend fortlaufend durchnummeriert sind.
  for ($i = 0; $i < $num_caves; ++$i){
    $query = "UPDATE Cave SET name = '" . $names[$i] . "' WHERE caveID = " . ($i + 1);
    $db_result = $db->query($query);
    if (!$db_result || ($db->affected_rows() != 1)){
      echo "Fehler beim Ändern des Höhlennamen: (" . ($i + 1) . ") " . $names[$i] . ". (3.a.)\n";
      echo mysql_errno() . ": " . mysql_error() . "\n";
      return -3;
    }
  }


  //
  // Liest aus einer Datei beliebiger Größe Strings ein und macht daraus eine Matrix
  // Die Datei muss folgendes Format haben:
  //   #1\n
  //   string\n
  //   #2\n
  //   string\n
  
  function createNames($dateiname = "namen.txt"){
  
    $datei = fopen($dateiname,"r");
    
    $switch = "-";
    
    $namen_a = array();
    $namen_b = array();
    
    $namenkombi = array();
    
    $xa = 0;
    $xb = 0;
    while (!feof($datei)){
      $zeile = fgets($datei, 1024);
     
      if (substr($zeile, 0, 2) == "#1"){
        $switch = "a";    
      } else if (substr($zeile, 0, 2) == "#2"){
        $switch = "b";    
      } else {
       
        $zeile_neu = substr($zeile, 0, strlen($zeile) - 1);
        
        if ($switch == "a"){
          array_push($namen_a, $zeile_neu);
        } else if ($switch == "b"){
          array_push($namen_b, $zeile_neu);
        }
      }
    }
    fclose($datei);
    
    echo "Vorsilben: " . sizeof($namen_a);
    $namen_a = array_unique($namen_a);
    echo " (" . sizeof($namen_a) . ")\n";

    echo "Nachsilben: " . sizeof($namen_b);
    $namen_b = array_unique($namen_b);
    echo " (" . sizeof($namen_b) . ")\n";
    
    
    for ($i = 0; $i < sizeof($namen_a); ++$i)
      for ($j = 0; $j < sizeof($namen_b); ++$j)
        array_push($namenkombi, htmlentities($namen_a[$i] . $namen_b[$j]));
    
    return $namenkombi;
  }
  
  function swapshuffle($array) {
    srand ((double) microtime() * 10000000);
    for ($i = 0; $i < sizeof($array); $i++) {
      $from=rand(0, sizeof($array) - 1);
      $old = $array[$i];
      $array[$i] = $array[$from];
      $array[$from] = $old;
    }  
    return $array;
  }
?>
