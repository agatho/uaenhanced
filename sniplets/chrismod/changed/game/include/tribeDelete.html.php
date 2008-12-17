<?
/*
 * tribeDelete.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function tribeDelete_getContent($playerID, $tribe, $confirm) {
  global $config, $db, $no_resource_flag, $params;

  $no_resource_flag = 1;

  // try to connect to login db
  if (! tribe_isLeader($playerID, $tribe, $db))
    page_dberror();

  // proccess form data   

  if ($confirm) { // the only necessary field
    $success = tribe_deleteTribe($tribe, $db); 

    $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/tribeDeleteResponse.ihtml');

    if ($success) {
      
      tmpl_set($template, 'message', 
	       "Der Clan wurde aufgel&ouml;st. Alle Mitglieder sind jetzt ".
	       "wieder Clanlos. Das Clanmen&uuml; funktioniert bei allen erst ".
               "nach dem n&auml;chsten einloggen wieder.");


    }
    else {
      tmpl_set($template, 'message', 
	       "Das l&ouml;schen des Clans ist fehlgeschlagen.".
	       "Bitte wenden Sie sich an das Support Team.");
    }

    return tmpl_parse($template);
  }

  // Show confirmation request

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/dialog.ihtml");
   
  tmpl_set($template, 'message', 
	   "M&ouml;chten Sie diesen Clan unwiderruflich l&ouml;schen? ".
	   "Ihre gesamten Clandaten gehen verloren. ");	   

  tmpl_set($template, 'BUTTON/formname', 'confirm');
  tmpl_set($template, 'BUTTON/text', 'Clan l&ouml;schen');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', TRIBE_DELETE);
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'confirm');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', 1);

  tmpl_iterate($template, 'BUTTON'); 

  tmpl_set($template, 'BUTTON/formname', 'cancel');
  tmpl_set($template, 'BUTTON/text', 'Abbrechen');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', TRIBE_ADMIN);

  return tmpl_parse($template);
}

?>
