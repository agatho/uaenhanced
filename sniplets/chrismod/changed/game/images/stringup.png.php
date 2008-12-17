<?php
  
  $caveID = intval($_GET['cave_id']);
  $filename = "$caveID.png";

  if (!file_exists($filename)){

    define("NAME_LENGTH", 17);

    require_once("../include/config.inc.php");
    require_once("../include/db.inc.php");
    require_once("../include/params.inc.php");
    
    $config = new Config();
    $db     = new Db();
    $post   = new POST();

    $cave = getCaveName($caveID);    
    if ($cave === 0) exit(1);
    
    $name = unhtmlentities($cave['name']);
    
    if (strlen($name) > NAME_LENGTH)
      $name = substr($name, 0, NAME_LENGTH-2) . "..";
    
    $im = imagecreate(40, 135);
    $white = imagecolorallocate($im, 255, 55, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
  
    
    imagecolortransparent($im, $white);
    imagestringup($im, 3,  5, 130, $name, 1);
    imagestringup($im, 2, 25, 130, "({$cave['xCoord']}|{$cave['yCoord']})", 1);
//    header("Content-type: image/png");
    imagepng($im, "temp/$caveID.png");
    header("Content-type: image/png");
    imagepng($im, "$caveID.png");
    imagedestroy($im);
  }
  Header("Location: $filename");

  function unhtmlentities($string){
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
  }

  function getCaveName($caveID){
  	global $db;
  
  	$res = $db->query("SELECT name, xCoord, yCoord FROM Cave WHERE caveID = '$caveID'");
  	if(!$res || $res->isEmpty()) return 0;
  	return $res->nextRow(MYSQL_ASSOC);
  }
?>