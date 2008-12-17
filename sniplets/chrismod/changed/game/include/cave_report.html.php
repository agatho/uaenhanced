<?

/*
 * cave_report.html.php - show details of a cave
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function getCaveDetailsContent($details, $showGiveUp = TRUE, $alternate = FALSE) {

  global
    $resourceTypeList,
    $buildingTypeList,
    $unitTypeList,
    $scienceTypeList,
    $defenseSystemTypeList,
    $params,
    $config,
    $db;

  // give this cave up
  if ($params->POST->caveGiveUpConfirm){

    if (cave_giveUpCave($params->POST->giveUpCaveID, $params->SESSION->user['playerID']))
      return "<p><b>Sie haben sich aus dieser Siedlung zur&uuml;ckgezogen.</b>";
    else
      $message = "<p>Diese Siedlung kann nicht aufgegeben werden.</p>";

  // end beginners protection
  } else if ($params->POST->endProtectionConfirm){
    if (beginner_endProtection($details['caveID'], $params->SESSION->user['playerID'], $db))
      $message = "<p><b>Sie haben den Anf&auml;ngerschutz abgeschaltet.</b>";
    else
      $message = "<p>Sie konnten den Anf&auml;ngerschutz nicht abschalten.</p>";
  }

  $widthCount = 10;

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/cave.ihtml');

  if ($message)
    tmpl_set($template, "/MESSAGE/message", $message);

  // fill give-up form
  if ($showGiveUp) {
    tmpl_context($template, "/GIVE_UP");
    tmpl_set($template, 'ARGUMENT', array(array('arg_name' => "giveUpCaveID", 'arg_value' => $details['caveID']),
                                          array('arg_name' => "modus", 'arg_value' => CAVE_GIVE_UP_CONFIRM)));
    tmpl_set($template, "text", "Siedlung " . $details['name'] . " aufgeben");
  }

  // fill end beginner protection form
  if ($details['protected']) {
    tmpl_iterate($template, "/GIVE_UP");
    tmpl_context($template, "/GIVE_UP");

    tmpl_iterate($template, "ARGUMENT");
    tmpl_set($template, "ARGUMENT/arg_name", "caveID");
    tmpl_set($template, "ARGUMENT/arg_value", $details['caveID']);

    tmpl_iterate($template, "ARGUMENT");
    tmpl_set($template, "ARGUMENT/arg_name", "modus");
    tmpl_set($template, "ARGUMENT/arg_value", END_PROTECTION_CONFIRM);

    tmpl_set($template, "text", "Anf&auml;ngerschutz vorzeitig beenden.");
  }

  // fill cave info template
  tmpl_context($template, ($alternate ? "/CAVE_ALTERNATE" : "/CAVE"));

  if ($details['protected'])
    tmpl_set($template, "PROPERTY/text", 'Anf&auml;ngerschutz aktiv');

  if (! $details['secureCave'] ) {
    tmpl_iterate($template, "PROPERTY");
    tmpl_set($template, "PROPERTY/text", '&uuml;bernehmbar');
  }
  
  if ($details['starting_position'] > 0){
    tmpl_iterate($template, "PROPERTY");
    tmpl_set($template, "PROPERTY/text", 'Hauptsiedlung');
  }

  tmpl_set($template, 'caveID', $details['caveID']);
  tmpl_set($template, 'name', $details['name']);

  tmpl_set($template, 'xCoord', $details['xCoord']);
  tmpl_set($template, 'yCoord', $details['yCoord']);

  // RESOURCES AUSFUELLEN
  $resources = array();
  for ($i = 0; $i < sizeof($resourceTypeList); ++$i){
    $resources[] = array('file' => $resourceTypeList[$i]->dbFieldName,
                         'name'  => $resourceTypeList[$i]->name,
                         'value' => $details[$resourceTypeList[$i]->dbFieldName]);
  }
  if (sizeof($resources)) tmpl_set($template, 'RESOURCES/RESOURCE', $resources);

 // UNITS AUSFUELLEN
  $units = array();
  for ($i = 0; $i < sizeof($unitTypeList); ++$i){
    $value = $details[$unitTypeList[$i]->dbFieldName];
    if ($value != 0)
      $units[] = array('file'  => $unitTypeList[$i]->dbFieldName,
                       'name'  => $unitTypeList[$i]->name,
                       'value' => $value);
  }
  if (sizeof($units)) tmpl_set($template, 'UNITS/UNIT', $units);

  // BUILDINGS AUSFUELLEN
  $addons = array();
  for ($i = 0; $i < sizeof($buildingTypeList); ++$i){
    $value = $details[$buildingTypeList[$i]->dbFieldName];
    if ($value != 0)
      $addons[] = array('file' => $buildingTypeList[$i]->dbFieldName,
                        'name'  => $buildingTypeList[$i]->name,
                        'value' => $value);
  }
  if (sizeof($addons)) tmpl_set($template, 'BUILDINGS/BUILDING', $addons);

  // VERTEIDIGUNG AUSFUELLEN
  $defense = array();
  for ($i = 0; $i < sizeof($defenseSystemTypeList); ++$i){
    $value = $details[$defenseSystemTypeList[$i]->dbFieldName];
    if ($value != 0)
      $defense[] = array('file' => $defenseSystemTypeList[$i]->dbFieldName,
                         'name'  => $defenseSystemTypeList[$i]->name,
                         'value' => $value);
  }
  if (sizeof($defense)) tmpl_set($template, 'DEFENSES/DEFENSE', $defense);

  return tmpl_parse($template);
}


function getAllCavesDetailsContent($meineHoehlen){

  global
    $resourceTypeList,
    $buildingTypeList,
    $unitTypeList,
    $scienceTypeList,
    $defenseSystemTypeList,
    $params,
    $config,
    $db;

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/caves.ihtml');

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
?>
