<?
/*
 * map.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

// include necessary files
include "config.inc.php";
include "db.inc.php";
include "game_rules.php";
include "basic.lib.php";
include "params.inc.php";

define("MAPWIDTH",       10);
define("MAPHEIGHT",      10);

// get globals
$config = new Config();
$db     = new Db();
$params = new Params();

// initialize game rules
//init_resources();
//init_sciences();

echo getCaveMapContent();

function getCaveMapContent(){

  global $params, $config, $terrainList;

  // template öffnen
  $template = tmpl_open('./templates/map.ihtml');

  // Grundparameter setzen
  tmpl_set($template, 'modus', MAP);

  // Koordinaten eingegeben ?
  if (isset($params->xCoord) && isset($params->yCoord)){
    $xCoord = $params->xCoord; $yCoord = $params->yCoord;
  } else {
    $xCoord  = 1; $yCoord  = 1;
  }

  // Größe der Karte wird benötigt
  $mapSize = getMapSize();

 // wenn in die Minimap geklickt wurde, zoome hinein
  if (!empty($params->POST->minimap_x) && !empty($params->POST->minimap_y) && $params->POST->scaling != 0){
    $xCoord = Floor($params->POST->minimap_x * 100 / $params->POST->scaling) + $mapSize['minX'];
    $yCoord = Floor($params->POST->minimap_y * 100 / $params->POST->scaling) + $mapSize['minY'];
  }

  // caveName eingegeben ?
  else if (!empty($params->POST->caveName)){
    $coords = getCaveByName($params->POST->caveName);
    if (sizeof($coords) == 0){
      $message = 'Die H&ouml;hle mit dem Namen: "' . $params->POST->caveName .
                 '" konnte nicht gefunden werden!';
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = 'Die H&ouml;hle mit dem Namen: "' . $params->POST->caveName .
                 '" befindet sich in (' . $xCoord . ' | ' . $yCoord . ').';
    }
  }

  // caveID eingegeben ?
  else if (!empty($params->POST->targetCaveID)){
    $coords = getCaveByID($params->POST->targetCaveID);
    if ($coords === null){
      $message = 'Die H&ouml;hle mit der ID: "' . $params->POST->targetCaveID .
                 '" konnte nicht gefunden werden!';
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = 'Die H&ouml;hle mit der ID: "' . $params->POST->targetCaveID .
                 '" befindet sich in (' . $xCoord . ' | ' . $yCoord . ').';
    }
  }

  // Koordinaten eingegeben ?
  else if (!empty($params->POST->xCoord) && !empty($params->POST->yCoord)){
    $xCoord = $params->POST->xCoord;
    $yCoord = $params->POST->yCoord;
  }
  
  if (isset($messageID)){
    tmpl_set($template, '/MESSAGE/message', $message);
  }

  // Koordinaten begrenzen
  if ($xCoord < $mapSize['minX']) $xCoord = $mapSize['minX'];
  if ($yCoord < $mapSize['minY']) $yCoord = $mapSize['minY'];
  if ($xCoord > $mapSize['maxX']) $xCoord = $mapSize['maxX'];
  if ($yCoord > $mapSize['maxY']) $yCoord = $mapSize['maxY'];

  // width und height anpassen
  $mapwidth  = isset($params->width)  ? intval($params->width)  : MAPWIDTH;
  $mapheight = isset($params->height) ? intval($params->height) : MAPHEIGHT;
  $MAP_WIDTH  = min($mapwidth,  $mapSize['maxX']-$mapSize['minX']+1);
  $MAP_HEIGHT = min($mapheight, $mapSize['maxY']-$mapSize['minY']+1);

  // Nun befinden sich in $xCoord und $yCoord die gesuchten Koordinaten.
  // ermittele nun die linke obere Ecke des Bildausschnittes
  $minX = min(max($xCoord - intval($MAP_WIDTH/2),  $mapSize['minX']), $mapSize['maxX']-$MAP_WIDTH+1);
  $minY = min(max($yCoord - intval($MAP_HEIGHT/2), $mapSize['minY']), $mapSize['maxY']-$MAP_HEIGHT+1);
  // ermittele nun die rechte untere Ecke des Bildausschnittes
  $maxX = $minX + $MAP_WIDTH  - 1;
  $maxY = $minY + $MAP_HEIGHT - 1;

  // get the map details
  $caveDetails = getCaveDetailsByCoords($minX, $minY, $maxX, $maxY);

  $map = array();
  foreach ($caveDetails AS $cave){
  
       $cell = array('terrain'   => strtolower($terrainList[$cave['terrain']]['name']),
                  'alt'       => "{$cave['cavename']} - ({$cave['xCoord']}|{$cave['yCoord']})",
                  'link'      => "modus=" . MAP_DETAIL . "&targetCaveID={$cave['caveID']}");

    // unbewohnte Höhle
    if ($cave['playerID'] == 0){

      // als Frei! zeigen, wenn man missionieren kann
      if (sizeof($caves) < $params->SESSION->user['takeover_max_caves'] && $cave['takeoverable'] == 1){
        $text = "Frei!";
        $file = "icon_cave_empty";
      // als Einöde zeigen, wenn man nicht mehr missionieren kann
      } else {
        $text = "Ein&ouml;de";
        $file = "icon_waste";
      }

    // bewohnte Höhle
    } else {

      // eigene Höhle
      if ($cave['playerID'] == $params->SESSION->user['playerID'])
        $file = "icon_cave_own";
      // fremde Höhle
      else
        $file = "icon_cave_other";

      // mit Artefakt
      if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $params->SESSION->user['tribe'] == GOD_ALLY))
        $file .= "_artefact";


      // link zum Tribe einfügen
      $cell['link_tribe'] = "modus=".TRIBE_DETAIL."&tribe=".urlencode(unhtmlentities($cave['tribe']));

      // Stamm abkürzen
      $decodedTribe = unhtmlentities($cave['tribe']);
      if (strlen($decodedTribe) > 10)
        $cell['text_tribe'] = htmlentities(substr($decodedTribe, 0, 8)) . "..";
      else
        $cell['text_tribe'] = $cave['tribe'];

      // Besitzer
      $decodedOwner = unhtmlentities($cave['name']);
      if (strlen($decodedOwner) > 10)
        $text = htmlentities(substr($decodedOwner, 0, 8)) . "..";
      else
        $text = $cave['name'];

      // übernehmbare Höhlen können gekennzeichnet werden
      if ($cave['secureCave'] != 1)
        $cell['unsecure'] = array('dummy' => '');
    }

    $cell['file'] = $file;
    $cell['text'] = $text;

    // Wenn die Höhle ein Artefakt enthält und man berechtigt ist -> anzeigen
    if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $params->SESSION->user['tribe'] == GOD_ALLY)){
      $cell['artefacts'] = $cave['artefacts'];
      $cell['artefacts_text'] = "Artefakte: {$cave['artefacts']}";
    }
    
    $map[$cave['xCoord']][$cave['yCoord']] = $cave;
  }

  // Karte mit Beschriftungen ausgeben

  // über alle Zeilen
  for ($j = $minY - 1; $j <= $maxY + 1; ++$j){
    tmpl_iterate($template, '/ROWS');
    // über alle Spalten
    for ($i = $minX - 1; $i <= $maxX + 1; ++$i){
      tmpl_iterate($template, '/ROWS/CELLS');

      // leere Zellen
      if (($j == $minY - 1 || $j == $maxY + 1) && 
          ($i == $minX - 1 || $i == $maxX + 1)){
        tmpl_set($template, "/ROWS/CELLS", getEmptyCell());
      
      // x-Beschriftung
      } else if ($j == $minY - 1 || $j == $maxY + 1){
        tmpl_set($template, "/ROWS/CELLS", getLegendCell('x', $i));
      
      // y-Beschriftung
      } else if ($i == $minX - 1 || $i == $maxX + 1){
        tmpl_set($template, "/ROWS/CELLS", getLegendCell('y', $j));
      
      // Kartenzelle
      } else {
        tmpl_set($template, "/ROWS/CELLS", getMapCell($map, $i, $j));
      }
    }
  }
  $width  = $mapSize['maxX'] - $mapSize['minX'] + 1;
  $height = $mapSize['maxY'] - $mapSize['minY'] + 1;
  
  // compute mapcenter coords
  $mcX = $minX + intval($MAP_WIDTH/2);
  $mcY = $minY + intval($MAP_HEIGHT/2);

  tmpl_set($template, "/MINIMAP", array('file'    => "images/minimap.png.php?x=" . $xCoord . "&y=" . $yCoord,
                                        'modus'   => MAP,
                                        'width'   => intval($width * MINIMAP_SCALING / 100),
                                        'height'  => intval($height * MINIMAP_SCALING / 100),
                                        'scaling' => MINIMAP_SCALING));

  tmpl_set($template, '/O',  array('modus' => MAP, 'x' => ($mcX + $MAP_WIDTH), 'y' =>  $mcY));
  tmpl_set($template, '/SO', array('modus' => MAP, 'x' => ($mcX + $MAP_WIDTH), 'y' => ($mcY + $MAP_HEIGHT)));
  tmpl_set($template, '/S',  array('modus' => MAP, 'x' =>  $mcX,               'y' => ($mcY + $MAP_HEIGHT)));
  tmpl_set($template, '/SW', array('modus' => MAP, 'x' => ($mcX - $MAP_WIDTH), 'y' => ($mcY + $MAP_HEIGHT)));
  tmpl_set($template, '/W',  array('modus' => MAP, 'x' => ($mcX - $MAP_WIDTH), 'y' =>  $mcY));
  tmpl_set($template, '/NW', array('modus' => MAP, 'x' => ($mcX - $MAP_WIDTH), 'y' => ($mcY - $MAP_HEIGHT)));
  tmpl_set($template, '/N',  array('modus' => MAP, 'x' =>  $mcX,               'y' => ($mcY - $MAP_HEIGHT)));
  tmpl_set($template, '/NO', array('modus' => MAP, 'x' => ($mcX + $MAP_WIDTH), 'y' => ($mcY - $MAP_HEIGHT)));
  
return tmpl_parse($template);
}

function getCaveDetailsByCoords($minX, $minY, $maxX, $maxY){
	global $db;

  $caveDetails = array();
	$query = "SELECT c.terrain, c.name AS cavename, c.caveID, c.xCoord, ".
	         "c.yCoord, c.secureCave, c.artefacts, c.takeoverable, ".
	         "p.name, p.playerID, p.tribe ".
	         "FROM Cave c LEFT JOIN Player p ".
	         "ON c.playerID = p.playerID ".
	         "WHERE $minX <= c.xCoord AND c.xCoord <= $maxX ".
	         "AND   $minY <= c.yCoord AND c.yCoord <= $maxY ".
	         "ORDER BY c.yCoord, c.xCoord";
	if($res = $db->query($query))
		while($row = $res->nextRow(MYSQL_ASSOC))
			array_push($caveDetails, $row);
	return $caveDetails;
}

function getEmptyCell(){
  return array('HEADER' => array('text' => '&nbsp;'));
}

function getLegendCell($name, $value){
  return array('HEADER' => array('text' => "<small>$name: $value</small>"));
}

function getMapCell($map, $xCoord, $yCoord){
  if (!is_array($map[$xCoord][$yCoord]))
    return getEmptyCell();
  else
    return array('MAPCELL' => $map[$xCoord][$yCoord]);
}
?>