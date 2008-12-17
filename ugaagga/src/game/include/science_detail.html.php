<?
/*
 * science_detail.html.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function science_getScienceDetails($scienceID, $caveData) {

  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $scienceTypeList,
         $unitTypeList,

         $no_resource_flag,
         $config, $params;

  $no_resource_flag = 1;

  // first check whether that science should be displayed...
  $science = $scienceTypeList[$scienceID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$science->maxLevel};", '$caveData')));
  if (!$science || ($science->nodocumentation &&
                 !$caveData[$science->dbFieldName] &&
                 rules_checkDependencies($science, $caveData) !== TRUE))
    $science = current($scienceTypeList);

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'science_detail.ihtml');

  $currentlevel = $caveData[$science->dbFieldName];
  $levels = array();
  for ($level = $caveData[$science->dbFieldName], $count = 0;
       $level < $maxLevel && $count < 6;
       ++$count, ++$level, ++$caveData[$science->dbFieldName]){

    $duration = time_formatDuration(
                  eval('return ' .
                       formula_parseToPHP($scienceTypeList[$scienceID]->productionTimeFunction.";",'$caveData'))
                  * BUILDING_TIME_BASE_FACTOR);

    // iterate ressourcecosts
    $resourcecost = array();
    foreach ($science->resourceProductionCost as $resourceID => $function){

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
    foreach ($science->unitProductionCost as $unitID => $function){
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$caveData')));
      if ($cost)
        array_push($unitcost,
                   array(
                   'name'        => $unitTypeList[$unitID]->name,
                   'dbFieldName' => $unitTypeList[$unitID]->dbFieldName,
                   'value'       => $cost));
    }

    $buildingCost = array();
    foreach ($science->buildingProductionCost as $key => $value)
      if ($value != "" && $value != 0)
        array_push($buildingCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                        'name'        => $buildingTypeList[$key]->name,
                                        'value'       => ceil(eval('return '.formula_parseToPHP($science->buildingProductionCost[$key] . ';', '$details')))));

    $externalCost = array();
    foreach ($science->externalProductionCost as $key => $value)
      if ($value != "" && $value != 0)
        array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                        'name'        => $defenseSystemTypeList[$key]->name,
                                        'value'       => ceil(eval('return '.formula_parseToPHP($science->externalProductionCost[$key] . ';', '$details')))));


    $levels[$count] = array('level' => $level + 1,
                            'time'  => $duration,
                            'BUILDINGCOST' => $buildingCost,
                            'EXTERNALCOST' => $externalCost,
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

  foreach ($science->buildingDepList as $key => $level)
    if ($level)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($science->defenseSystemDepList as $key => $level)
    if ($level)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&gt;= " . $level));

  foreach ($science->resourceDepList as $key => $level)
    if ($level)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($science->scienceDepList as $key => $level)
    if ($level)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&gt;= " . $level));

  foreach ($science->unitDepList as $key => $level)
    if ($level)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&gt;= " . $level));


  foreach ($science->maxBuildingDepList as $key => $level)
    if ($level != -1)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($science->maxDefenseSystemDepList as $key => $level)
    if ($level != -1)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&lt;= " . $level));

  foreach ($science->maxResourceDepList as $key => $level)
    if ($level != -1)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($science->maxScienceDepList as $key => $level)
    if ($level != -1)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&lt;= " . $level));

  foreach ($science->maxUnitDepList as $key => $level)
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

  tmpl_set($template, '/', array('name'          => $science->name,
                                 'dbFieldName'   => $science->dbFieldName,
                                 'description'   => $science->description,
                                 'maxlevel'      => $maxLevel,
                                 'currentlevel'  => $currentlevel,
                                 'LEVELS'        => $levels,
                                 'DEPGROUP'      => $dependencies,
                                 'rules_path'    => RULES_PATH));

  return tmpl_parse($template);
}
?>
