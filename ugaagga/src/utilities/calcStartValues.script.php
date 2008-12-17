<?php
global $config;

include("util.inc.php");

echo "CALCULATING START VALUES...\n";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."startvalues.php";

$config = new Config();

if (!($db =
      new Db($config->DB_HOST, $config->DB_USER,
             $config->DB_PWD, $config->DB_NAME))) {
  echo "CALCSTARTVALUES: Failed to connect to game db.\n";
  exit(1);
}

$countStr = array();

foreach($start_values AS $id => $max) {
  $countStr[] = ($max ? "LEAST( " : "") .
		"SUM(".$id.") / COUNT(*) * ".STARTVALUES_AVERAGE_MULTIPLIER.
		($max ? ", $max )" : ""). " AS ".$id ;
}

$query = "SELECT ". implode($countStr, ", ") ." ".
         "FROM Cave ".
	 "WHERE playerID != 0 ";

if (!($r=$db->query($query)) || !($row = $r->nextRow(MYSQL_ASSOC))) {
  echo "CALCSTARTVALUES: Failed to count entities.\n";
  echo $query."\n";
  exit(1);
}

if (! $db->query("DELETE FROM StartValue") ) {
    echo "CALCSTARTVALUES: Failed to delete old values.\n";
    exit(1);
  }

foreach($row AS $field => $value) {
  $query = "INSERT INTO StartValue values('$field', '$value')";
  if (! $db->query($query) ) {
    echo "CALCSTARTVALUES: Failed to insert value.\n";
    exit(1);
  }
}

echo "FINISHED\n";

?>
