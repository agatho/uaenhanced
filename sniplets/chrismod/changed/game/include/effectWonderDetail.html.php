<?
/*
 * effectWonderDetail.html.php - show active effects
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function effect_getEffectWonderDetailContent($caveID, $caveData){

  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $scienceTypeList,
         $unitTypeList,
         $wonderTypeList,
         $effectTypeList,
	 $terrainList, // ADDED by chris--- for terrain effects
         $config,
         $params,
         $db;

  // don't show the resource bar
  $no_resource_flag = 1;

  // open the template
  $template = @tmpl_open('./templates/' . $config->template_paths[$params->SESSION->user['template']] . '/effectWonderDetail.ihtml');

  $wonders = wonder_getActiveWondersForCaveID($caveID, $db);
  $wondersData = array();
  if ($wonders){
    foreach ($wonders AS $key => $data){
      $wonderData = array("name"  =>$wonderTypeList[$data['wonderID']]->name,
                          "end"   =>$data['end_time']);
      $effectsData = array();

      // iterating through effectTypes
      foreach ($effectTypeList AS $effect)
        if ($value = $data[$effect->dbFieldName] + 0)
          $effectsData[] = array("name"  => $effect->name,
                                 "value" => ($value > 0 ? "+" : "") . $value);

      // iterating through resourceTypes
      foreach ($resourceTypeList AS $resource)
        if ($value = $data[$resource->dbFieldName] + 0)
          $effectsData[] = array("name"  => $resource->name,
                                 "value" => ($value > 0 ? "+" : "") . $value);

      // iterating through buildingTypes
      foreach ($buildingTypeList AS $building)
        if ($value = $data[$building->dbFieldName] + 0)
          $effectsData[] = array("name"  => $building->name,
                                 "value" => ($value > 0 ? "+" : "") . $value);

      // iterating through scienceTypes
      foreach ($scienceTypeList AS $science)
        if ($value = $data[$science->dbFieldName] + 0)
          $effectsData[] = array("name"  => $science->name,
                                 "value" => ($value > 0 ? "+" : "") . $value);

      // iterating through unitTypes
      foreach ($unitTypeList AS $unit)
        if ($value = $data[$unit->dbFieldName] + 0)
          $effectsData[] = array("name"  => $unit->name,
                                 "value" => ($value > 0 ? "+" : "") . $value);

      // iterating through defenseSystemTypes
      foreach ($defenseSystemTypeList AS $defenseSystem)
        if ($value = $data[$defenseSystem->dbFieldName] + 0)
          $effectsData[] = array("name"  => $defenseSystem->name,
                                 "value" => ($value > 0 ? "+" : "") . $value);

      $wonderData['EFFECT'] = $effectsData;

      $wondersData[] = $wonderData;
    } // end iterating through active wonders
  }

// ADDED by chris--- for terrain effects ****************************

	global $effect, $effectname;
	$terraineffectData = array();
      $cave = getCaveByID($caveID);
	$terrainvalue = $cave['terrain'];

      $terraineffectsData[] = array("name"  => $terrainList[$terrainvalue]['name'].":",
                             "value" => "");

for ($t=0;$t<count($effect[$terrainvalue]);$t++) {
	$effectvalue = explode(" = ", $effect[$terrainvalue][$t]);
	$effectkey[$t] = $effectvalue[0];
	$effectv[$t] = substr($effectvalue[1],1,-1);
      $terraineffectsData[] = array("name"  => $effectname[$terrainvalue][$t],
                             "value" => $effectv[$t]);
}

// *******************************************************************

// ADDED by chris--- for leadership

$leadermultiplier = 0;
if ($caveData['science_faith'] > 0) $leadermultiplier = ($caveData['building_leader']*0.05)+1 * ($caveData['building_betterleader']*0.1)+1;
if ($caveData['science_darkness'] > 0) $leadermultiplier = ($caveData['building_orcleader']*0.05)+1 * ($caveData['building_betterorcleader']*0.1)+1;
if ($caveData['science_hex'] > 0) $leadermultiplier = ($caveData['building_soeldnerleader']*0.05)+1 * ($caveData['building_bettersoeldnerleader']*0.1)+1;

// -----------------------------------------

  $effectsData = array();
  foreach ($effectTypeList AS $data){
    $value = $caveData[$data->dbFieldName] + 0;

// ADDED by chris--- for leadership

if ($leadermultiplier > 0) {
  if ($data->dbFieldName == "effect_rangeattack_factor") {
    if ($value > 0) $value = $value * $leadermultiplier;
      else $value = $leadermultiplier-1;
  }
  if ($data->dbFieldName == "effect_arealattack_factor") {
    if ($value > 0) $value = $value * $leadermultiplier;
      else $value = $leadermultiplier-1;
  }
  if ($data->dbFieldName == "effect_attackrate_factor") {
    if ($value > 0) $value = $value * $leadermultiplier;
      else $value = $leadermultiplier-1;
  }
  if ($data->dbFieldName == "effect_defenserate_factor") {
    if ($value > 0) $value = $value * $leadermultiplier;
      else $value = $leadermultiplier-1;
  }
  if ($data->dbFieldName == "effect_ranged_damage_resistance_factor") {
    if ($value > 0) $value = $value * $leadermultiplier;
      else $value = $leadermultiplier-1;
  }
}

// ---------------------------------------------


for ($t=0;$t<count($effect[$terrainvalue]);$t++) {
  if ($data->dbFieldName == $effectkey[$t]) {
    $value = $value - $effectv[$t];
  }
}
    if ($value) {
      $effectsData[] = array("name"  => $data->name,
                             "value" => $value);
    }
  } // end iterating through effectTypes

  $data = array();
  if (!sizeof($wondersData))
    $data['NOWONDER'] = array('dummy' => "");
  else
    $data['WONDER'] = $wondersData;

  if (!sizeof($effectsData))
    $data['NOEFFECT'] = array('dummy' => "");
  else
    $data['EFFECT'] = $effectsData;

  if (sizeof($terraineffectsData) < 2)
    $data['TERRAINNOEFFECT'] = array('dummy' => "");
  else
    $data['TERRAINEFFECT'] = $terraineffectsData;

  tmpl_set($template, "/", $data );

  return tmpl_parse($template);
}
?>