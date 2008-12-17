<?
  include "util.inc.php";

  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";

$config = new Config();
$db = new Db();

$query = "SELECT CONCAT(\"'\", p.name, \"'\"), r.rank, r.average, ".
   "CONCAT(\"'\", p.tribe, \"'\"), r.religion, count(*) as anzahl ".
   "FROM Cave c ".
   "RIGHT JOIN Player p ON p.playerID = c.playerID ".
   "LEFT JOIN Ranking r ON r.playerID = p.playerID GROUP BY p.playerID";

if (!($db_result = $db->query($query))){
  die("Fehler beim Auslesen.");
}

echo $db_result->numRows() . "\t" . time() . "\n";

while($row = $db_result->nextRow(MYSQL_ASSOC)){
  echo implode($row, "\t") . "\n";
}
?>
