<?
  require_once("config.inc.php");
  
  global $cfg;
  
  session_save_path($cfg['SESSION_SAVE_PATH']);
  session_name("UAPORTAL");
  session_start();

  $im_bg = @imagecreatefrompng("templates/images/security_bg.png");
  $im_fg = @imagecreate(imagesx($im_bg), imagesy($im_bg));

  $background_color = ImageColorAllocate ($im_fg, 255, 255, 255);
  $text_color = ImageColorAllocate ($im_fg, 255, 51, 0);
  for ($i = 0; $i < 4; ++$i)
    ImageTTFText($im_fg, 20, 0, 3 + 15*$i, 24 + rand(-2, 2) * 3, -$text_color,
//                 dirname($_SERVER['SCRIPT_FILENAME']) . "decoder.ttf",
                 "decoder.ttf",
                 $_SESSION['seccode']{$i});

  // when did we send the seccode
  $_SESSION['show_seccode_time'] = time();  
  //$rotated_img = ImageRotate($im, 10, 0);
  
  imagecolortransparent($im_fg, $background_color);
  imagecopy ($im_bg, $im_fg, 0, 0, 0, 0, imagesx($im_bg), imagesy($im_bg));

  header ("Content-type: image/png");
  ImagePNG ($im_bg);
?>