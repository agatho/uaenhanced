<?
/*
 * cave_book.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function show_cavebook($playerID, $deleteID) {
  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $unitTypeList,
         $config,
         $params,
         $db;

  // messages
  $messageText = array (
    0 => "Siedlung wurde eingetragen.",
    1 => "Diese Siedlung gibt es nicht.",
    2 => "Diese Siedlung ist schon in der Liste.",
    3 => "Siedlung aus der Liste gel&ouml;scht.",
    4 => "Siedlung konnte nicht aus der Liste entfernt werden.",
    5 => "Verarsch mich nicht!",
    6 => "Datenbank Fehler.");


  // enter something new
  if (isset($params->POST->newEntryName) && $params->POST->newEntryName != "") {
    $messageID = cavebook_newEntry($playerID, $params->POST->newEntryName);
  }
    else
  if (isset($params->POST->x) && isset($params->POST->y) && $params->POST->y > 0 && $params->POST->x > 0) {
    $messageID = cavebook_newEntry_coords($playerID, $params->POST->x, $params->POST->y);
  }
    else
  if (isset($params->POST->id)) {
    $messageID = cavebook_newEntry_id($playerID, $params->POST->id);
  }
    else
  if (isset($params->GET->id)) {
    $messageID = cavebook_newEntry_id($playerID, $params->GET->id);
  }


  // process delete
  else if ($deleteID > 0) {
    $messageID = cavebook_deleteEntry($playerID, $deleteID);
  }


  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/cave_book.ihtml");

  // Show a special message
  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }


  // Getting entries
// call our function
 $cavelist = cavebook_getEntries($playerID);

  // Show the cave table
  for($i = 0; $i < sizeof($cavelist[id]); $i++) {

    $cavename = $cavelist[name][$i]; // the current cavename
    $caveX = $cavelist[x][$i];
    $caveY = $cavelist[y][$i];
    $caveID = $cavelist[id][$i];
    $playerName = $cavelist[playerName][$i];
    $playerID = $cavelist[playerID][$i];

    $tribe = $cavelist[tribe][$i]; // the current tribe
    $tribelink = "<a href=\"main.php?modus=".TRIBE_DETAIL."&tribe=".urlencode(unhtmlentities($tribe))."\" target=\"_blank\">";
    if ($tribe != "") $tribe = "(".$tribe.")";

    $playerName = "&nbsp;&nbsp;-&nbsp;&nbsp;<a href=\"main.php?modus=".PLAYER_DETAIL."&amp;detailID=".$playerID."\" target=\"_blank\">".$playerName."</a>";
    if ($playerID == 0) $playerName = "";
    $cavename = "<a href=\"main.php?modus=".MAP_DETAIL."&amp;targetCaveID=".$caveID."\" target=\"_blank\">".$cavename."</a>";

    $movementLink = "?modus=".MOVEMENT."&targetXCoord=".$caveX."&targetYCoord=".$caveY."&targetCaveName=".unhtmlentities($cavelist[name][$i]);

    tmpl_iterate($template, '/CAVES');

    tmpl_set($template, "CAVES/alternate", ($count++ % 2 ? "alternate" : ""));

    tmpl_set($template, 'CAVES', array('cavename'        => $cavename,
					'tribe'	        => $tribe,
					'playerName'	=> $playerName,
					'tribelink'	=> $tribelink,
					'movementLink'	=> $movementLink,
                                        'caveID'      => $caveID,
                                        'caveX'      => $caveX,
                                        'caveY'      => $caveY,
					'modus_delete'	=> CAVE_BOOK_DELETE));

  }

  if (sizeof($cavelist) < 1) {

    tmpl_set($template, "NOCAVES/dummy", "");

  }

  return tmpl_parse($template);
}
?>
