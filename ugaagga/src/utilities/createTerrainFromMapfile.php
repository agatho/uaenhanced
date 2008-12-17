<?
/*
.    * Wiese: +0.1 Nahrungsfaktor
T    * Wald: +0.1 Holzfaktor
M    * Gebirge: +0.1 Metallfaktor
~    * Sumpf: +0.1 Schwefelfaktor
:    * Geröllwüste: +0.1 Steinfaktor
*/

include "util.inc.php";

  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";
  include INC_DIR."game_rules.php";

$config = new Config();
$db = new Db();

// set memory limit
ini_set("memory_limit", "32M");

// check for right syntax
if ($argc != 3) die("USAGE:\n{$argv[0]} <mapfilename> starting_positions\n");

// load mapfile
$fp = fopen($argv[1], "r");
if ($fp === FALSE) die("could not open '{$argv[1]}'\n");

// parse mapfile
$terrain = array();
while (($c = fgetc($fp)) != FALSE){
  if ($c == "\n" || $c == "\r")
    continue;
  switch ($c){
    case '.':
            array_push($terrain, 0);	// Plains
            break;
    case 'T':
            array_push($terrain, 1);	// Forest
            break;
    case 'M':
            array_push($terrain, 2);	// Mountains
            break;
    case '~':
            array_push($terrain, 3);	// Swamp
            break;
    case ':':
            array_push($terrain, 4);	// Swamp
            break;
    default:
            die("unknown character: " . $c);
  }  
}
fclose($fp); 
  
// get size of map from db
$size   = getMapSize();
$width  = $size['maxX'] - $size['minX'] + 1;
$height = $size['maxY'] - $size['minY'] + 1;


// chunk the 1D vector thus making it 2D
$temp = sizeof($terrain);
$func = function_exists('array_chunk') ? "array_chunk" : "my_array_chunk";
$terrain = $func($terrain, $width);  

// echo some data about the parsed mapfile
printf("The specified mapfile '%s' contained a total of %d cells.\n".
       "It was chunked to a map of %dx%d which should be %d cells\n",
       $argv[1], $temp, $width, $height, $width*$height);

setTerrain($terrain, $size['minX'], $size['minY']);

setStarting_Positions($argv[2]);

/***************************************************************************/
/* FUNCTIONS                                                               */
/***************************************************************************/

function my_array_chunk($a, $s, $p=false){
  $r = Array();
  $ak = array_keys($a);
  $i = 0;
  $sc = 0;
  for ($x=0;$x<count($ak);$x++){
    if ($i == $s){
      $i = 0;
      $sc++;
    }
    $k = ($p) ? $ak[$x] : $i;
    $r[$sc][$k] = $a[$ak[$x]];
    $i++;
  }
  return $r;
}

function getMapSize(){
	global $db;

	if($res = $db->query("SELECT MIN(xCoord) as minX, MAX(xCoord) as maxX, MIN(yCoord) as minY, MAX(yCoord) as maxY FROM Cave")){
	  return $res->nextRow(MYSQL_ASSOC);
	}
	return 0;
}

function setTerrain($terrain, $offsetX, $offsetY){
  global $db; 
  echo "updating caves' terrain ";
  for ($y = 0; $y < sizeof($terrain); ++$y){
    for ($x = 0; $x < sizeof($terrain[0]); ++$x){
      $query = "UPDATE Cave SET terrain = {$terrain[$y][$x]} WHERE xCoord = " . ($x + $offsetX) . " AND yCoord = " . ($y + $offsetY);
      if (!$db->query($query)){
        echo "Fehler beim Eintragen des neuen Terrains!\n";
        return 1;
      }
    }
    echo ".";
  }  
  echo "\n";
}
function setStarting_Positions($limit){
  global $db; 
  echo "updating caves' Starting_positions ";
  $query = "UPDATE Cave SET starting_position = 1 ORDER BY RAND() LIMIT ".$limit;
  if (!$db->query($query)){
    echo "Fehler beim Eintragen der Starting_Positions!\n";
    return 1;
  }
  echo ".";
  echo "\n";
}

function stopwatch($start=false) {
  static $starttime;
  
  list($usec, $sec) = explode(" ", microtime());
  $mt = ((float)$usec + (float)$sec);

  if (!empty($start))
    return ($starttime = $mt);
  else
    return $mt - $starttime;
}

?>
