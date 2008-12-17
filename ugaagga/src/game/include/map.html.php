<?
/*
 * map.html.php - 
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

@include_once('modules/CaveBookmarks/model/CaveBookmarks.php');

function getCaveMapContent($caves, $caveID){

  global $params, $config, $terrainList;

  $caveData = $caves[$caveID];
  $message  = '';

  // template �ffnen
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'map.ihtml');

  // Grundparameter setzen
  tmpl_set($template, 'modus', MAP);

  // default Werte: Koordinaten dieser H�hle
  $xCoord  = $caveData['xCoord'];
  $yCoord  = $caveData['yCoord'];

  // Gr��e der Karte wird ben�tigt
  $mapSize = getMapSize();

  // wenn in die Minimap geklickt wurde, zoome hinein
  if (isset($params->POST->minimap_x) && isset($params->POST->minimap_y) && $params->POST->scaling != 0){
    $xCoord = Floor($params->POST->minimap_x * 100 / $params->POST->scaling) + $mapSize['minX'];
    $yCoord = Floor($params->POST->minimap_y * 100 / $params->POST->scaling) + $mapSize['minY'];
  }

  // caveName eingegeben ?
  else if (isset($params->POST->caveName)){
    $coords = getCaveByName($params->POST->caveName);
    if (sizeof($coords) == 0){
      $message = sprintf(_('Die H�hle mit dem Namen: "%s" konnte nicht gefunden werden!'), $params->POST->caveName);
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die H�hle mit dem Namen: "%s" befindet sich in (%d|%d).'), $params->POST->caveName, $xCoord, $yCoord);
    }
  }

  // caveID eingegeben ?
  else if (isset($params->POST->targetCaveID)){
    $coords = getCaveByID($params->POST->targetCaveID);
    if ($coords === null){
      $message = sprintf(_('Die H�hle mit der ID: "%d" konnte nicht gefunden werden!'), $params->POST->targetCaveID);       
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = sprintf(_('Die H�hle mit der ID: "%d" befindet sich in (%d|%d).'), $params->POST->targetCaveID, $xCoord, $yCoord);
    }
  }

  // Koordinaten eingegeben ?
  else if (isset($params->POST->xCoord) && isset($params->POST->yCoord)){
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
  $MAP_WIDTH  = min(MAP_WIDTH,  $mapSize['maxX']-$mapSize['minX']+1);
  $MAP_HEIGHT = min(MAP_HEIGHT, $mapSize['maxY']-$mapSize['minY']+1);

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

    $cell = array('terrain'   => 'terrain'.$cave['terrain'],
                  'alt'       => "{$cave['cavename']} - ({$cave['xCoord']}|{$cave['yCoord']}) - {$cave['region']}",
                  'link'      => "modus=map_detail&amp;targetCaveID={$cave['caveID']}");

    // unbewohnte H�hle
    if ($cave['playerID'] == 0){

      // als Frei! zeigen
      if ($cave['takeoverable'] == 1){
        $text = _('Frei!');
        $file = "icon_cave_empty";
      // als Ein�de zeigen
      } else {
        $text = _('Ein�de');
        $file = "icon_waste";
      }

    // bewohnte H�hle
    } else {

      // eigene H�hle
      if ($cave['playerID'] == $params->SESSION->player->playerID)
        $file = "icon_cave_own";
      // fremde H�hle
      else
        $file = "icon_cave_other";

      // mit Artefakt
      if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $params->SESSION->player->tribe == GOD_ALLY))
        $file .= "_artefact";


      // link zum Tribe einf�gen
      $cell['link_tribe'] = "modus=tribe_detail&amp;tribe=".urlencode(unhtmlentities($cave['tribe']));

      // Stamm abk�rzen
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

      // �bernehmbare H�hlen k�nnen gekennzeichnet werden
      if ($cave['secureCave'] != 1)
        $cell['unsecure'] = array('dummy' => '');
    }

    $cell['file'] = $file;
    $cell['text'] = $text;

    // Wenn die H�hle ein Artefakt enth�lt und man berechtigt ist -> anzeigen
    if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $params->SESSION->player->tribe == GOD_ALLY)){
      $cell['artefacts'] = $cave['artefacts'];
      $cell['artefacts_text'] = sprintf(_('Artefakte: %d'), $cave['artefacts']);
    }
    $map[$cave['xCoord']][$cave['yCoord']] = $cell;
  }

  // Karte mit Beschriftungen ausgeben

  // �ber alle Zeilen
  for ($j = $minY - 1; $j <= $maxY + 1; ++$j){
    tmpl_iterate($template, '/ROWS');
    // �ber alle Spalten
    for ($i = $minX - 1; $i <= $maxX + 1; ++$i){
      tmpl_iterate($template, '/ROWS/CELLS');

      // leere Zellen
      if (($j == $minY - 1 || $j == $maxY + 1) && 
          ($i == $minX - 1 || $i == $maxX + 1)){
        tmpl_set($template, "/ROWS/CELLS", getCornerCell());
      
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
  
  // Minimap
  $width  = $mapSize['maxX'] - $mapSize['minX'] + 1;
  $height = $mapSize['maxY'] - $mapSize['minY'] + 1;
  
  // compute mapcenter coords
  $mcX = $minX + intval($MAP_WIDTH/2);
  $mcY = $minY + intval($MAP_HEIGHT/2);

  tmpl_set($template, "/MINIMAP", array('file'    => "images/minimap.png.php?x=" . $xCoord . "&amp;y=" . $yCoord,
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

  // Module "CaveBookmarks" Integration
  // FIXME should know whether the module is installed
  if (TRUE){

    // show CAVEBOOKMARKS context
    tmpl_set($template, '/CAVEBOOKMARKS/iterate', '');

    // get model
    $cb_model = new CaveBookmarks_Model();
    
    // get bookmarks
    $bookmarks = $cb_model->getCaveBookmarks(true);
    
    // set bookmarks
    if (sizeof($bookmarks))
      tmpl_set($template, '/CAVEBOOKMARKS/CAVEBOOKMARK', $bookmarks);
  }

  return tmpl_parse($template);

}

function getCaveReport($meineHoehlen, $caveID, $targetCaveID){
  global $params, $config, $terrainList;

  $cave  = getCaveByID($targetCaveID);

  $caveDetails   = array();
  $playerDetails = array();
  if ($cave['playerID'] != 0){
    $caveDetails   = getCaves($cave['playerID']);
    $playerDetails = getPlayerByID($cave['playerID']);
  }

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'mapdetail.ihtml');

  if ($cave['protected']) tmpl_set($template, 'PROPERTY', _('Anf�ngerschutz aktiv'));

  if (!$cave['secureCave'] && $cave['playerID']){
    tmpl_iterate($template, 'PROPERTY');
    tmpl_set($template, 'PROPERTY', _('�bernehmbar'));
  }

  $region = getRegionByID($cave['regionID']);

  tmpl_set($template, array('cavename'     => $cave['name'],
                            'xcoord'       => $cave['xCoord'],
                            'ycoord'       => $cave['yCoord'],
                            'terrain'      => $terrainList[$cave['terrain']]['name'],
                            'region'       => $region['name'],
                            'movementlink' => sprintf("?modus=unit_movement&amp;targetXCoord=%d&amp;targetYCoord=%d&amp;targetCaveName=%s",
                                                      $cave['xCoord'], $cave['yCoord'], unhtmlentities($cave['name'])),
                            'backlink'     => sprintf("?modus=map&amp;xCoord=%d&amp;yCoord=%d",
                                                      $cave['xCoord'], $cave['yCoord'])));
  if ($cave['playerID'] != 0){

    tmpl_set($template, '/OCCUPIED', array('playerLink'  => "?modus=player_detail&amp;detailID=" . $playerDetails['playerID'],
                                           'caveOwner'   => $playerDetails['name']));

    if ($playerDetails['tribe']){
      tmpl_set($template, '/OCCUPIED/TRIBE', array(
        'tribeLink'   => "?modus=tribe_detail&amp;tribe=".urlencode(unhtmlentities($playerDetails['tribe'])),
        'ownersTribe' => $playerDetails['tribe']));
    }
    if ($cave['artefacts'] != 0 &&
        ($playerDetails['tribe'] != GOD_ALLY || $params->SESSION->player->tribe == GOD_ALLY)){
      tmpl_set($template, '/OCCUPIED/ARTEFACT/artefactLink', "?modus=artefact_list&amp;caveID={$caveID}");
    }

    $caves = array();
    foreach ($caveDetails AS $key => $value){
      $temp = array('caveName'     => $value['name'],
                    'xCoord'       => $value['xCoord'],
                    'ycoord'       => $value['yCoord'],
                    'terrain'      => $terrainList[$value['terrain']]['name'],
                    'caveSize'     => floor($value[CAVE_SIZE_DB_FIELD] / 50) + 1,
                    'movementLink' => "?modus=unit_movement&amp;targetXCoord=" . $value['xCoord'] .
                                      "&amp;targetYCoord=" . $value['yCoord'] .
                                      "&amp;targetCaveName=" . unhtmlentities($value['name']));

      if ($value['artefacts'] != 0 && ($playerDetails['tribe'] != GOD_ALLY || $params->SESSION->player->tribe == GOD_ALLY))
        $temp['ARTEFACT'] = array('artefactLink' => "?modus=artefact_list&amp;caveID={$caveID}");

      if ($value['protected'] && $value['playerID'])
        $temp['PROPERTY'] = array('text' => _('Anf�ngerschutz aktiv'));
      else if (!$value['secureCave'])
        $temp['PROPERTY'] = array('text' => _('�bernehmbar'));

      $caves[] = $temp;
    }
    tmpl_set($template, '/OCCUPIED/CAVES', $caves);

  } else if (sizeof($meineHoehlen) < $params->SESSION->player->takeover_max_caves && $cave['takeoverable'] == 1){

    tmpl_set($template, 'TAKEOVERABLE',
             array('modus'        => TAKEOVER,
                   'caveID'       => $caveID,
                   'targetXCoord' => $cave['xCoord'],
                   'targetYCoord' => $cave['yCoord']));
  }

  return tmpl_parse($template);
}
?>
