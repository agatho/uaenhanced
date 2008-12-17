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

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribeDelete_getContent($playerID, $tribe, $confirm) {
  global $config, $db, $no_resource_flag, $params;

  $no_resource_flag = 1;

  // try to connect to login db
  if (! tribe_isLeader($playerID, $tribe, $db))
    page_dberror();

  // proccess form data

  if ($confirm) { // the only necessary field
    $success = tribe_deleteTribe($tribe, $db);

    $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'tribeDeleteResponse.ihtml');

    if ($success) {
      tmpl_set($template, 'message', _('Der Stamm wurde aufgelöst. Alle Mitglieder sind jetzt wieder stammeslos. Das Stammesmenü funktioniert bei allen erst nach dem nächsten einloggen wieder.'));
    }
    else {
      tmpl_set($template, 'message', _('Das löschen des Stammes ist fehlgeschlagen. Bitte wenden Sie sich an das Support Team.'));
    }
    return tmpl_parse($template);
  }

  // Show confirmation request

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'dialog.ihtml');

  tmpl_set($template, 'message', _('Möchten Sie diesen Stamm unwiderruflich löschen? Ihre gesamten Stammesdaten gehen verloren.'));


  tmpl_set($template, 'BUTTON/formname', 'confirm');
  tmpl_set($template, 'BUTTON/text', _('Stamm auflösen'));
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', TRIBE_DELETE);
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'confirm');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', 1);

  tmpl_iterate($template, 'BUTTON');

  tmpl_set($template, 'BUTTON/formname', 'cancel');
  tmpl_set($template, 'BUTTON/text', _('Abbrechen'));
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', TRIBE_ADMIN);

  return tmpl_parse($template);
}

?>
