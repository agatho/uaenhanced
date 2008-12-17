<?php
  include "util.inc.php";

  global $config;
  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";

  DEFINE("VOID_ID", 4);
  DEFINE("MAX_PROB", 0.5);

  $config = new Config();
  $db     = new Db();

  srand ((double)microtime()*100000);

  $query=
    "SELECT MAX(xCoord) As maxX, MAX(yCoord) AS maxY, ".
    "MIN(xCoord) AS minX, MIN(yCoord) AS minY ".
    "FROM Cave ";

  if (!($result = $db->query($query)) || !($row = $result->nextRow())) {
    echo "Query failed:\n";
    echo $query;
    exit;
  }
  $maxX = $row['maxX'];
  $maxY = $row['maxY'];
  $minX = $row['minX'];
  $minY = $row['minY'];

  echo "Map size: $minX -> $maxX x $minY -> $maxY\n";

  $query=
    "SELECT xCoord, yCoord ".
    "FROM Cave ".
    "WHERE terrain = '".VOID_ID."'";

  if (!($result = $db->query($query))) {
    echo "Query failed:\n";
    echo $query;
    exit;
  }

  while($row = $result->nextRow()) {
    $caves[$row['xCoord']][$row['yCoord']] = 1;
  }

  $caves_new  = spreadVoid($caves, $minX, $minY, $maxX, $maxY);

  foreach($caves_new AS $x => $ar) {
    foreach($ar AS $y => $void) {
      if ($caves[$x][$y] != $void) {
        $caves_dif[$x][$y] = $void;
      }
    }
  }

  if ($caves_dif == 0) {
    echo "Void does not spread this cycle.";
    exit;
  }
  foreach($caves_dif AS $x => $a) {
    foreach($a AS $y => $void) {
      $query = 
	"UPDATE Cave ".
	"SET terrain = '".VOID_ID."' ".
	"WHERE xCoord = '$x' AND yCoord = '$y'";

      echo $query."\n";

      if (!($result = $db->query($query))) {
        echo "Query failed:\n";
        echo $query;
        exit;
      }
    }
  }

function spreadVoid($caves, $minX, $minY, $maxX, $maxY) {
  for ($x=$minX; $x <= $maxX; $x++) {
    for ($y=$minY; $y <= $maxY; $y++) {
      
      if ($caves[$x][$y] == 1) {
	$caves_new[$x][$y] = 1;
      }
      else {
	$count = countVoid($caves, $x, $y);

	// linearly increasing probability
	$prob = (MAX_PROB / 8.) * $count;

	if (rand() / (double)getRandMax() < $prob) {
	  $caves_new[$x][$y] = 1;
	}
      }
    }
  }
  return $caves_new;
}

function countVoid($caves, $x, $y) {
  for ($i=-1; $i <= 1; $i++) {
    for ($j=-1; $j <= 1; $j++) {
      if ($i != 0 || $j != 0) {
	$count += $caves[$x+$i][$y+$j];
      }
    }
  } 
  return $count;
}

function testSpreadVoid() {
  $caves[20][20] = 1;
  $caves[20][21] = 1;
  
  for ($i=1; $i < 5; $i++) {
    $caves_new = spreadVoid($caves, 0,0, 100, 100);

    echo "old: ";
    print_r ($caves);
    echo "new: ";
    print_r ($caves_new);
  
    $caves = $caves_new;
  }
}

?>




