<?php 
global $config, $unitTypeList;

include "util.inc.php";

if ($_SERVER['argc'] != 1) {
  echo "Usage: ".$_SERVER['argv'][0]."\n";
  exit (1);
}

$halfgods = array("firak", "enzio", "nicknehm", "sirat", "slavomir", "kirkalot",
                  "gharlane", "paffi", "trubatsch");

echo "RUNNING HALFGOD STATS...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."formula_parser.inc.php";


$config = new Config();

if (!($db = 
      new Db($config->DB_HOST, $config->DB_USER, 
             $config->DB_PWD, $config->DB_NAME))) {
  echo "HALFGOD STATS: Failed to connect to game db.\n";
  exit(1);
}

foreach($halfgods AS $id => $god) {
  $query =
    "SELECT COUNT(playerID) AS n ".
    "FROM  Player ".
    "WHERE science_$god > 0";

  if (!($result = $db->query($query))) {
    echo "HALFGOD STATS halfgod $god: COUNT ";
    echo "FAILURE\n";
    exit(1);
  }
  if (!($row = $result->nextRow())) {
    echo "HALFGOD STATS halfgod $god: GET COUNT ";
    echo "FAILURE\n";
    exit(1);
  }
  echo "$god: {$row['n']}\n";
}

?>
