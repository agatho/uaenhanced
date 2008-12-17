<?php 
global $config, $unitTypeList;

include "util.inc.php";

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

echo "RUNNING UNIT STATS...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."formula_parser.inc.php";


$config = new Config();

if (!($db = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "UNIT STATS: Failed to connect to game db.\n";
  exit(1);
}

foreach($unitTypeList AS $unitID => $unit) {
  $query =
    "SELECT SUM($unit->dbFieldName) AS n ".
    "FROM Cave ";

  if (!($result = $db->query($query))) {
    echo "UNIT STATS unitID $unitID: COUNT ";
    echo "FAILURE\n";
    echo $query."\n";
    exit(1);
  }
  if (!($row = $result->nextRow())) {
    echo "UNIT STATS unitID $unitID: GET COUNT ";
    echo "FAILURE\n";
    exit(1);
  }
  echo "$unitID ".$unit->dbFieldName." {$row['n']}\n";
}

?>
