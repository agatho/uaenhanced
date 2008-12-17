<?php
function getCaveMapContent($caves, $caveID, $playerID){

  global $params, $config, $terrainList;

  $caveData = $caves[$caveID];
  $message  = '';

  // template öffnen
  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/map.ihtml');

  // Grundparameter setzen
  tmpl_set($template, 'modus', MAP);

  tmpl_set($template, 'cave_book_link', CAVE_BOOK); // ADDED by chris--- for cavebook

  // default Werte: Koordinaten dieser Höhle
  $xCoord  = $caveData['xCoord'];
  $yCoord  = $caveData['yCoord'];

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
      $message = 'Die Siedlung mit dem Namen: "' . $params->POST->caveName .
                 '" konnte nicht gefunden werden!';
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = 'Die Siedlung mit dem Namen: "' . $params->POST->caveName .
                 '" befindet sich in (' . $xCoord . ' | ' . $yCoord . ').';
    }
  }

  // caveID eingegeben ?
  else if (!empty($params->POST->targetCaveID)){
    $coords = getCaveByID($params->POST->targetCaveID);
    if ($coords === null){
      $message = 'Die Siedlung mit der ID: "' . $params->POST->targetCaveID .
                 '" konnte nicht gefunden werden!';
    } else {
      $xCoord = $coords['xCoord'];
      $yCoord = $coords['yCoord'];
      $message = 'Die Siedlung mit der ID: "' . $params->POST->targetCaveID .
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

// ADDED by chris--- for Quests --------------------------------------------------------------------------------

global $db;

        if ($cave['quest_cave'] && !(isCaveInvisibleToPlayer($cave['caveID'], $playerID, $db)) && $cave['invisible_name'] != "")
          $cave['cavename'] = $cave['invisible_name'];

// -------------------------------------------------------------------------------------------------------
    
    $cell = array('terrain'   => strtolower($terrainList[$cave['terrain']]['name']),
                  'alt'       => "{$cave['cavename']} - ({$cave['xCoord']}|{$cave['yCoord']})",
                  'link'      => "modus=" . MAP_DETAIL . "&targetCaveID={$cave['caveID']}");

    // unbewohnte Höhle

// ADDED by chris--- for Quests
// ----------------------------------------------------------

// checking if this cave is a quest cave and if its visible to the player (than he knows the quest)
// if he does not know the quest the cave is invisible

if ($cave['quest_cave'] && isCaveInvisibleToPlayer($cave['caveID'], $playerID, $db)) {
  $cave['playerID'] = 0;
}


// ----------------------------------------------------------

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

     // oder Dunkelheit zeigen?
      if ($cave['terrain'] == 4 && $text != "Frei!") {
        $text = "verdorrtes Land";
        $file = "icon_waste";
      }

    // bewohnte Höhle
    } else {

      // eigene Höhle
      if ($cave['playerID'] == $params->SESSION->user['playerID'])
        $file = "icon_cave_own";
      // fremde Höhle
      else {
        $file = "icon_cave_other";
	if ($cave['quest_cave']) $file = "icon_cave_quest";
      }

      // mit Artefakt
      if ($cave['artefacts'] != 0 && ($cave['tribe'] != GOD_ALLY || $params->SESSION->user['tribe'] == GOD_ALLY))
        $file .= "_artefact";


      // link zum Tribe einfügen
      $cell['link_tribe'] = "modus=".TRIBE_DETAIL."&tribe=".urlencode(unhtmlentities($cave['tribe']));

      // Clan abkürzen
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
    $map[$cave['xCoord']][$cave['yCoord']] = $cell;
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



// ADDED by chris--- for cavebook:

  // Getting entries
 $cavelist = cavebook_getEntries($params->SESSION->user['playerID']);

  // Show the cave table
  for($i = 0; $i < sizeof($cavelist[id]); $i++) {

    $cavename = $cavelist[name][$i]; // the current cavename
    $cavebookID = $cavelist[id][$i];
    $cave_x = $cavelist[x][$i];
    $cave_y = $cavelist[y][$i];

    tmpl_iterate($template, '/BOOKENTRY');
    tmpl_set($template, 'BOOKENTRY/book_entry', $cavename);
    tmpl_set($template, 'BOOKENTRY/book_id', $cavebookID);
    tmpl_set($template, 'BOOKENTRY/book_x', $cave_x);
    tmpl_set($template, 'BOOKENTRY/book_y', $cave_y);
  }



  
  // Minimap
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

function getCaveReport($meineHoehlen, $caveID, $targetCaveID, $playerID){
   global $params, $config, $terrainList;

  $cave  = getCaveByID($targetCaveID);

  $caveDetails   = array();
  $playerDetails = array();


// ADDED by chris--- for Quests
// ----------------------------------------------------------
global $db;

// checking if this cave is a quest cave and if its visible to the player (than he knows the quest)
// if he does not know the quest the cave is invisible

if (isCaveQuestCave($targetCaveID, $db) && isCaveInvisibleToPlayer($targetCaveID, $playerID, $db)) $cave['playerID'] = 0;


// ----------------------------------------------------------

  if ($cave['playerID'] != 0){
    $caveDetails   = getCaves($cave['playerID']);
    $playerDetails = getPlayerFromID($cave['playerID']);

// ADDED by chris--- for farmschutz
if (FARMSCHUTZ_ACTIVE == 1) $farmschutz = getFarmschutz($cave['playerID']);
  else $farmschutz = "";

  }

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/mapdetail.ihtml');

  if ($cave['protected']) tmpl_set($template, 'PROPERTY', 'Anf&auml;ngerschutz aktiv');

  if (!$cave['secureCave'] && $cave['playerID']){
    tmpl_iterate($template, 'PROPERTY');
    tmpl_set($template, 'PROPERTY', '&uuml;bernehmbar!');
  }

// ADDED by chris--- for Quests --------------------------------------------------------------------------------

        if ($cave['quest_cave'] && !(isCaveInvisibleToPlayer($cave['caveID'], $playerID, $db)) && $cave['invisible_name'] != "")
          $cave['name'] = $cave['invisible_name'];

// -------------------------------------------------------------------------------------------------------

$addCaveLink = "?modus=".CAVE_BOOK_ADD."&amp;id=".$targetCaveID; // ADDED by chris--- for cavebook

  tmpl_set($template, '/' , array('cavename'     => $cave['name'],
                                  'xcoord'       => $cave['xCoord'],
                                  'ycoord'       => $cave['yCoord'],
                                  'terrain'      => $terrainList[$cave['terrain']]['name'],
                                  'movementlink' => "?modus="          . MOVEMENT .
                                                    "&targetXCoord="   . $cave['xCoord'] .
                                                    "&targetYCoord="   . $cave['yCoord'] .
                                                    "&targetCaveName=" . unhtmlentities($cave['name']),
                                  'backlink'     => "?modus="  . MAP .
                                                    "&xCoord=" . $cave['xCoord'] .
                                                    "&yCoord=" . $cave['yCoord']));
  if ($cave['playerID'] != 0){

    tmpl_set($template, '/OCCUPIED', array('playerLink'  => "?modus="    . PLAYER_DETAIL .
                                                            "&detailID=" . $playerDetails['playerID'],
                                           'caveOwner'   => $playerDetails['name'],
// ADDED by chris--- for farmschutz
					   'farmschutz' => $farmschutz,
// ADDED by chris--- for adressbook
					   'adressbook_add_modus' => MESSAGE_BOOK_ADD,
// ADDED by chris--- for adressbook
					   'addCaveLink'	=> $addCaveLink));


    if ($playerDetails['tribe']){
      tmpl_set($template, '/OCCUPIED/TRIBE', array(
        'tribeLink'   => "?modus=".TRIBE_DETAIL."&tribe=".urlencode(unhtmlentities($playerDetails['tribe'])),
        'ownersTribe' => $playerDetails['tribe']));
    }
    if ($cave['artefacts'] != 0 &&
        ($playerDetails['tribe'] != GOD_ALLY || $params->SESSION->user['tribe'] == GOD_ALLY)){
      tmpl_set($template, '/OCCUPIED/ARTEFACT/artefactLink', "?modus=" . ARTEFACT_LIST . "&caveID={$caveID}");
    }

    $caves = array();
    foreach ($caveDetails AS $key => $value){

if (!(isCaveQuestCave($value['caveID'], $db) && isCaveInvisibleToPlayer($value['caveID'], $playerID, $db))) {


      $temp = array('caveName'     => $value['name'],
                    'xCoord'       => $value['xCoord'],
                    'ycoord'       => $value['yCoord'],
                    'terrain'      => $terrainList[$value['terrain']]['name'],
                    'caveSize'     => floor($value[CAVE_SIZE_DB_FIELD] / 50) + 1,
                    'movementLink' => "?modus=" . MOVEMENT .
                                      "&targetXCoord=" . $value['xCoord'] .
                                      "&targetYCoord=" . $value['yCoord'] .
                                      "&targetCaveName=" . unhtmlentities($value['name']));

      if ($value['artefacts'] != 0 && ($playerDetails['tribe'] != GOD_ALLY || $params->SESSION->user['tribe'] == GOD_ALLY))
        $temp['ARTEFACT'] = array('artefactLink' => "?modus=" . ARTEFACT_LIST . "&caveID={$caveID}");

      if ($value['protected'] && $value['playerID'])
        $temp['PROPERTY'] = array('text' =>'Schutz');
      else if (!$value['secureCave'])
        $temp['PROPERTY'] = array('text' => '&uuml;bernehmbar');

      $caves[] = $temp;
}
    }
    tmpl_set($template, '/OCCUPIED/CAVES', $caves);

  } else if (sizeof($meineHoehlen) < $params->SESSION->user['takeover_max_caves'] && $cave['takeoverable'] == 1){

    tmpl_set($template, 'TAKEOVERABLE',
             array('modus'        => TAKEOVER,
                   'caveID'       => $caveID,
                   'targetXCoord' => $cave['xCoord'],
                   'targetYCoord' => $cave['yCoord']));
  }

  return tmpl_parse($template);
}
?>
