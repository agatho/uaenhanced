<?
/*
 * ticker_entry.html.php - show ticker messages
 * Copyright (c) 2004  chris---
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function ticker_newEntry($caveID){
  global $params, $config, $no_resource_flag;
  $no_resource_flag = 1;

$allowed = playerEntryAllowed($params->SESSION->user['playerID']);

if ($allowed < 2) {

  $nachricht  = nl2br($params->POST->nachricht);

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/ticker_entry.ihtml');

  tmpl_set($template, array('sender'     => $params->SESSION->user['name'],
			    'block_time' => TICKER_BLOCK_TIME,
			    'max_chars'  => TICKER_MAX_CHARS,
			    'nachricht'  => $nachricht));

  tmpl_set($template, 'HIDDEN', array(array('arg' => "playerID", 'value' => $params->SESSION->user['playerID']),
                                      array('arg' => "modus",  'value' => NEW_TICKER_MESSAGE_RESPONSE)));

  return tmpl_parse($template);

} else {

// Player is not allowed to enter something

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/tickerResponse.ihtml');

    $t = $allowed;    
    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};

$message = "Um Spam zu vermeiden ist das Eintragen nur alle ".TICKER_BLOCK_TIME." Minuten erlaubt!<br>Dein letzter Eintrag war am ".$time;

  tmpl_set($template, 'success', $message);

  return tmpl_parse($template);

} // end if

}


function ticker_sendMessage($caveID) {

  global $no_resource_flag, $params, $config;
  $no_resource_flag = 1;

$laenge = strlen($params->POST->nachricht);
if ($laenge > TICKER_MAX_CHARS) {

// Zuviele Zeichen
  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/tickerResponse.ihtml');

  $message = "Ihre Nachricht ist mit ".$laenge." Zeichen zu lang!<br>Maximal erlaubt sind ".TICKER_MAX_CHARS." Zeichen!";
  tmpl_set($template, 'success', $message);

  $backlink = "<br><input type=\"submit\" name=\"senden\" value=\"Zur&uuml;ck zur Nachricht\"><br>";
  tmpl_set($template, 'backlink', $backlink);

  $hidden = "<input type=\"hidden\" name=\"nachricht\" value=\"".$params->POST->nachricht."\">";
  tmpl_set($template, 'hidden', $hidden);

  $action = "main.php?modus=".TICKER_ENTRY;
  tmpl_set($template, 'action', $action);

  return tmpl_parse($template);
}


if ($laenge < 8) {

// Nachricht zu kurz
  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/tickerResponse.ihtml');

  $message = "Ihre Nachricht erscheint nicht sinnvoll!";
  tmpl_set($template, 'success', $message);

  $backlink = "<br><input type=\"submit\" name=\"senden\" value=\"Zur&uuml;ck zur Nachricht\"><br>";
  tmpl_set($template, 'backlink', $backlink);

  $hidden = "<input type=\"hidden\" name=\"nachricht\" value=\"".$params->POST->nachricht."\">";
  tmpl_set($template, 'hidden', $hidden);

  $action = "main.php?modus=".TICKER_ENTRY;
  tmpl_set($template, 'action', $action);

  return tmpl_parse($template);
}



  $nachricht  = nl2br($params->POST->nachricht);

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/tickerResponse.ihtml');

  if (ticker_insertMessageIntoDB($nachricht, $params->SESSION->user['playerID']))  {
    tmpl_set($template, 'success', 'Ihre Nachricht wurde verschickt!');
  } else {
    tmpl_set($template, 'success', 'Fehler! Nachricht konnte nicht verschickt werden!');
  }

  return tmpl_parse($template);
}


?>