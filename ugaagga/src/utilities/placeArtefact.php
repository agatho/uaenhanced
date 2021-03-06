<?php
{
  include "util.inc.php";

  global $config;
  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";

  DEFINE("VOID_ID", 4);
  DEFINE("MAX_PROB", 0.5);

  if ($_SERVER['argc']!=3) {
    echo "Usage: placeArtefact.php artefactClassID number\n";
    exit(1);
  }

  $artefactClassID = $_SERVER['argv'][1];
  $number          = $_SERVER['argv'][2];
  
  $config = new Config();
  $db     = new Db();
/* alter Code aber nüich fütere Runden
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
*/
  for ($i=0; $i < $number; $i++) {
    do {  // look randomly for an existing cave
//      $x = (int)(rand() / (double)getRandMax() * ($maxX-$minX)) + $minX;
//      $y = (int)(rand() / (double)getRandMax() * ($maxY-$minY)) + $minY;

      $query =
	"SELECT caveID ".
	"FROM Cave ".
//	"WHERE TAKEOVERABLE = -99 ".
	"WHERE region in (1,3,7,8) ".
	"AND playerID = 0 ".
	"ORDER BY rand() LIMIT 1";
	/*"WHERE xCoord = $x AND yCoord = $y"; alter Code */
    } while (!($result = $db->query($query)) || !($row = $result->nextRow()));

    $caveID = $row['caveID'];
    
    $query =
      "INSERT INTO Artefact (artefactClassID, caveID) ".
      "values ($artefactClassID, $caveID)";
    if (! $db->query($query)) {
      echo "Couldn't create Artefact!\n";
      exit (1);
    }
    $query =
      "UPDATE Cave SET artefacts=artefacts+1 ".
      "WHERE caveID = $caveID";
    if (! $db->query($query)) {
      echo "Couldn't place Artefact into cave $caveID!\n";
      echo $query."\n";
      exit (1);
    }
    echo "Placed one artefact into Cave $caveID at $x : $y\n";
  }
}
?>









