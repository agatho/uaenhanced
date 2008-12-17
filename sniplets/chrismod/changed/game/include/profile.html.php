<?
/*
 * profile.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function profile_getContent($playerID) {
  global $config, $params, $db, $no_resource_flag;

  $no_resource_flag = 1;

  // try to connect to login db
  if (! $db_login = new DB($config->DB_LOGIN_HOST,
			   $config->DB_LOGIN_USER,
			   $config->DB_LOGIN_PWD,
			   $config->DB_LOGIN_NAME))
	  page_dberror();

  // messages
  $messageText = array (
    0 => "Die Daten wurden erfolgreich aktualisiert.",
    1 => "Das Pa&szlig;wort stimmt nicht mit der Wiederholung &uuml;berein.",
    2 => "Die Daten konnten gar nicht oder zumindest nicht vollst&auml;ndig ".
         "aktualisiert werden.",
    3 => "Das Passwort muss mindestens 4 Zeichen lang sein!",

    5 => "Die Priorit&auml;t mu&szlig; zwischen 0 und 10 liegen!", // ADDED by chris--- for cave sorting
    6 => "Dein Clan ist im Krieg und du kannst darum den Urlaubsmodus nicht aktivieren!", // ADDED by chris--- for urlaubsmodus
    7 => "Du warst erst k&uuml;rzlich im Urlaub und mu&szlig;t mindestens die gleiche Zeitspanne warten!",
    8 => "Fehler beim Aktivieren des Urlaubsmodus",

    10 => "Dieser Clanname ist nicht erlaubt!");
  
  // proccess form data   

  if ($params->POST->data ||
      $params->POST->password) { // insert necessary fields
    $messageID = profile_processUpdate($playerID, 
				       $params->POST->data,
				       $params->POST->password,
				       $params->POST->cave_prio, // ADDED by chris--- for cave sorting: cave_prio
				       $db,
				       $db_login); 
  }

  // get the user data

  if (!($playerData = profile_getPlayerData($playerID, $db, $db_login)))
    page_dberror();
  
  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/profile.ihtml");
   
  // Show a special message

  if (isset($messageID)) { 
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
    page_refreshUserData();    
  }

  // show the profile's data

  tmpl_set($template, 'modus_name', 'modus');
  tmpl_set($template, 'modus_value', USER_PROFILE);

  ////////////// user data //////////////////////

  tmpl_set($template, 'DATA_GROUP/heading', 'Benutzerdaten');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/name',  'Name');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/value', $playerData['game']['name']);
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INFO');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/name',  'Email');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/value', $playerData['game']['email']);
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INFO');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Email 2');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'email2');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $playerData['game']['email2']);
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Geschlecht (m/w)');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'sex');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $playerData['game']['sex']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '1');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '1');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Herkunft');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'origin');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $playerData['game']['origin']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '30');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '30');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Alter');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'age');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $playerData['game']['age']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '2');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '2');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'ICQ#');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'icq');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $playerData['game']['icq']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '15');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '15');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Avatar URL (max ' . AVATAR_X . 'x' . AVATAR_Y . ')');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'avatar');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $playerData['game']['avatar']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '30');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '90');

  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/name',      'Beschreibung');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/dataentry', 'description');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/value',     $playerData['game']['description']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/cols',      '25');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/rows',      '8');


// ADDED by chris--- for cave sorting
  ////////////// cave sorting //////////////////////

$meineHoehlen = getCaves($playerID);

// only show this if there is more than one cave
if (sizeof($meineHoehlen) > 1) {

  tmpl_iterate($template, 'DATA_GROUP');

  tmpl_set($template, 'DATA_GROUP/heading', 'Siedlungspriorit&auml;t (1=hoch, 10=niedrig)');

  $i=0;
  $select = array();
  foreach($meineHoehlen as $key => $value){

    tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      lib_shorten_html($value['name'], 17));
    tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'cave_prio');
    tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', $key);
    tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $meineHoehlen[$key][priority]);
    tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '2');
    tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '2');

    if ($i+1 < sizeof($meineHoehlen)) tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

    $i++;
  }

} // end if

// ---------------------------------------------------------------------------------------------


  ////////////// template //////////////////////
/* DISABLED

  tmpl_iterate($template, 'DATA_GROUP');

  tmpl_set($template, 'DATA_GROUP/heading', 'Template ausw&auml;hlen');

  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/name',      'W&auml;hlen Sie ein Template (erneutes einloggen erforderlich!):');
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataentry', 'template');
  
  $selector = array();
  foreach ($config->template_paths as $key => $value){
    if ($key == $params->SESSION->user['template'])
      array_push($selector, array('value' => $key, 'selected' => "selected", 'text' => $value));
    else
      array_push($selector, array('value' => $key, 'text' => $value));
  }
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/SELECTOR', $selector);
*/

  ////////////// show_unqualified //////////////////////
  tmpl_iterate($template, 'DATA_GROUP');

  tmpl_set($template, 'DATA_GROUP/heading', "Erweiterte Ansicht");

  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/name',      "Sollen auch Einheiten, Erweiterungen etc. angezeigt werden, die noch nicht gebaut werden k&ouml;nnen:");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataentry', 'show_unqualified');
  
  $selector = array();
  $selector[0] = array('value'    => 0,
                       'selected' => $params->SESSION->user['show_unqualified'] == 0 ? "selected" : "",
                       'text'     => "nein");
  $selector[1] = array('value'    => 1,
                       'selected' => $params->SESSION->user['show_unqualified'] == 1 ? "selected" : "",
                       'text'     => "ja");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/SELECTOR', $selector);



  // ADDED by chris--- for urlaubsmod:
  ////////////// urlaub //////////////////////

  tmpl_iterate($template, 'DATA_GROUP');

  tmpl_set($template, 'DATA_GROUP/heading', "Urlaubsmodus");

  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/name',      "Soll der Urlaubsmodus eingeschaltet werden:<br>ACHTUNG: Lest vorher die Hilfe unten genau durch!");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataentry', 'urlaub');
  
  $selector = array();
  $selector[0] = array('value'    => 0,
                       'selected' => $params->SESSION->user['urlaub'] == 0 ? "selected" : "",
                       'text'     => "nein");
  $selector[1] = array('value'    => 1,
                       'selected' => $params->SESSION->user['urlaub'] == 1 ? "selected" : "",
                       'text'     => "ja");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/SELECTOR', $selector);



  // ADDED by chris--- for ticker:
  ////////////// show_ticker //////////////////////
  tmpl_iterate($template, 'DATA_GROUP');

  tmpl_set($template, 'DATA_GROUP/heading', "Nachrichten Ticker");

  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/name',      "Soll der Nachrichten Ticker angezeigt werden:");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataentry', 'show_ticker');
  
  $selector = array();
  $selector[0] = array('value'    => 0,
                       'selected' => $params->SESSION->user['show_ticker'] == 0 ? "selected" : "",
                       'text'     => "nein");
  $selector[1] = array('value'    => 1,
                       'selected' => $params->SESSION->user['show_ticker'] == 1 ? "selected" : "",
                       'text'     => "ja");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/SELECTOR', $selector);


  // ADDED by chris--- for returns:
  ////////////// show_returns //////////////////////
  tmpl_iterate($template, 'DATA_GROUP');

  tmpl_set($template, 'DATA_GROUP/heading', "R&uuml;ckkehrbewegungen");

  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/name',      "Sollen R&uuml;ckkehrbewegungen im Terminkalender angezeigt werden:");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/dataentry', 'show_returns');
  
  $selector = array();
  $selector[0] = array('value'    => 0,
                       'selected' => $params->SESSION->user['show_returns'] == 0 ? "selected" : "",
                       'text'     => "nein");
  $selector[1] = array('value'    => 1,
                       'selected' => $params->SESSION->user['show_returns'] == 1 ? "selected" : "",
                       'text'     => "ja");
  tmpl_set($template, 'DATA_GROUP/ENTRY_SELECTION/SELECTOR', $selector);





  ////////////// gfxpath //////////////////////
  tmpl_iterate($template, 'DATA_GROUP');
  tmpl_set($template, 'DATA_GROUP/heading', "Grafikpack");
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Pfad zum Grafikpack (default:'.DEFAULT_GFX_PATH.'):');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'gfxpath');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $playerData['game']['gfxpath']);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '30');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '200');

  ////////////// password //////////////////////

  tmpl_iterate($template, 'DATA_GROUP');

  tmpl_set($template, 'DATA_GROUP/heading', 'Passwort &Auml;nderung');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/name',      'Neues Passwort');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/dataarray', 'password');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/dataentry', 'password1');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/size',      '15');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/maxlength', '15');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT_PWD');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/name',      'Neues Passwort Wiederholung');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/dataarray', 'password');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/dataentry', 'password2');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/size',      '15');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT_PWD/maxlength', '15');

 ////////////// delete account ////////////////////

  tmpl_set($template, 'DELETE/modus_name', 'modus');
  tmpl_set($template, 'DELETE/modus',      DELETE_ACCOUNT);
  tmpl_set($template, 'DELETE/heading',    'Account L&ouml;schen');
  tmpl_set($template, 'DELETE/text',       'Ich habe keine Lust mehr!');

  return tmpl_parse($template);
}

?>
