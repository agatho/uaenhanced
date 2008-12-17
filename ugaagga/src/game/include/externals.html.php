<?
/*
 * externals.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');


################################################################################

/**
 *
 */

function externals_builder($caveID, &$cave){
  global $config, $db, $params,
         $buildingTypeList, $defenseSystemTypeList, $resourceTypeList, $unitTypeList;

  // process a cancel-order request
  if (isset($params->POST->eventID)){
    $message = externals_cancelOrder($params->POST->eventID, $caveID, $db);

  // process a demolish request
  } else if (isset($params->POST->breakDownConfirm)){
    $message = externals_performDemolishing($params->POST->externalID, $caveID, $cave, $db);
    $reload = 1;

  // process an order request
  } else if (isset($params->POST->externalID)){
    check_timestamp($params->POST->tstamp);
    $message = externals_performOrder($params->POST->externalID, $caveID, $cave, $db);
    $reload = 1;
  }

  // refresh cave data
  if ($reload){
    $r = getCaveSecure($caveID, $params->SESSION->player->playerID);
    if ($r->isEmpty())
      page_dberror();
    $cave = $r->nextRow();
  }

  // get this cave's queue
  $queue = externals_getQueue($params->SESSION->player->playerID, $caveID);

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'externalBuilder.ihtml');

  // show special messages
  if (isset($message))
    tmpl_set($template, '/MESSAGE/message', $message);

  // show the external table
  for($i = 0; $i < sizeof($defenseSystemTypeList); $i++){

    $external = $defenseSystemTypeList[$i];
    $maxLevel = round(eval('return '.formula_parseToPHP("{$external->maxLevel};", '$cave')));
		$notenough=FALSE;

    $result = rules_checkDependencies($external, $cave);

    // if all requirements are met, but the maxLevel is 0, treat it like a non-buildable
    if ($maxLevel <= 0 && $result === TRUE)
      $result = ($cave[$external->dbFieldName]) ? _('Max. Stufe: 0') : FALSE;

    if ($result === TRUE){

      tmpl_iterate($template, 'DEFENSESYSTEM');

      tmpl_set($template, "DEFENSESYSTEM/alternate", ($count++ % 2 ? "alternate" : ""));

      tmpl_set($template, 'DEFENSESYSTEM',
               array('name'        => $external->name,
                     'dbFieldName' => $external->dbFieldName,
                     'externalID'  => $i,
                     'size'        => "0" + $cave[$external->dbFieldName],
                     'time'        => time_formatDuration(eval('return ' . formula_parseToPHP($external->productionTimeFunction . ";", '$cave')) * DEFENSESYSTEM_TIME_BASE_FACTOR)));

      // iterate ressourcecosts
      foreach ($external->resourceProductionCost as $resourceID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));

        if ($cost){

          tmpl_iterate($template, "DEFENSESYSTEM/RESSOURCECOST");

          if ($cave[$resourceTypeList[$resourceID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/ENOUGH/value", $cost);
          } else {
            tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/dbFieldName", $resourceTypeList[$resourceID]->dbFieldName);
          tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/name",        $resourceTypeList[$resourceID]->name);
        }
      }
      // iterate unitcosts
      foreach ($external->unitProductionCost as $unitID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/UNITCOST");

          if ($cave[$unitTypeList[$unitID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/UNITCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/UNITCOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/UNITCOST/name", $unitTypeList[$unitID]->name);
        }
      }
      // iterate buildingcosts
      foreach ($external->buildingProductionCost as $buildingID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/BUILDINGCOST");

          if ($cave[$buildingTypeList[$buildingID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/name", $buildingTypeList[$buildingID]->name);
        }
      }
      // iterate externalcosts
      foreach ($external->externalProductionCost as $externalID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/EXTERNALCOST");

          if ($cave[$defenseSystemTypeList[$externalID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/name", $defenseSystemTypeList[$externalID]->name);
        }
      }

      // show the break down link
      if ($cave[$external->dbFieldName])
      tmpl_set($template, 'DEFENSESYSTEM/BREAK_DOWN_LINK',
               array('externalID' => $external->defenseSystemID));

      // do not show order link
      if ($queue){
        tmpl_set($template, 'DEFENSESYSTEM/BUILD_LINK_NO/message', _('Ausbau im Gange'));

			} else if ($notenough && $maxLevel > $cave[$external->dbFieldName]) {
        tmpl_set($template, 'DEFENSESYSTEM/BUILD_LINK_NO/message', _('Zu wenig Rohstoffe'));
				

      // show order link
      } else if ($maxLevel > $cave[$external->dbFieldName]){
        tmpl_set($template, 'DEFENSESYSTEM/BUILD_LINK',
                 array('externalID' => $external->defenseSystemID,
                       'tstamp'     => time()));

      // maxlvl reached
      } else {
        tmpl_set($template, '/DEFENSESYSTEM/BUILD_LINK_NO/message', _('Max. Stufe'));
      }

    // can't build but already in cave
    } else if ($cave[$external->dbFieldName]){

      tmpl_iterate($template, '/UNWANTEDDEFENSESYSTEMS/DEFENSESYSTEM');
      tmpl_set($template, '/UNWANTEDDEFENSESYSTEMS/DEFENSESYSTEM',
               array('alternate'       => ($count_unwanted++ % 2 ? "" : "alternate"),
                     'externalID'      => $i,
                     'size'            => $cave[$external->dbFieldName],
                     'dbFieldName'     => $external->dbFieldName,
                     'name'            => $external->name));

      // if building not impossible, show dependencies
      if ($result !== FALSE)
        tmpl_set($template, '/UNWANTEDDEFENSESYSTEMS/DEFENSESYSTEM/dependencies', $result);

    // building not impossible, but DONT show dependencies
    } else if ($result !== FALSE && !$external->nodocumentation){

      tmpl_iterate($template, '/UNQUALIFIEDDEFENSESYSTEMS/DEFENSESYSTEM');
      tmpl_set($template, '/UNQUALIFIEDDEFENSESYSTEMS/DEFENSESYSTEM',
               array('alternate'       => ($count_unqualified++ % 2 ? "" : "alternate"),
                     'externalID' => $i,
                     'name'            => $external->name,
                     'dbFieldName'     => $external->dbFieldName,
                     'dependencies'    => $result));

    }
  }

  // queue
  if ($queue){
    $row = $queue->nextRow();
    tmpl_set($template, 'DEFENSESYSTEM_QUEUE',
             array('name'    => $defenseSystemTypeList[$row['defenseSystemID']]->name,
                   'size'    => $cave[$defenseSystemTypeList[$row['defenseSystemID']]->dbFieldName] + 1,
                   'finish'  => time_formatDatetime($row['end']),
                   'eventID' => $row['event_defenseSystemID']));
  }

  tmpl_set($template, array('rules_path' => RULES_PATH));

  return tmpl_parse($template);
}


################################################################################

/**
 *
 */

function externals_showProperties($cave) {

  global $buildingTypeList, $defenseSystemTypeList, $resourceTypeList,
         $scienceTypeList, $unitTypeList,
         $config, $params;

  $externalID = intval($params->POST->externalID);

  // first check whether that defense should be displayed...
  $defense = $defenseSystemTypeList[$externalID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$defense->maxLevel};", '$cave')));

  if (!$defense || ($defense->nodocumentation &&
                 !$cave[$defense->dbFieldName] &&
                 rules_checkDependencies($defense, $cave) !== TRUE))
    $defense = current($defenseSystemTypeList);

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'externalProperties.ihtml');

  $currentlevel = $cave[$defense->dbFieldName];
  $levels = array();
  for ($level = $cave[$defense->dbFieldName], $count = 0;
       $level < $maxLevel && $count < 6;
       ++$count, ++$level, ++$cave[$defense->dbFieldName]){

    $duration = time_formatDuration(
                  eval('return ' .
                       formula_parseToPHP($defense->productionTimeFunction.";",'$cave'))
                  * DEFENSESYSTEM_TIME_BASE_FACTOR);

    // iterate ressourcecosts
    $resourcecost = array();
    foreach ($defense->resourceProductionCost as $resourceID => $function){

      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));
      if ($cost)
        array_push($resourcecost,
                   array(
                   'name'        => $resourceTypeList[$resourceID]->name,
                   'dbFieldName' => $resourceTypeList[$resourceID]->dbFieldName,
                   'value'       => $cost));
    }
    // iterate unitcosts
    $unitcost = array();
    foreach ($defense->unitProductionCost as $unitID => $function){
      $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$cave')));
      if ($cost)
        array_push($unitcost,
                   array(
                   'name'        => $unitTypeList[$unitID]->name,
                   'dbFieldName' => $unitTypeList[$unitID]->dbFieldName,
                   'value'       => $cost));
    }

    $buildingCost = array();
    foreach ($defense->buildingProductionCost as $key => $value)
      if ($value != "" && $value != 0)
        array_push($buildingCost, array('dbFieldName' => $buildingTypeList[$key]->dbFieldName,
                                        'name'        => $buildingTypeList[$key]->name,
                                        'value'       => ceil(eval('return '.formula_parseToPHP($defense->buildingProductionCost[$key] . ';', '$details')))));

    $externalCost = array();
    foreach ($defense->externalProductionCost as $key => $value)
      if ($value != "" && $value != 0)
        array_push($externalCost, array('dbFieldName' => $defenseSystemTypeList[$key]->dbFieldName,
                                        'name'        => $defenseSystemTypeList[$key]->name,
                                        'value'       => ceil(eval('return '.formula_parseToPHP($defense->externalProductionCost[$key] . ';', '$details')))));

    $levels[$count] = array('level' => $level + 1,
                            'time'  => $duration,
                            'BUILDINGCOST' => $buildingCost,
                            'EXTERNALCOST' => $externalCost,
                            'RESOURCECOST' => $resourcecost,
                            'UNITCOST'     => $unitcost);
  }
  if (sizeof($levels))
    $levels = array('population' => $cave['resource_population'], 'LEVEL' => $levels);


  $dependencies     = array();
  $buildingdep      = array();
  $defensesystemdep = array();
  $resourcedep      = array();
  $sciencedep       = array();
  $unitdep          = array();

  foreach ($defense->buildingDepList as $key => $level)
    if ($level)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($defense->defenseSystemDepList as $key => $level)
    if ($level)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&gt;= " . $level));

  foreach ($defense->resourceDepList as $key => $level)
    if ($level)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&gt;= " . $level));

  foreach ($defense->scienceDepList as $key => $level)
    if ($level)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&gt;= " . $level));

  foreach ($defense->unitDepList as $key => $level)
    if ($level)
      array_push($unitdep, array('name'  => $unitTypeList[$key]->name,
                                 'level' => "&gt;= " . $level));


  foreach ($defense->maxBuildingDepList as $key => $level)
    if ($level != -1)
      array_push($buildingdep, array('name'  => $buildingTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($defense->maxDefenseSystemDepList as $key => $level)
    if ($level != -1)
      array_push($defensesystemdep, array('name'  => $defenseSystemTypeList[$key]->name,
                                          'level' => "&lt;= " . $level));

  foreach ($defense->maxResourceDepList as $key => $level)
    if ($level != -1)
      array_push($resourcedep, array('name'  => $resourceTypeList[$key]->name,
                                     'level' => "&lt;= " . $level));

  foreach ($defense->maxScienceDepList as $key => $level)
    if ($level != -1)
      array_push($sciencedep, array('name'  => $scienceTypeList[$key]->name,
                                    'level' => "&lt;= " . $level));

  foreach ($defense->maxUnitDepList as $key => $level)
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

  tmpl_set($template, '/', array('name'          => $defense->name,
                                 'dbFieldName'   => $defense->dbFieldName,
                                 'description'   => $defense->description,
                                 'maxlevel'      => $maxLevel,
                                 'currentlevel'  => $currentlevel,
                                 'rangeAttack'   => $defense->attackRange,
                                 'arealAttack'   => $defense->attackAreal,
                                 'attackRate'    => $defense->attackRate,
                                 'defenseRate'   => $defense->defenseRate,
                                 'size'          => $defense->hitPoints,
                                 'antiSpyChance' => $defense->antiSpyChance,
                                 'LEVELS'        => $levels,
                                 'DEPGROUP'      => $dependencies,
                                 'rules_path'    => RULES_PATH));


  return tmpl_parse($template);
}


################################################################################

/**
 *
 */

function externals_getQueue($playerID, $caveID) {
  global $db;

  // prepare query
  $query = sprintf("SELECT e.* FROM Event_defenseSystem e ".
                   "LEFT JOIN Cave c ON c.caveID = e.caveID ".
                   "WHERE c.caveID IS NOT NULL AND c.playerID = '%d' ".
                   "AND e.caveID = '%d'", $playerID, $caveID);

  // execute query
  $result = $db->query($query);

  // on error or if nothing is queued, return 0
  if (!$result || $result->isEmpty())
    return 0;
  return $result;
}


################################################################################

/**
 *
 */

function externals_cancelOrder($event_defenseSystemID, $caveID, $db) {

  // prepare query
  $query = sprintf("DELETE FROM Event_defenseSystem ".
                   "WHERE event_defenseSystemID = '%d' AND caveID = '%d'",
                   $event_defenseSystemID, $caveID);

  // execute query
  if($db->query($query))
    return _('Der Arbeitsauftrag wurde erfolgreich gestoppt.');

  return _('Es konnte kein Arbeitsauftrag gestoppt werden.');
}


################################################################################

/**
 *
 */

function externals_demolishingPossible($caveID, $db){

  // prepare query
  $query = sprintf("SELECT toreDownTimeout < NOW()+0 AS possible ".
                   "FROM Cave WHERE caveID = '%d'", $caveID);

  // execute query
  if (!($result = $db->query($query)))
    return 0;

  if (!($row = $result->nextRow()) || ! $row["possible"])
    return 0;

  return 1;
}


################################################################################

/**
 *
 */

function externals_performDemolishing($externalID, $caveID, $cave, $db){

  global $config, $resourceTypeList, $defenseSystemTypeList;

  $dbFieldName = $defenseSystemTypeList[$externalID]->dbFieldName;

  // can't demolish
  if (!externals_demolishingPossible($caveID, $db))
    return sprintf(_('Sie können derzeit kein Gebäude oder Verteidigungen abreissen, weil erst vor Kurzem etwas in dieser Höhle abgerissen wurde. Generell muss zwischen zwei Abrissen eine Zeitspanne von %d Minuten liegen.'), TORE_DOWN_TIMEOUT);

  // no defenseSystem of that type
  if ($cave[$dbFieldName] < 1)
    return _('Sie haben von der Sorte gar keine Gebäude');

  $query = "UPDATE Cave ";
  $where = "WHERE caveID = '$caveID' ".
           "AND {$dbFieldName} > 0 ";

  // add resources gain
  /*
  if (is_array($defenseSystemTypeList[$externalID]->resourceProductionCost)){
    $resources = array();
    foreach ($defenseSystemTypeList[$externalID]->resourceProductionCost as $key => $value){
      if ($value != "" && $value != "0"){
        $formula     = formula_parseToSQL($value);
        $dbField     = $resourceTypeList[$key]->dbFieldName;
        $maxLevel    = round(eval('return '.formula_parseToPHP("{$resourceTypeList[$key]->maxLevel};", '$cave')));
        $resources[] = "$dbField = LEAST($maxLevel, $dbField + ($formula) / {$config->DEFENSESYSTEM_PAY_BACK_DIVISOR})";
      }
    }
    $set .= implode(", ", $resources);
  }
  */

  // ATTENTION: "SET defenseSystem = defenseSystem - 1" has to be placed BEFORE
  //            the calculation of the resource return. Otherwise
  //            mysql would calculate the cost of the NEXT step not
  //            of the LAST defenseSystem step (returns would be too high)...
  $query .= "SET {$dbFieldName} = {$dbFieldName} - 1, ".
            "toreDownTimeout = (NOW() + INTERVAL ".
            TORE_DOWN_TIMEOUT." MINUTE)+0 ";

  if (strlen($set)) $query .= ", $set ";

  if (!$db->query($query.$where) || !$db->affected_rows() == 1)
    return _('Das Gebäude konnte nicht abgerissen werden.');

  return _('Das Gebäude wurde erfolgreich abgerissen.');
}


################################################################################

/**
 *
 */

function externals_performOrder($externalID, $caveID, $cave, $db){

  global $defenseSystemTypeList, $unitTypeList, $buildingTypeList,
         $scienceTypeList, $resourceTypeList;

  $external = $defenseSystemTypeList[$externalID];
  $maxLevel = round(eval('return '.formula_parseToPHP("{$external->maxLevel};", '$cave')));

  $set     = array();
  $setBack = array();
  $where   = array("WHERE caveID = '$caveID'",
                   "{$external->dbFieldName} < $maxLevel");

  // get all the resource costs
  foreach ($external->resourceProductionCost as $key => $value){
    if ($value){
      $formula = formula_parseToSQL($value);
      $dbField = $resourceTypeList[$key]->dbFieldName;
      $set[]     = "{$dbField} = {$dbField} - ({$formula})";
      $setBack[] = "{$dbField} = {$dbField} + ({$formula})";
      $where[]   = "{$dbField} >= ({$formula})";
    }
  }

  // get all the unit costs
  foreach ($external->unitProductionCost as $key => $value){
    if ($value){
      $formula = formula_parseToSQL($value);
      $dbField = $unitTypeList[$key]->dbFieldName;
      $set[]     = "{$dbField} = {$dbField} - ({$formula})";
      $setBack[] = "{$dbField} = {$dbField} + ({$formula})";
      $where[]   = "{$dbField} >= ({$formula})";
    }
  }

  // get all the building costs
  foreach ($external->buildingProductionCost as $key => $value){
    if ($value){
      $formula = formula_parseToSQL($value);
      $dbField = $buildingTypeList[$key]->dbFieldName;
      $set[]     = "{$dbField} = {$dbField} - ({$formula})";
      $setBack[] = "{$dbField} = {$dbField} + ({$formula})";
      $where[]   = "{$dbField} >= ({$formula})";
    }
  }

  // get all the external costs
  foreach ($external->externalProductionCost as $key => $value){
    if ($value){
      $formula = formula_parseToSQL($value);
      $dbField = $defenseSystemTypeList[$key]->dbFieldName;
      $set[]     = "{$dbField} = {$dbField} - ({$formula})";
      $setBack[] = "{$dbField} = {$dbField} + ({$formula})";
      $where[]   = "{$dbField} >= ({$formula})";
    }
  }

  // generate SQL
  if (sizeof($set)){
    $set     = implode(", ", $set);
    $set     = "UPDATE Cave SET $set ";
    $setBack = implode(", ", $setBack);
    $setBack = "UPDATE Cave SET $setBack WHERE caveID = '$caveID'";
  }

  // generate dependencies
  foreach($external->buildingDepList as $key => $value)
    if ($value)
      $where[] = "{$buildingTypeList[$key]->dbFieldName} >= $value";
  foreach($external->maxBuildingDepList as $key => $value)
    if ($value != -1)
      $where[] = "{$buildingTypeList[$key]->dbFieldName} <= $value";

  foreach($external->defenseSystemDepList as $key => $value)
    if ($value)
      $where[] = "{$defenseSystemTypeList[$key]->dbFieldName} >= $value";
  foreach($external->maxDefenseSystemDepList as $key => $value)
    if ($value != -1)
      $where[] = "{$defenseSystemTypeList[$key]->dbFieldName} <= $value";

  foreach($external->resourceDepList as $key => $value)
    if ($value)
      $where[] = "{$resourceTypeList[$key]->dbFieldName} >= $value";
  foreach($external->maxResourceDepList as $key => $value)
    if ($value != -1)
      $where[] = "{$resourceTypeList[$key]->dbFieldName} <= $value";

  foreach($external->scienceDepList as $key => $value)
    if ($value)
      $where[] = "{$scienceTypeList[$key]->dbFieldName} >= $value";
  foreach($external->maxScienceDepList as $key => $value)
    if ($value != -1)
      $where[] = "{$scienceTypeList[$key]->dbFieldName} <= $value";

  foreach($external->unitDepList as $key => $value)
    if ($value)
      $where[] = "{$unitTypeList[$key]->dbFieldName} >= $value";
  foreach($external->maxUnitDepList as $key => $value)
    if ($value != -1)
      $where[] = "{$unitTypeList[$key]->dbFieldName} <= $value";

  $where = implode(" AND ", $where);

  if (!$db->query($set.$where) || !$db->affected_rows() == 1)
    return _('Der Auftrag konnte nicht erteilt werden. Es fehlen die notwendigen Voraussetzungen.');

  // calculate the production time;
  $prodTime = 0;
  if ($time_formula = $external->productionTimeFunction) {
    $time_eval_formula = formula_parseToPHP($time_formula, '$cave');

    $time_eval_formula="\$prodTime=$time_eval_formula;";
    eval($time_eval_formula);
  }

  $prodTime *= DEFENSESYSTEM_TIME_BASE_FACTOR;
  $now = time();
  $query = sprintf("INSERT INTO Event_defenseSystem (caveID, defenseSystemID, ".
                   "`start`, `end`) VALUES (%d, %d, '%s', '%s')",
                   $caveID, $externalID,
                   time_toDatetime($now),
                   time_toDatetime($now + $prodTime));

  if (!$db->query($query)){
    $db->query($setBack);
    return _('Der Auftrag konnte nicht erteilt werden. Es fehlen die notwendigen Voraussetzungen.');
  }
  return _('Der Auftrag wurde erteilt.');
}


################################################################################

/**
 * Show confirmation request
 */

function externals_demolish(){
  global $config, $params, $defenseSystemTypeList;

  // fetch externalID
  $externalID = intval($params->POST->externalID);

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'externalDemolish.ihtml');

  // set name and id
  tmpl_set($template, array('name' => $defenseSystemTypeList[$externalID]->name,
                            'id'   => $externalID));

  // return parsed template
  return tmpl_parse($template);
}
?>
