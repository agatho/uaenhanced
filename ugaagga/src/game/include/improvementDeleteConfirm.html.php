<?
/*
 * improvementDeleteConfirm.html.php - 
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function improvement_deleteConfirm($caveID, $buildingID) {
  global $config, $db, $no_resource_flag, $buildingTypeList, $params;

  $no_resource_flag = 1;

  // Show confirmation request

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'dialog.ihtml');
   
  tmpl_set($template, 'message', sprintf(_('Mˆchten Sie 1 %s abreissen?'), $buildingTypeList[$buildingID]->name));	   

  tmpl_set($template, 'BUTTON/formname', 'confirm');
  tmpl_set($template, 'BUTTON/text', _('Abreiﬂen'));
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', IMPROVEMENT_DETAIL);
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'breakDownConfirm');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', 1);
  tmpl_iterate($template, 'BUTTON/ARGUMENT');
  
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'buildingID');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', $buildingID);
  tmpl_iterate($template, 'BUTTON/ARGUMENT');
  
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'caveID');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', $caveID);
  
  tmpl_iterate($template, 'BUTTON'); 

  tmpl_set($template, 'BUTTON/formname', 'cancel');
  tmpl_set($template, 'BUTTON/text', _('Abbrechen'));
  tmpl_set($template, 'BUTTON/modus_name', 'modus');
  tmpl_set($template, 'BUTTON/modus_value', IMPROVEMENT_DETAIL);
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_name', 'caveID');
  tmpl_set($template, 'BUTTON/ARGUMENT/arg_value', $caveID);
    
  return tmpl_parse($template);
}
