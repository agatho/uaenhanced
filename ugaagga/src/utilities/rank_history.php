<?PHP

// This script stores a history of the raniking points


// create a table in game db:
/*
CREATE TABLE `Rank_history` (
`playerID` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL ,
`name` VARCHAR( 90 ) ,
`day_1` INT( 11 ) UNSIGNED NOT NULL ,
`day_2` INT( 11 ) UNSIGNED NOT NULL ,
`day_3` INT( 11 ) UNSIGNED NOT NULL ,
`day_4` INT( 11 ) UNSIGNED NOT NULL ,
`day_5` INT( 11 ) UNSIGNED NOT NULL ,
`day_6` INT( 11 ) UNSIGNED NOT NULL ,
`day_7` INT( 11 ) UNSIGNED NOT NULL ,
`day_8` INT( 11 ) UNSIGNED NOT NULL ,
`day_9` INT( 11 ) UNSIGNED NOT NULL ,
`day_10` INT( 11 ) UNSIGNED NOT NULL ,
`curr_day` INT( 1 ) UNSIGNED NOT NULL ,
UNIQUE (
`playerID` 
)
);
*/


echo "RANK HISTORY: (".date("d.m.Y H:i:s",time()).") Starting...\r\n";
define("_VALID_UA",1); 

// some config

$maxDays = 10;

define("INC_DIR", "/usr/local/ugaagga/include/");

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
  echo "RANK HISTORY: Failed to connect to game db.\r\n";
  exit(1);
}

// ------------------------------------------------


echo "Getting current day...\r\n";

$query = "SELECT curr_day FROM Rank_history LIMIT 0, 1";

  if (!($result = $db->query($query))) {
    echo "RANK HISTORY ";
    echo "FAILURE\r\n";
    echo $query."\r\n";
    exit(1);
  }
  if (($row = $result->nextRow())) {
    $curr_day = $row[curr_day];
  } else {
    // table empty
    $curr_day = 0;
  }




// increasing the day

$curr_day++;

if ($curr_day > $maxDays) $curr_day = 1;

echo "day is: ".$curr_day."\r\n";

// Updating the table

$query = "UPDATE Rank_history SET curr_day = ".$curr_day;

  if (!($db->query($query))) {
    echo "RANK HISTORY ";
    echo "FAILURE\r\n";
    echo $query."\r\n";
    exit(1);
  }

// Getting averages from ranking

// getting last playerID

$query = "SELECT playerID AS anzahl FROM Ranking ORDER BY playerID DESC LIMIT 0, 1";

  if (!($result = $db->query($query))) {
    echo "RANK HISTORY ";
    echo "FAILURE\r\n";
    echo $query."\r\n";
    exit(1);
  }
  if (!($row = $result->nextRow())) {
    echo "RANK HISTORY: GET ROW ";
    echo "FAILURE\r\n";
    exit(1);
  }

$anzahl = $row[anzahl];

$points = array();

$query = "SELECT playerID, name, average FROM Ranking";

  if (!($result = $db->query($query))) {
    echo "RANK HISTORY ";
    echo "FAILURE\r\n";
    echo $query."\r\n";
    exit(1);
  }

for ($i=1;$i<=$anzahl;$i++) {

  if ($row = $result->nextRow()) {
    $playerID = $row[playerID];
    echo "Getting average from playerID: ".$playerID.", ";

    $points[$playerID][name] = $row[name];
    $points[$playerID][average] = $row[average];

    echo $points[$playerID][name].", ";
    echo $points[$playerID][average]."\r\n";

  }

}

// updating the table

for ($i=1;$i<=$anzahl;$i++) {

  if ($points[$i][name]) {

    echo "Updating Rank_history for playerID: ".$i."\r\n";

    $query = "SELECT playerID FROM Rank_history WHERE playerID = ".$i." LIMIT 0, 1";

    if (!($result = $db->query($query))) {
      echo "RANK HISTORY ";
      echo "FAILURE\r\n";
      echo $query."\r\n";
      exit(1);
    }
    if (!($row = $result->nextRow())) {
      // no entry, we need to insert

      $query = "INSERT INTO Rank_history SET playerID = ".$i.", name = '".$points[$i][name]."', day_".$curr_day." = ".$points[$i][average].", curr_day = ".$curr_day;
      if (!($db->query($query))) {
        echo "RANK HISTORY ";
        echo "FAILURE\r\n";
        echo $query."\r\n";
        exit(1);
      }
    } else {
      // entry found, update the table

      $query = "UPDATE Rank_history SET day_".$curr_day." = ".$points[$i][average].", curr_day = ".$curr_day." WHERE playerID = ".$i;
      if (!($db->query($query))) {
        echo "RANK HISTORY ";
        echo "FAILURE\r\n";
        echo $query."\r\n";
        exit(1);
      }
    }

  } // end if

} // end for


echo "finished...\r\n\r\n";

?>
