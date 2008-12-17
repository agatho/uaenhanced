<?
/*
 * delete.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function profile_deleteAccount($playerID, $data) {
  global $config, $db, $no_resource_flag, $params;

  $no_resource_flag = 1;

  // try to connect to login db
  $db_login = new DB($config->DB_LOGIN_HOST,
                     $config->DB_LOGIN_USER,
                     $config->DB_LOGIN_PWD,
                     $config->DB_LOGIN_NAME);
  if (!$db_login) page_dberror();

  // proccess form data   

  if (isset($data->confirm)) { // the only necessary field
    $success = profile_processDeleteAccount($playerID, $db_login); 

    $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/deleteResponse.ihtml');
    if ($success) {
      session_destroy();
      
      tmpl_set($template, 'message', 
	       "Ihr Account wurde zur L&ouml;schung vorgemerkt. ".
	       "Sie sind jetzt ausgeloggt und k&ouml;nnen das Fenster ".
	       "Schlie&szlig;en.");
      tmpl_set($template, 'link', "http://tntchris.dyndns.org/ugaagga/");
    }
    else {
      tmpl_set($template, 'message', 
	       "Das l&ouml;schen Ihres Accounts ist fehlgeschlagen.".
	       "Bitte wenden Sie sich an das Support Team.");
      tmpl_set($template, 'link', "ugastart.php");
    }

    return tmpl_parse($template);
  }

  // Show confirmation request

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/dialog.ihtml");
   
  tmpl_set($template, 'message', 
	   "M&ouml;chten Sie Ihren Account unwiderruflich l&ouml;schen? ".
	   "Ihre gesamten Spieldaten gehen verloren, ein neuerliches ".
	   "einloggen als dieser Spieler ist nicht m&ouml;glich. ".
	   "<p> Allerdings steht Ihnen die Emailadresse anschlie&szlig;end ".
	   "f&uuml;r eine Neuanmeldung zur Verf&uuml;gung.".
	   "<p> Beachten Sie, da&szlig; Ihre Siedlung noch f&uuml;r einige ".
	   "Zeit nach der L&ouml;schung f&uuml;r ander Spieler sichtbar ist, ".
	   "da die L&ouml;schungen aus der Datenbank nur einmal am Tag ".
	   "vorgenommen werden.");	   

  tmpl_set($template, 'BUTTON/formname', 'confirm');
  tmpl_set($template, 'BUTTON/text', 'Account l&ouml;schen');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', DELETE_ACCOUNT);
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'confirm');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', 1);

  tmpl_iterate($template, 'BUTTON'); 

  tmpl_set($template, 'BUTTON/formname', 'cancel');
  tmpl_set($template, 'BUTTON/text', 'Abbrechen');
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', USER_PROFILE);

  return tmpl_parse($template);
}

?>
