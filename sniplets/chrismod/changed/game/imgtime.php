<?
require_once("include/time.inc.php");
require_once("include/basic.lib.php");
  
$now = getUgaAggaTime(time());

$time = $now['day'] . ". " . unhtmlentities(getMonthName($now['month'])) . ", " . $now['year'] . ". Jahr";
$timelen = strlen($time);

$size = 11;
if ($timelen > 18) $size = 8;

$x = 95-$timelen/2*$size;
if ($x < 0) $x = 0;
  
  $im_bg = @imagecreate(185,32);
  $im_fg = @imagecreate(185,32);

  $background_color = ImageColorAllocate ($im_bg, 255, 255, 255);
  $background_color = ImageColorAllocate ($im_fg, 255, 255, 255);
  $text_color = ImageColorAllocate ($im_fg, 0, 0, 0);
  ImageTTFText($im_fg, $size, 0, $x, 18, -$text_color, "aniron.ttf", $time);

  imagecolortransparent($im_fg, $background_color);
  imagecolortransparent($im_bg, $background_color);
  imagecopy ($im_bg, $im_fg, 0, 0, 0, 0, 185, 32);

  header ("Content-type: image/png");
  ImagePNG ($im_bg);

?>