<?
/*
 * improvement_building_detail.html.php - 
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function improvement_getBuildingDetails($buildingID, $caveData) {

  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $scienceTypeList,
         $unitTypeList,

         $no_resource_flag,
         $config, $params;

  $no_resource_flag = 1;

  // first check whether that building should be displayed...
  $building = $buildingTypeList[$buildingID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$building->maxLevel};", '$caveData')));
  if (!$building || ($building->nodocumentation &&
                 !$caveData[$building->dbFieldName] &&
                 rules_checkDependencies($building, $caveData) !== TRUE))
    $building = current($buildingTypeList);

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'improvement_building_detail.ihtml');


  $currentlevel = $caveData[$building->dbFieldName];
  $levels = array();
  for ($level = $caveData[$building->dbFieldName], $count = 0;
       $level < $maxLevel && $count < 6;
       ++$count, ++$level, ++$caveData[$building->dbFieldName]){

    $duration = time_formatDuration(
                  eval('return ' .
                       formula_parseToPHP($buildingTypeList[$buildingID]->productionTimeFunction.";",'$caveData'))
                  * BUILDING_TIME_BASE_FACTOR);

    // iterate ressourcecosts
    $resourcecost = array();
    foreach ($building->resourceProductionCost as $resourceID => $function){

      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
      if ($cost)
        array_push($resourcecost,
                   array(
                   'name'        => $resourceTypeList[$resourceID]->name,
                   'dbFieldName' => $resourceTypeList[$resourceID]->dbFieldName,
                   'value'       => $cost));
    }
    // iterate unitcosts
    $unitcost = array();
    foreach ($building->unitProductionCost as $unitID => $function){
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
      if ($cost)
        array_push($unitcost,
                   array(
                   'name'        => $unitTypeList[$unitID]->name,
                   'dbFieldName' => $unitTypeList[$unitID]->dbFieldName,
                   'value'       => $cost));
    }

  $buildingCost = array();
  foreach ($building->buildingProductionCost as $key => $value)
    if ($value != "" && $value != 0)
      array_push($buildingCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                      'name'        => $buildingTypeList[$key]->name,
                                      'value'       => ceil(eval('return '.formula_parseToPHP($building->buildingProductionCost[$key] . ';', '$details')))));

  $externalCost = array();
  foreach ($building->externalProductionCost as $key => $value)
    if ($value != "" && $value != 0)
      array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                      'name'        => $defenseSystemTypeList[$key]->name,
                                      'value'       => ceil(eval('return '.formula_parseToPHP($building->externalProductionCost[$key] . ';', '$details')))));

    $levels[$count] = array('level' => $level + 1,
                            'time'  => $duration,
                            'BUILDINGCOST'  => $buildingCost,
                            'EXTERNALCOST'  => $externalCost,
                            'RESOURCECOST' => $resourcecost,
                            'UNITCOST'     => $unitcost);
  }
  if (sizeof($levels))
    $levels = array('population' => $caveData['resource_population'], 'LEVEL' => $levels);


  $dependencies     = array();
  $buildingdep      = array();
  $defensesystemdep = array();
  $resourcedep      = array();
  $sciencedep       = array();
  $unitdep          = array();

  foreach ($building->buildingDepList as $key => $level)
    if ($level)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($building->defenseSystemDepList as $key => $level)
    if ($level)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&gt;= " . $level));

  foreach ($building->resourceDepList as $key => $level)
    if ($level)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($building->scienceDepList as $key => $level)
    if ($level)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&gt;= " . $level));

  foreach ($building->unitDepList as $key => $level)
    if ($level)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&gt;= " . $level));


  foreach ($building->maxBuildingDepList as $key => $level)
    if ($level != -1)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($building->maxDefenseSystemDepList as $key => $level)
    if ($level != -1)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&lt;= " . $level));

  foreach ($building->maxResourceDepList as $key => $level)
    if ($level != -1)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($building->maxScienceDepList as $key => $level)
    if ($level != -1)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&lt;= " . $level));

  foreach ($building->maxUnitDepList as $key => $level)
    if ($level != -1)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&lt;= " . $level));


  if (sizeof($buildingdep))
    array_push($dependencies, array('name' => _('Erweiterungen'),
                                    'DEP'  => $buildingdep));

  if (sizeof($defensesystemdep))
    array_push($dependencies, array('name' => _('Verteidigungsanlagen'),
                                    'DEP'  => $defensesystemdep));

  if (sizeof($resourcedep))
    array_push($dependencies, array('name' => _('Rohstoffe'),
                                    'DEP'  => $resourcedep));

  if (sizeof($sciencedep))
    array_push($dependencies, array('name' => _('Forschungen'),
                                    'DEP'  => $sciencedep));

  if (sizeof($unitdep))
    array_push($dependencies, array('name' => _('Einheiten'),
                                    'DEP'  => $unitdep));

  tmpl_set($template, '/', array('name'          => $building->name,
                                 'dbFieldName'   => $building->dbFieldName,
                                 'description'   => $building->description,
                                 'maxlevel'      => $maxLevel,
                                 'currentlevel'  => $currentlevel,
                                 'LEVELS'        => $levels,
                                 'DEPGROUP'      => $dependencies,
                                 'rules_path'    => RULES_PATH));

  return tmpl_parse($template);

}


?>
