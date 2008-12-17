<?
/** minimap.png.php
 *  erzeugt eine Minimap mit einer Markierung für den aktuellen Standpunkt
 *  Eingangsparameter:
 *
 *  x    : xKoordinate des aktuellen Standpunkts
 *  y    : yKoordinate des aktuellen Standpunkts
 *  minX :
 *  maxX :
 *  minY :
 *  maxY :
 */

require_once("../include/config.inc.php");
require_once("../include/db.inc.php");
require_once("../include/params.inc.php");

define("TERRAIN_MAP", "terrain_map.png");
define("MAP_TIMEOUT", 60*60);
//define("MAP_TIMEOUT", 11*60);


$config = new Config();
$db     = new Db();
$post   = new POST();

// which coordinate should be marked
$x    = (int) $post->x;
$y    = (int) $post->y;

// get map size
$size = getMapSize();

if($size == 0)
  return false;

$minX = $size['minX'];
$maxX = $size['maxX'];
$minY = $size['minY'];
$maxY = $size['maxY'];

$width  = $maxX - $minX + 1;
$height = $maxY - $minY + 1;

// make coordinate valid
$x = min($maxX, max($minX, $x));
$y = min($maxY, max($minY, $y));


// get map file's lifetime if existent
$lifetime = -1;

if (file_exists(TERRAIN_MAP)){
  $lifetime = time() - filemtime(TERRAIN_MAP);
}

// get map file's size
if ($lifetime == -1 || $lifetime >= MAP_TIMEOUT){
  $status = createTerrainMap($db);
  if ($status != TRUE) die("could not create map file.");
}
$minimap = loadPNG(TERRAIN_MAP);

// check correct map size
if (imagesx($minimap) != $width || imagesy($minimap) != $height){
  $status = createTerrainMap($db);
  if ($status != TRUE) die("could not create map file.");
}
$minimap = loadPNG(TERRAIN_MAP);


// show minimap
header("Content-type: image/png");
imagearc($minimap, $x - $minX, $y - $minY, 2, 2, 0, 360,imagecolorallocate ($minimap, 0xFF, 0x00, 0x00));
imagepng($minimap);
imagedestroy($minimap);


/* ***** FUNCTIONS ***** */

function getMapSize(){
	global $db;

	if($res = $db->query("SELECT MIN(xCoord) as minX, MAX(xCoord) as maxX, MIN(yCoord) as minY, MAX(yCoord) as maxY FROM Cave")){
	  return $res->nextRow(MYSQL_ASSOC);
	}
	return 0;
}

function createTerrainMap($db){

  unlink(TERRAIN_MAP);
  $size = getMapSize();
  
  if($size == 0)
    return false;
  
  $terrainMap = ImageCreate($size['maxX'] - $size['minX'] + 1, $size['maxY'] - $size['minY'] + 1);
  $terrain_colour = array(ImageColorAllocate($terrainMap, 0xF0, 0xF0, 0xC0),
                          ImageColorAllocate($terrainMap, 0xC0, 0xE2, 0x9A),
                          ImageColorAllocate($terrainMap, 0xE0, 0xC0, 0x98),
                          ImageColorAllocate($terrainMap, 0xC0, 0xC0, 0xA4),
                          ImageColorAllocate($terrainMap, 0x99, 0x99, 0x55),  // color of darkness
                          ImageColorAllocate($terrainMap, 0x3A, 0x11, 0x3E));

  $query = "SELECT xCoord, yCoord, terrain FROM Cave ORDER BY yCoord, xCoord";
  if (!($db_result = $db->query($query))){
    echo "Fehler beim Auslesen des Terrains!\n";
    return false;
  }
  $terrain = array();
  while($row = $db_result->nextRow(MYSQL_ASSOC)){
    if(array_key_exists($row['terrain'], $terrain_colour))
      ImageSetPixel($terrainMap, $row['xCoord'] - $size['minX'], $row['yCoord'] - $size['minY'], $terrain_colour[$row['terrain']]);
  }
  ImagePng($terrainMap, TERRAIN_MAP);
  return true;
}

function loadPNG ($imgname) {
    $im = @imagecreatefrompng ($imgname); /* Attempt to open */
    if (!$im) { /* See if it failed */
        $im  = imagecreate (150, 30); /* Create a blank image */
        $bgc = imagecolorallocate ($im, 255, 255, 255);
        $tc  = imagecolorallocate ($im, 0, 0, 0);
        imagefilledrectangle ($im, 0, 0, 150, 30, $bgc);
        /* Output an errmsg */
        imagestring ($im, 1, 5, 5, "Error loading $imgname", $tc);
    }
    return $im;
}
?> 
