<?
/*
 * fortification.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function defenseSystem_getDefenseSystemDetail($caveID, &$details) {
  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $unitTypeList,
         $config,
         $params,
         $db;

  // messages
  $messageText = array(
    0 => "Der Arbeitsauftrag wurde erfolgreich gestoppt.",
    1 => "Es konnte kein Arbeitsauftrag gestoppt werden.",
    2 => "Der Auftrag konnte nicht erteilt werden. Es fehlen die ".
         "notwendigen Voraussetzungen.",
    3 => "Der Auftrag wurde erteilt",
    5 => "Das Geb&auml;ude wurde erfolgreich abgerissen",
    6 => "Das Geb&auml;ude konnte nicht abgerissen werden",
    7 => "Sie haben von der Sorte gar keine Geb&auml;ude");

  // proccess a cancel-order request
  if (isset($params->POST->eventID))
    $messageID = defenseSystem_processOrderCancel($params->POST->eventID, $caveID, $db);

  // proccess a tore down or new order request
  if (isset($params->POST->breakDownConfirm)){
    $messageID = defenseSystem_breakDown($params->POST->defenseSystemID, $caveID, $details, $db);
    $reload = 1;
  } else if (isset($params->POST->defenseSystemID)){
    check_timestamp($params->POST->tstamp);
    $messageID = defenseSystem_processOrder($params->POST->defenseSystemID, $caveID, $details, $db);
    $reload = 1;
  }

  if ($reload){  // this isn't that elegant...
    $r = getCaveSecure($caveID, $params->SESSION->user['playerID']);

    if ($r->isEmpty())
      page_dberror();
    $details = $r->nextRow();
  }

  $queue = defenseSystem_getDefenseSystemQueueForCave($params->SESSION->user['playerID'], $caveID);

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/fortification.ihtml");

  // Show a special message

  if (isset($messageID))
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);


  // Show the defenseSystem table
  for($i = 0; $i < sizeof($defenseSystemTypeList); $i++){

$notenough = FALSE;

    $defenseSystem = $defenseSystemTypeList[$i]; // the current building
    $maxLevel = round(eval('return '.formula_parseToPHP("{$defenseSystem->maxLevel};", '$details')));

    $result = rules_checkDependencies($defenseSystem, $details);

    // if all requirements are met but the maxLevel is 0,
    // treat it like a non-buildable
    if ($maxLevel <= 0 && $result === TRUE)
      $result = ($details[$defenseSystem->dbFieldName]) ? 'Max. Stufe: 0' : FALSE;

    if ($result === TRUE){

      tmpl_iterate($template, 'DEFENSESYSTEM');

      tmpl_set($template, "DEFENSESYSTEM/alternate", ($count++ % 2 ? "alternate" : ""));

      tmpl_set($template, 'DEFENSESYSTEM',
               array('name'            => $defenseSystem->name,
                     'dbFieldName'     => $defenseSystem->dbFieldName,
                     'defenseSystemID' => $i,
                     'modus'           => DEFENSESYSTEM_DETAIL,
                     'caveID'          => $caveID,
'maxlevel' => $maxLevel,
                     'size'            => "0" + $details[$defenseSystem->dbFieldName],
                     'time'            => time_formatDuration(eval('return ' .
                                          formula_parseToPHP($defenseSystem->productionTimeFunction . ";", '$details'))
                                          * DEFENSESYSTEM_TIME_BASE_FACTOR)));

      // iterate ressourcecosts
      foreach ($defenseSystem->resourceProductionCost as $resourceID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){

          tmpl_iterate($template, "DEFENSESYSTEM/RESSOURCECOST");

          if ($details[$resourceTypeList[$resourceID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/ENOUGH/value", $cost);
          } else {
            tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/dbFieldName", $resourceTypeList[$resourceID]->dbFieldName);
          tmpl_set($template, "DEFENSESYSTEM/RESSOURCECOST/name",        $resourceTypeList[$resourceID]->name);
        }
      }
      // iterate unitcosts
      foreach ($defenseSystem->unitProductionCost as $unitID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/UNITCOST");

          if ($details[$unitTypeList[$unitID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/UNITCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/UNITCOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/UNITCOST/name", $unitTypeList[$unitID]->name);
        }
      }
      // iterate buildingcosts
      foreach ($defenseSystem->buildingProductionCost as $buildingID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/BUILDINGCOST");

          if ($details[$buildingTypeList[$buildingID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/name", $buildingTypeList[$buildingID]->name);
        }
      }
      // iterate externalcosts
      foreach ($defenseSystem->externalProductionCost as $externalID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/EXTERNALCOST");

          if ($details[$defenseSystemTypeList[$externalID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/name", $defenseSystemTypeList[$externalID]->name);
        }
      }

      // show the break down link
      if ($details[$defenseSystem->dbFieldName])
      tmpl_set($template, 'DEFENSESYSTEM/BREAK_DOWN_LINK',
               array('action'          => DEFENSESYSTEM_BREAK_DOWN,
                     'defenseSystemID' => $defenseSystem->defenseSystemID,
                     'caveID'          => $caveID));

      // show the improvement link ?!
      if ($queue)
        tmpl_set($template, 'DEFENSESYSTEM/BUILD_LINK_NO/message', "Ausbau im Gange");

      else if ($notenough && $maxLevel > $details[$defenseSystem->dbFieldName])
        tmpl_set($template, 'DEFENSESYSTEM/BUILD_LINK_NO/message', "");

      else if ($maxLevel > $details[$defenseSystem->dbFieldName]){
        tmpl_set($template, 'DEFENSESYSTEM/BUILD_LINK',
                 array('action'          => DEFENSESYSTEM,
                       'defenseSystemID' => $defenseSystem->defenseSystemID,
                       'caveID'          => $caveID,
		       'tstamp'       => "".time()));
      } else
        tmpl_set($template, '/DEFENSESYSTEM/BUILD_LINK_NO/message', "Max. Stufe");

    } else if ($details[$defenseSystem->dbFieldName]){

      tmpl_iterate($template, '/UNWANTEDDEFENSESYSTEMS/DEFENSESYSTEM');
      tmpl_set($template, '/UNWANTEDDEFENSESYSTEMS/DEFENSESYSTEM',
               array('alternate'       => ($count_unwanted++ % 2 ? "" : "alternate"),
                     'modus'           => DEFENSESYSTEM_DETAIL,
                     'defenseSystemID' => $i,
                     'size'            => $details[$defenseSystem->dbFieldName],
                     'dbFieldName'     => $defenseSystem->dbFieldName,
                     'name'            => $defenseSystem->name,
                     'action'          => DEFENSESYSTEM_BREAK_DOWN));

      if ($result !== FALSE)
        tmpl_set($template, '/UNWANTEDDEFENSESYSTEMS/DEFENSESYSTEM/dependencies', $result);

    } else if ($params->SESSION->user['show_unqualified'] && $result !== FALSE && !$defenseSystem->nodocumentation){

      tmpl_iterate($template, '/UNQUALIFIEDDEFENSESYSTEMS/DEFENSESYSTEM');
      tmpl_set($template, '/UNQUALIFIEDDEFENSESYSTEMS/DEFENSESYSTEM',
               array('alternate'       => ($count_unqualified++ % 2 ? "" : "alternate"),
                     'modus'           => DEFENSESYSTEM_DETAIL,
                     'defenseSystemID' => $i,
                     'name'            => $defenseSystem->name,
                     'dbFieldName'     => $defenseSystem->dbFieldName,
                     'dependencies'    => $result));

    }
  }

  // Show the building queue

  if ($queue){
    $row = $queue->nextRow();
    tmpl_set($template, 'DEFENSESYSTEM_QUEUE',
             array('name'    => $defenseSystemTypeList[$row['defenseSystemID']]->name,
                   'size'    => $details[$defenseSystemTypeList[$row['defenseSystemID']]->dbFieldName] + 1,
                   'finish'  => date("d.m.Y H:i:s" , time_timestampToTime($row['event_end'])),
                   'action'  => DEFENSESYSTEM,
                   'eventID' => $row['event_defenseSystemID'],
                   'caveID'  => $caveID));
  }

  return tmpl_parse($template);
}

?>
