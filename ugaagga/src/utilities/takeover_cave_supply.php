<?php
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."game_rules.php";

$config = new Config();
$db     = new Db();

$SUPPLYFACTOR = 3; // this factor means: how many people should compete for one cave


// first delete all takeoverable flags from caves taken over
$sql = 'UPDATE Cave SET takeoverable = 0 WHERE playerID != 0';
$db_cleanup = $db->query($sql);
if (!$db_cleanup){
  echo "Could not cleanup.\n";
  return -1;
}
echo "Cleanup: " . ( "0" + $db->affected_rows()) . " Flags gelöscht.\n";


// get demand
$sql = "SELECT COUNT(*) AS num_caves, takeover_max_caves " .
       "FROM Cave c " .
       "LEFT JOIN Player p ON p.playerID = c.playerID " .
       "WHERE c.playerID != 0 " .
       "GROUP BY c.playerID " .
       "HAVING num_caves < takeover_max_caves";
$db_demand = $db->query($sql);
if (!$db_demand){
  echo "Could not calculate demand.\n";
  return -1;
}
$demand = $db_demand->numRows();

// get supply
$sql = 'SELECT * FROM Cave c, Regions r WHERE c.takeoverable = 1 AND c.playerID = 0 AND c.regionID = r.regionID AND r.startRegion = 1';
$db_supply = $db->query($sql);
if (!$db_supply){
  echo "Could not calculate supply.\n";
  return -1;
}
$supply = $db_supply->numRows();


echo "Angebot:                    $supply\n".
     "Nachfrage:                  $demand\n";
$demand = (int)($demand / $SUPPLYFACTOR);
echo "zu befriedigende Nachfrage: $demand\n";

if ($supply < $demand){
  // supply to low, get more caves
  
  // how many more caves are needed:
  $diff = $demand - $supply;
  
  echo "Es fehlen noch $diff Höhlen!\n";
  
  // first get all the caves with the takeoverable = 2 (those are caves given up or freed by the deleteInactives script)
  $sql = "SELECT c.caveID FROM Cave c, Regions r WHERE c.playerID = 0 AND c.starting_position = 0 AND c.takeoverable = 2 AND c.regionID = r.regionID AND r.startRegion = 1 ORDER BY RAND() LIMIT " . ((int)$diff);
  $db_new_supply = $db->query($sql);
  if (!$db_new_supply){
    echo "Could not get new supply.\n";
    return -1;
  }
  
  $count_new_caves = 0;
  while($row = $db_new_supply->nextrow()){
    
    $sql = 'UPDATE Cave SET takeoverable = 1 WHERE playerID = 0 AND caveID = ' . $row['caveID'];
    $db_update = $db->query($sql);
    if (!$db_update){
      echo "Could not update.\n";
      return -1;
    } else {
      $count_new_caves++;
    }    
  }
  $diff -= $count_new_caves;
  // hmm, there was not enough given up caves to satisfy the demand,
  // get some of those wastes (takeoverable = 0 and playerID = 0)
  if ($diff > 0){
    $sql = "SELECT c.caveID FROM Cave c, Regions r WHERE c.playerID = 0 AND c.starting_position = 0 AND c.takeoverable = 0 AND c.regionID = r.regionID AND r.startRegion = 1 ORDER BY RAND() LIMIT " . ((int)$diff);
    $db_new_supply = $db->query($sql);
    if (!$db_new_supply){
      echo "Could not get new supply.\n";
      return -1;
    }
    
    while($row = $db_new_supply->nextrow()){
      
      $sql = 'UPDATE Cave SET takeoverable = 1 WHERE playerID = 0 AND caveID = ' . $row['caveID'];
      $db_update = $db->query($sql);
      if (!$db_update){
        echo "Could not update.\n";
        return -1;
      } else {
        $count_new_caves++;
      }    
    }
  }
  
  echo "Es wurden $count_new_caves weitere Höhlen freigegeben!\n";
  

} else {
  echo "Angebotsüberschuss: " . ($supply - $demand) . "\n";
}
