<?
/*
 * message_book.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function show_adressbook($playerID, $deleteID) {
  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $unitTypeList,
         $config,
         $params,
         $db;

  // messages
  $messageText = array (
    0 => "Spieler wurde eingetragen.",
    1 => "Es gibt keinen Spieler mit diesem Namen.",
    2 => "Dieser Spieler ist schon in der Liste.",
    3 => "Spieler aus der Liste gel&ouml;scht.",
    4 => "Spieler konnte nicht aus der Liste entfernt werden.",
    5 => "Verarsch mich nicht!",
    6 => "Datenbank Fehler.");


  // enter something new
  if (isset($params->POST->empfaenger)) {
    $messageID = book_newEntry($playerID, $params->POST->empfaenger);
  }

  if (isset($params->POST->newEntryName)) {
    $messageID = book_newEntry($playerID, $params->POST->newEntryName);
  }



  // process delete
  else if ($deleteID > 0) {
    $messageID = book_deleteEntry($playerID, $deleteID);
  }


  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/message_book.ihtml");

  // Show a special message
  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }


  // Getting entries
// call our function
 $playerlist = book_getEntries($playerID);

  // Show the player table
  for($i = 0; $i < sizeof($playerlist[id]); $i++) {

    $playername = $playerlist[name][$i]; // the current playername
    $tribe = $playerlist[tribe][$i]; // the current tribe
    $tribelink = "<a href=\"main.php?modus=".TRIBE_DETAIL."&tribe=".urlencode(unhtmlentities($tribe))."\" target=\"_blank\">";
    if ($tribe != "") $tribe = "(".$tribe.")";

    $playerID = $playerlist[id][$i];
    $link = "<a href=\"main.php?modus=".NEW_MESSAGE."&amp;playerID=".$playername."\">";

    tmpl_iterate($template, '/PLAYER');

    tmpl_set($template, "PLAYER/alternate", ($count++ % 2 ? "alternate" : ""));

    if ($playername != "Spieler nicht auffindbar") tmpl_set($template, "PLAYER/LINK/link", $link);

    tmpl_set($template, 'PLAYER', array('name'        => $playername,
					'tribe'	        => $tribe,	
					'tribelink'	=> $tribelink,
                                        'playerID'      => $playerID,
					'modus'		=> NEW_MESSAGE,
					'modus_delete'	=> MESSAGE_BOOK_DELETE));

  }

  if (sizeof($playerlist) < 1) {

    tmpl_set($template, "NOPLAYER/dummy", "");

  }

  return tmpl_parse($template);
}
?>
