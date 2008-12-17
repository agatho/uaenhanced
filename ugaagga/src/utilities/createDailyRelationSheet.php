<?
  include "util.inc.php";

  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";

$config = new Config();
$db = new Db();

$query = "SELECT tribe, tribe_target, relationType ".
   "FROM Relation ";

if (!($db_result = $db->query($query))){
  die("Fehler beim Auslesen.\n");
}

while($row = $db_result->nextRow(MYSQL_ASSOC)){
  echo implode($row, "\t") . "\n";
}
?>
