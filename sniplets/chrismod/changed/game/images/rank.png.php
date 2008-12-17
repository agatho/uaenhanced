<?PHP

// config
$imgX = 380;
$imgY = 180;

$offsetTop = 30;
$offsetBottom = 20;
$offsetLeft = 40;
$offsetRight = 20;

$maxDays = 10;


require_once("../include/config.inc.php");
require_once("../include/db.inc.php");
require_once("../include/params.inc.php");

define("TIMEOUT", 60*60);


$config = new Config();
$db     = new Db();


$playerID = intval($_GET['detailID']);

if (!$playerID) {
  echo "No playerid\r\n";
  exit;
}

// -------------------------------------

define("IMG_FILE", "points_".$playerID.".png");



// get file's lifetime if existent
$lifetime = -1;

if (file_exists(IMG_FILE)){
  $lifetime = time() - filemtime(IMG_FILE);
}

// get map file's size
if ($lifetime == -1 || $lifetime >= TIMEOUT){
  $status = createImg($db, $playerID);
  if ($status < 0) {

    // create error image
    $image = imagecreate(150, 30);
    $farbe_body = imagecolorallocate($image,255,255,196); // background color
    $farbe_s = imagecolorallocate($image,0,0,0); // graph line color
    $farbe_r = imagecolorallocate($image,220,0,0); // line color
    $farbe_g = imagecolorallocate($image,0,200,0); // average color
    $farbe_b = imagecolorallocate($image,0,0,220); // graph line color

    imagecolortransparent($image,$farbe_body);

    $headline = "FEHLER!";
    if ($status == -1) $headline = "Fehler beim Auslesen des Rankings!";
    if ($status == -2) $headline = "Unbekannte playerID!";

    $x = (150-imagefontwidth(2)*strlen($headline))/2;
    $y = (imagefontheight(2))/2;
    imagestring ($image,2,$x,$y, $headline, $farbe_r);

    ImagePng($image, IMG_FILE);

  }
}
$image = loadPNG(IMG_FILE);


// show graph
imageinterlace($image,1);
header("Content-type: image/png");
imagepng($image);
imagedestroy($image);



// functions ------------------------------------------------


function createImg($db, $playerID) {

global $imgX, $imgY, $offsetTop, $offsetBottom, $offsetLeft, $offsetRight, $maxDays;

$headerSize = 2;


// Getting the points

$query = "SELECT * FROM rank_history WHERE playerID = ".$playerID;
  if (!($db_result = $db->query($query))) return -1;

if(!($row = $db_result->nextRow(MYSQL_ASSOC))) return -2;

$name = $row[name];
$curr_day = $row[curr_day];

$headline = "Punkte von ".$name." in den letzten ".$maxDays." Tagen";

$day = array();
for ($i=1;$i<=$maxDays;$i++) {
  $string = '$row[day_'.$i.']';
  eval ("\$string = \"$string\";");
  $day[$i] = $string;
}


// sorting the day array

$a = $maxDays+1;
for ($i=$curr_day;$i>0;$i--) {
  $a--;
  $points[$a] = $day[$i];
}

for ($i=$maxDays;$i>$curr_day;$i--) {
  $a--;
  $points[$a] = $day[$i];
}





// creating the base graph

$image = imagecreate($imgX, $imgY);
$farbe_body=imagecolorallocate($image,255,255,196); // background color
$farbe_s = imagecolorallocate($image,0,0,0); // graph line color
$farbe_r = imagecolorallocate($image,220,0,0); // line color
$farbe_g = imagecolorallocate($image,0,200,0); // average color
$farbe_b = imagecolorallocate($image,0,0,220); // graph line color

imagecolortransparent($image,$farbe_body);


imagefilledrectangle($image,$offsetLeft,$imgY-$offsetBottom,$imgX-$offsetRight,$imgY-$offsetBottom,$farbe_s); // X-base line
imagefilledrectangle($image,$offsetLeft+5,$offsetTop,$offsetLeft+5,$imgY-$offsetBottom,$farbe_s); // Y-base line
imagefilledrectangle($image,$imgX-$offsetRight,$offsetTop,$imgX-$offsetRight,$imgY-$offsetBottom,$farbe_s); // Y-base line2


// calc diff
$diff = ($imgY-$offsetTop-$offsetBottom)/$maxDays;
$linelength = 10; // pixel
$pointDiff = 6;

// draw 10 lines on y

for ($i=0;$i<$maxDays;$i++) {

  $y = $offsetTop+$i*$diff;

  imagefilledrectangle($image,$offsetLeft+5-$linelength/2,$y,$offsetLeft+5+$linelength/2,$y,$farbe_s); // X-base line

  // create dotted line
  // calc points

  $numPoints = ($imgX-$offsetLeft-$offsetRight-$linelength)/$pointDiff;

  for ($a=0;$a<$numPoints;$a++) {

    $x = ($offsetLeft+5+$linelength/2)+$pointDiff*$a;

    imagesetpixel($image, $x, $y, $farbe_s);
  }

}


// draw 10 lines on x

$diff = floor(($imgX-$offsetRight-$offsetLeft)/($maxDays-1));

$linelength = 10; // pixel

for ($i=0;$i<$maxDays;$i++) {

  $x = $offsetLeft+5+$i*$diff;

  imagefilledrectangle($image,$x,$imgY-$offsetBottom-$linelength/2,$x,$imgY-$offsetBottom+$linelength/2,$farbe_s); // X-base line

}


// Beschriftung

// headline


$size = pow(1.74,$headerSize);

$y = $offsetTop/2-2*$size;
if ($y < 0) $y = 2;
$x = $imgX/2-(strlen($headline)*$size);
if ($x < 0) $x = 2;

$x = ($imgX-imagefontwidth($headerSize)*strlen($headline))/2;
$y = ($offsetTop-imagefontheight($headerSize))/2;


imagestring ($image,$headerSize,$x,$y, $headline, $farbe_s);



// bottom numbers

$diff = floor(($imgX-$offsetLeft-$offsetRight)/($maxDays-1));

$size = 1; // pixel

for ($i=$maxDays;$i>0;$i--) {

  $y = $imgY-$offsetBottom+7;

  $x = $offsetLeft+5+$i*$diff-$diff-2;

  imagestring ($image,$size,$x,$y, $maxDays+1-$i, $farbe_s);

}


// left numbers

// calc max and min

$minimum = min($points);
$maximum = max($points);

if ($maximum-$minimum < 100) $minimum = $minimum-100;
if ($minimum <= 0) $minimum = 1;

$textdiff = round(($maximum-$minimum)/($maxDays));

$maximum2 = 1-(1/($maximum/$minimum));


// getting nearest max
/*
if (strlen($maximum) == 4) $maximum = ceil($maximum/100)*100;
if (strlen($maximum) == 3) $maximum = ceil($maximum/10)*10;
*/


// getting nearest min

//if (strlen($maximum) == 5)
/*
if (strlen($minimum) == 4) $minimum = floor($minimum/100)*100;
if (strlen($minimum) == 3) $minimum = floor($minimum/10)*10;

$textdiff = round(($maximum-$minimum)/($maxDays));
*/

$diff = ($imgY-$offsetTop-$offsetBottom)/$maxDays;
$size = 1;

// draw left numbers

for ($i=0;$i<=$maxDays;$i++) {

  $x = $offsetLeft-7-20;

  $y = $offsetTop+($i)*$diff-3;

  $text = $maximum-$textdiff*$i;

  if ($i == 0) $text = $maximum;
//  if ($i == $maxDays) $text = $minimum;

  imagestring ($image,$size,$x,$y, $text, $farbe_s);

}



// the line

$diff = floor(($imgX-$offsetLeft-$offsetRight)/($maxDays-1));


for ($i=$maxDays;$i>0;$i--) {

  $x = $offsetLeft+5+$i*$diff-$diff;
  $x2 = $offsetLeft+5+($i+1)*$diff-$diff;


  if ($points[$i] == $maximum) $points[$i]--;
  if ($points[$i+1] == $maximum) $points[$i+1]--;
  if($points[$i] <= 0) $points[$i] = 1;


  $y = $offsetTop+(($imgY-$offsetTop-$offsetBottom)/(($maximum/($maximum-$points[$i])*$maximum2)));
  $y2 = $offsetTop+(($imgY-$offsetTop-$offsetBottom)/(($maximum/($maximum-$points[$i+1])*$maximum2)));


  if (!$points[$i+1]) {
    $y2 = $y;
    $x2 = $x;
  }


    imageline($image, $x, $y, $x2, $y2, $farbe_r);

// draw little crosses
$linelength = 8;

    imageline($image, $x-($linelength/2), $y, $x+($linelength/2), $y, $farbe_b);
    imageline($image, $x, $y-($linelength/2), $x, $y+($linelength/2), $farbe_b);

}

// average

$average1 = 0;
$till = round($maxDays/2);
for ($i=1;$i<=$till;$i++) {
  $average1 += $points[$i];
}
$average1 = $average1/($till);

$average2 = 0;
for ($i=$till+1;$i<=$maxDays;$i++) {
  $average2 += $points[$i];
}
$average2 = $average2/($till);


$diff = floor(($imgX-$offsetLeft-$offsetRight)/($maxDays-1));

$x = $offsetLeft+5+1*$diff-$diff;
$x2 = $offsetLeft+5+($maxDays)*$diff-$diff;

$y = $offsetTop+(($imgY-$offsetTop-$offsetBottom)/(($maximum/($maximum-$average1)*$maximum2)));
$y2 = $offsetTop+(($imgY-$offsetTop-$offsetBottom)/(($maximum/($maximum-$average2)*$maximum2)));

imageline($image, $x, $y, $x2, $y2, $farbe_g);


ImagePng($image, IMG_FILE);
return true;

} // end of function



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