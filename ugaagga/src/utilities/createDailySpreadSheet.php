<?
  include "util.inc.php";

  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";

$config = new Config();
$db = new Db();

$query = "SELECT c.caveID, c.xCoord, c.yCoord, CONCAT(\"'\", c.name, \"'\"), ".
   "c.terrain, CONCAT(\"'\", p.name, \"'\"), CONCAT(\"'\", p.tribe, \"'\"), r.rank ".
   "FROM Cave c ".
   "LEFT JOIN Player p ON p.playerID = c.playerID ".
   "LEFT JOIN Ranking r ON r.playerID = p.playerID";

if (!($db_result = $db->query($query))){
  die("Fehler beim Auslesen.\n");
}

while($row = $db_result->nextRow(MYSQL_ASSOC)){
  echo implode($row, "\t") . "\n";
}
?>
