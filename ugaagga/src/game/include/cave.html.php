<?
/*
 * cave.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function getCaveDetailsContent($cave_data, $showGiveUp = TRUE, $alternate = FALSE){

  global $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList,
         $defenseSystemTypeList, $params, $config, $db;

  // give this cave up
  if ($params->POST->caveGiveUpConfirm){
    if (cave_giveUpCave($params->POST->giveUpCaveID, $params->SESSION->player->playerID,$params->SESSION->player->tribe))
      return _('Sie haben sich aus dieser Höhle zurückgezogen.');
    else
      $message = _('Diese Höhle kann nicht aufgegeben werden.');
  }

  // end beginners protection
  else if ($params->POST->endProtectionConfirm){
    if (beginner_endProtection($cave_data['caveID'], $params->SESSION->player->playerID, $db)){
      $message = _('Sie haben den Anfängerschutz abgeschaltet.');
      $cave_data['protected'] = 0;
    }
    else $message = _('Sie konnten den Anfängerschutz nicht abschalten.');
  }

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'cave.ihtml');

  if ($message) tmpl_set($template, "/MESSAGE/message", $message);

  // get region data
  $region = getRegionByID($cave_data['regionID']);

  // fill give-up form
  if ($showGiveUp) tmpl_set($template, "GIVE_UP", $cave_data);

  // fill end beginner protection form
  if ($cave_data['protected']) tmpl_set($template, "UNPROTECT/iterate", '');

  // fill cave info template
  tmpl_context($template, ($alternate ? "/CAVE_ALTERNATE" : "/CAVE"));

  // set properties
  $properties = array();
  if ($cave_data['protected'])
    $properties[] = array('text' => _('Anfängerschutz aktiv'));
  if (!$cave_data['secureCave'])
    $properties[] = array('text' => _('übernehmbar'));
  if ($cave_data['starting_position'] > 0)
    $properties[] = array('text' => _('Haupthöhle'));
  if (sizeof($properties)) tmpl_set($template, 'PROPERTY', $properties);

  tmpl_set($template, 'caveID', $cave_data['caveID']);
  tmpl_set($template, 'name',   $cave_data['name']);
  tmpl_set($template, 'xCoord', $cave_data['xCoord']);
  tmpl_set($template, 'yCoord', $cave_data['yCoord']);
  tmpl_set($template, 'region', $region['name']);

  // RESOURCES AUSFUELLEN
  $resources = array();
  foreach ($resourceTypeList as $resource)
    if (!$resource->nodocumentation || ($cave_data[$resource->dbFieldName] > 0))
      $resources[] = array('file'  => $resource->dbFieldName,
                           'name'  => $resource->name,
                           'value' => $cave_data[$resource->dbFieldName]);
  if (sizeof($resources)) tmpl_set($template, 'RESOURCES/RESOURCE', $resources);

  // UNITS AUSFUELLEN
  $units = array();
  foreach ($unitTypeList as $unit){
    $value = $cave_data[$unit->dbFieldName];
    if ($value != 0)
      $units[] = array('file'  => $unit->dbFieldName,
                       'name'  => $unit->name,
                       'value' => $value);
  }
  if (sizeof($units)) tmpl_set($template, 'UNITS/UNIT', $units);

  // BUILDINGS AUSFUELLEN
  $addons = array();
  foreach ($buildingTypeList as $building){
    $value = $cave_data[$building->dbFieldName];
    if ($value != 0)
      $buildings[] = array('file'  => $building->dbFieldName,
                           'name'  => $building->name,
                           'value' => $value);
  }
  if (sizeof($buildings)) tmpl_set($template, 'BUILDINGS/BUILDING', $buildings);

  // VERTEIDIGUNG AUSFUELLEN
  $defenses = array();
  foreach ($defenseSystemTypeList as $defense){
    $value = $cave_data[$defense->dbFieldName];
    if ($value != 0)
      $defenses[] = array('file'  => $defense->dbFieldName,
                          'name'  => $defense->name,
                          'value' => $value);
  }
  if (sizeof($defenses)) tmpl_set($template, 'DEFENSES/DEFENSE', $defenses);

  return tmpl_parse($template);
}

function getAllCavesDetailsContent($meineHoehlen){

  global $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList,
         $defenseSystemTypeList, $params, $config, $db;

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'caves.ihtml');

  $mycaves = array();
  foreach ($meineHoehlen AS $caveID => $caveDetails){
    $mycaves[] = array('cave_name_url' => urlencode($caveDetails['name']),
                       'cave_name'     => $caveDetails['name'],
                       'cave_id'       => $caveID,
                       'cave_x'        => $caveDetails['xCoord'],
                       'cave_y'        => $caveDetails['yCoord']);
  }

  $sum = 0;
  $alt = 0;
  $myres = array();
  foreach ($resourceTypeList AS $resource){
    $temp = array('alternate'            => $alt % 2,
                  'resource_name'        => $resource->name,
                  'resource_dbFieldName' => $resource->dbFieldName,
                  'CAVE'                 => array());

    $row_sum       = 0;
    $row_sum_delta = 0;
    foreach ($meineHoehlen AS $caveID => $caveDetails){
      $amount = $caveDetails[$resource->dbFieldName];
      $delta = $caveDetails[$resource->dbFieldName.'_delta'];
      $row_sum       += $amount;
      $row_sum_delta += $delta;
      if ($delta >= 0) $delta = "+" . $delta;
      $temp['CAVE'][] = array('amount' => $amount, 'delta' => $delta);
    }
    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum']       = $row_sum;
    if ($row_sum_delta >= 0) $row_sum_delta = "+" . $row_sum_delta;
    $temp['sum_delta'] = $row_sum_delta;
    $myres[] = $temp;
  }

  if ($sum > 0){
    tmpl_set($template, '/RESOURCES/CAVES_HEADER', $mycaves);
    tmpl_set($template, '/RESOURCES/RESOURCE', $myres);
  }

  $sum = 0;
  $alt = 0;
  $myunits = array();
  foreach ($unitTypeList AS $unit){
    $temp = array('alternate'            => $alt % 2,
                  'unit_name'            => $unit->name,
                  'unit_dbFieldName'     => $unit->dbFieldName,
                  'CAVE' => array());
    $row_sum = 0;
    foreach ($meineHoehlen AS $caveID => $caveDetails){
      $amount = $caveDetails[$unit->dbFieldName];
      $row_sum += $amount;
      $temp['CAVE'][] = array('amount' => $amount);
    }
    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum'] = $row_sum;
    $myunits[] = $temp;
  }

  if ($sum > 0){
    tmpl_set($template, '/UNITS/CAVES_HEADER', $mycaves);
    tmpl_set($template, '/UNITS/UNIT', $myunits);
  }

  $sum = 0;
  $alt = 0;
  $mybuildings = array();
  foreach ($buildingTypeList AS $building){
    $temp = array('alternate'            => $alt % 2,
                  'building_name'        => $building->name,
                  'building_dbFieldName' => $building->dbFieldName,
                  'CAVE'                 => array());
    $row_sum = 0;
    foreach ($meineHoehlen AS $caveID => $caveDetails){
      $amount = $caveDetails[$building->dbFieldName];
      $row_sum += $amount;
      $temp['CAVE'][] = array('amount' => $amount);
    }
    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum'] = $row_sum;
    $mybuildings[] = $temp;
  }

  if ($sum > 0){
    tmpl_set($template, '/BUILDINGS/CAVES_HEADER', $mycaves);
    tmpl_set($template, '/BUILDINGS/BUILDING', $mybuildings);
  }

  $sum = 0;
  $alt = 0;
  $myexternals = array();
  foreach ($defenseSystemTypeList AS $external){
    $temp = array('alternate'            => $alt % 2,
                  'external_name'        => $external->name,
                  'external_dbFieldName' => $external->dbFieldName,
                  'CAVE'                 => array());
    $row_sum = 0;
    foreach ($meineHoehlen AS $caveID => $caveDetails){
      $amount = $caveDetails[$external->dbFieldName];
      $row_sum += $amount;
      $temp['CAVE'][] = array('amount' => $amount);
    }
    if (!$row_sum) continue;
    $alt++;
    $sum += $row_sum;
    $temp['sum'] = $row_sum;
    $myexternals[] = $temp;
  }

  if ($sum > 0){
    tmpl_set($template, '/EXTERNALS/CAVES_HEADER', $mycaves);
    tmpl_set($template, '/EXTERNALS/EXTERNAL', $myexternals);
  }

  return tmpl_parse($template);
}

function cave_giveUpCave($caveID, $playerID, $tribe){
  global $db, $relationList;
  $query = "UPDATE Cave SET playerID = 0, takeoverable = 2, ".
           "protection_end = NOW()+0, secureCave = 0 ".
           "WHERE playerID = '$playerID' AND ".
           "caveID = '$caveID' AND ".
           "starting_position = 0";
  if (!$db->query($query)) return 0;
  if (!$db->affected_rows()) return 0;

  $query = "UPDATE `Cave` c SET name = (SELECT name FROM `Cave_Orginalname` co WHERE co.caveID = '{$caveID}' ) WHERE c.caveID='{$caveID}'";
  @$db->query($query);
  @unlink("/var/www/speed/images/temp/{$caveID}.png");

  // delete all scheduled Events
  //   Event_movement - will only be deleted, when a new player gets that cave
  //   Event_artefact - can't be deleted, as it would result in serious errors
  //   Event_wonder   - FIX ME: don't know
  $db->query("DELETE FROM Event_defenseSystem WHERE caveID = '$caveID'");
  $db->query("DELETE FROM Event_expansion WHERE caveID = '$caveID'");
  $db->query("DELETE FROM Event_science WHERE caveID = '$caveID'");
  $db->query("DELETE FROM Event_unit WHERE caveID = '$caveID'");

  if ($tribe!='') {
    $ownRelations = relation_getRelationsForTribe($tribe, $db);
    foreach ($ownRelations['own'] as $actRelation) {
   	  $ownType = $actRelation['relationType'];
   	  if ($relationList[$ownType]['isPrepareForWar'] || $relationList[$ownType]['isWar']) {
	    $newfame = $actRelation['fame'] - (NONSECURE_CAVE_VAlUE * NONSECURE_CAVE_GIVEUP_FAKTOR);  
	    $query = "UPDATE Relation SET fame = '".$newfame ."' ".
	             "WHERE tribe =  '".$actRelation['tribe']."' AND ".
	             "tribe_target  = '".$actRelation['tribe_target']."'";
	    $db->query($query);         
   	  }
    }
  }

  return 1;
}

function cave_giveUpConfirm($cave_data){
  global $config, $db, $params;

  // Show confirmation request
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'dialog.ihtml');

  tmpl_set($template, 'message', sprintf(_('Möchten Sie die Höhle %s wirklich aufgeben? Sie verlieren die Kontrolle über alle Rohstoffe und alle Einheiten, die sich hier befinden!'), $cave_data['name']));

  $buttons = array();

  // give-up button
  $buttons[] = array('formname'    => 'confirm',
                     'text'        => _('Aufgeben'),
                     'modus_name'  => 'modus',
                     'modus_value' => CAVE_DETAIL,
                     'ARGUMENT'    => array(
                                        array('arg_name'  => 'caveGiveUpConfirm',
                                              'arg_value' => 1,
                                              ),
                                        array('arg_name'  => 'giveUpCaveID',
                                              'arg_value' => $cave_data['caveID'],
                                              )));
  // cancel button
  $buttons[] = array('formname'    => 'cancel',
                     'text'        => _('Abbrechen'),
                     'modus_name'  => 'modus',
                     'modus_value' => CAVE_DETAIL);

  tmpl_set($template, 'BUTTON', $buttons);
  return tmpl_parse($template);
}

function beginner_endProtectionConfirm($cave_data){
  global $config, $db, $params;

  // Show confirmation request
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'dialog.ihtml');

  tmpl_set($template, 'message', sprintf(_('Möchten Sie den Anfängerschutz in Höhle %s wirklich unwiderruflich aufgeben? Sie können dann ab sofort angreifen, aber auch angegriffen werden!'), $cave_data['name']));

  $buttons = array();

  // unprotect button
  $buttons[] = array('formname'    => 'confirm',
                     'text'        => _('Anfängerschutz beenden'),
                     'modus_name'  => 'modus',
                     'modus_value' => CAVE_DETAIL,
                     'ARGUMENT'    => array(
                                        array('arg_name'  => 'endProtectionConfirm',
                                              'arg_value' => 1,
                                              ),
                                        array('arg_name'  => 'caveID',
                                              'arg_value' => $cave_data['caveID'],
                                              )));
  // cancel button
  $buttons[] = array('formname'    => 'cancel',
                     'text'        => _('Abbrechen'),
                     'modus_name'  => 'modus',
                     'modus_value' => CAVE_DETAIL);

  tmpl_set($template, 'BUTTON', $buttons);
  return tmpl_parse($template);
}
?>
