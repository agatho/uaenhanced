<?
/*
 * improvement.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function improvement_getImprovementDetail($caveID, &$details){
  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $unitTypeList,
         $config,
         $params,
         $db;

  // messages
  $messageText = array (
    0 => "Der Arbeitsauftrag wurde erfolgreich gestoppt.",
    1 => "Es konnte kein Arbeitsauftrag gestoppt werden.",
    2 => "Der Auftrag konnte nicht erteilt werden. Es fehlen die ".
         "notwendigen Voraussetzungen.",
    3 => "Der Auftrag wurde erteilt",
    5 => "Das Geb&auml;ude wurde erfolgreich abgerissen",
    6 => "Das Geb&auml;ude konnte nicht abgerissen werden",
    7 => "Sie haben von der Sorte gar keine Geb&auml;ude",
    8 => "Sie k&ouml;nnen derzeit kein Geb&auml;ude abreissen, weil erst vor Kurzem etwas in dieser Siedlung abgerissen wurde. Generell muss zwischen zwei Abrissen eine Zeitspanne von ".TORE_DOWN_TIMEOUT." Minuten liegen."
    );

  // proccess a cancel-order request
  if (isset($params->POST->eventID))
    $messageID = improvement_processOrderCancel($params->POST->eventID, $caveID, $db);

  // proccess a tore down or new order request
  if (isset($params->POST->breakDownConfirm)){
    $messageID = improvement_breakDown($params->POST->buildingID, $caveID, $details, $db);
    $reload = 1;

  } else if (isset($params->POST->buildingID)){
    $messageID = improvement_processOrder($params->POST->buildingID, $caveID, $details, $db);
    $reload = 1;
  }

  if ($reload){  // this isn't that elegant...
    $r = getCaveSecure($caveID, $params->SESSION->user['playerID']);

    if ($r->isEmpty())
      page_dberror();
    $details = $r->nextRow();
  }

  $queue = improvement_getImprovementQueueForCave($params->SESSION->user['playerID'], $caveID);

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/improvement.ihtml");

  // Show a special message

  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }

  // Show the improvement table
  for ($i = 0; $i < sizeof($buildingTypeList); $i++){

$notenough = FALSE;

    $building = $buildingTypeList[$i]; // the current building
    $maxLevel = round(eval('return '.formula_parseToPHP("{$building->maxLevel};", '$details')));

    $result = rules_checkDependencies($building, $details);
    if ($result === TRUE){

      tmpl_iterate($template, 'IMPROVEMENT');

      tmpl_set($template, "IMPROVEMENT/alternate", ($count++ % 2 ? "alternate" : ""));

      tmpl_set($template, 'IMPROVEMENT',
               array('name'       => $building->name,
                     'dbFieldName'=> $building->dbFieldName,
                     'buildingID' => $i,
                     'modus'      => IMPROVEMENT_BUILDING_DETAIL,
                     'caveID'     => $caveID,
'maxlevel' => $maxLevel,
                     'size'       => "0" + $details[$building->dbFieldName],
                     'time'       => time_formatDuration(eval('return ' .
                                     formula_parseToPHP($building->productionTimeFunction . ";", '$details'))
                                     * BUILDING_TIME_BASE_FACTOR)));

      // iterate ressourcecosts
      foreach ($building->resourceProductionCost as $resourceID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){

          tmpl_iterate($template, "IMPROVEMENT/RESSOURCECOST");

          if ($details[$resourceTypeList[$resourceID]->dbFieldName] >= $cost){
            tmpl_set($template, "IMPROVEMENT/RESSOURCECOST/ENOUGH/value", $cost);
          } else {
            tmpl_set($template, "IMPROVEMENT/RESSOURCECOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "IMPROVEMENT/RESSOURCECOST/dbFieldName", $resourceTypeList[$resourceID]->dbFieldName);
          tmpl_set($template, "IMPROVEMENT/RESSOURCECOST/name",        $resourceTypeList[$resourceID]->name);
        }
      }
      // iterate unitcosts
      foreach ($building->unitProductionCost as $unitID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "IMPROVEMENT/UNITCOST");

          if ($details[$unitTypeList[$unitID]->dbFieldName] >= $cost){
            tmpl_set($template, "IMPROVEMENT/UNITCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "IMPROVEMENT/UNITCOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "IMPROVEMENT/UNITCOST/name", $unitTypeList[$unitID]->name);
        }
      }



      // iterate buildingcosts
/*
      foreach ($building->buildingProductionCost as $buildingID => $function){

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
*/


      // iterate buildingcosts
      foreach ($building->buildingProductionCost as $buildingID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "IMPROVEMENT/BUILDINGCOST");

          if ($details[$buildingTypeList[$buildingID]->dbFieldName] >= $cost){
            tmpl_set($template, "IMPROVEMENT/BUILDINGCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "IMPROVEMENT/BUILDINGCOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "IMPROVEMENT/BUILDINGCOST/name", $buildingTypeList[$buildingID]->name);
        }
      }


      // iterate externalcosts
      foreach ($building->externalProductionCost as $externalID => $function){

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
      tmpl_set($template, 'IMPROVEMENT/BREAK_DOWN_LINK',
               array('action'     => IMPROVEMENT_BREAK_DOWN,
                     'buildingID' => $building->buildingID,
                     'caveID'     => $caveID));

      // show the building link ?!
      if ($queue)
        tmpl_set($template, 'IMPROVEMENT/BUILD_LINK_NO/message', "Ausbau im Gange");

      else if ($notenough && $maxLevel > $details[$building->dbFieldName])
        tmpl_set($template, 'IMPROVEMENT/BUILD_LINK_NO/message', "");

      else if ($maxLevel > $details[$building->dbFieldName]){
        tmpl_set($template, 'IMPROVEMENT/BUILD_LINK',
                 array('action'     => IMPROVEMENT_DETAIL,
                       'buildingID' => $building->buildingID,
                       'caveID'     => $caveID));
      } else
        tmpl_set($template, '/IMPROVEMENT/BUILD_LINK_NO/message', "Max. Stufe");

    } else if ($details[$building->dbFieldName]){

      tmpl_iterate($template, '/UNWANTEDIMPROVEMENTS/IMPROVEMENT');
      tmpl_set($template, '/UNWANTEDIMPROVEMENTS/IMPROVEMENT',
               array('alternate'    => ($count_unwanted++ % 2 ? "" : "alternate"),
                     'modus'        => IMPROVEMENT_BUILDING_DETAIL,
                     'buildingID'   => $i,
                     'caveID'       => $caveID,
                     'size'         => $details[$building->dbFieldName],
                     'dbFieldName'  => $building->dbFieldName,
                     'name'         => $building->name,
                     'action'       => IMPROVEMENT_BREAK_DOWN));
      if ($result !== FALSE)
        tmpl_set($template, '/UNWANTEDIMPROVEMENTS/IMPROVEMENT/dependencies', $result);

    } else if ($params->SESSION->user['show_unqualified'] && $result !== FALSE && !$building->nodocumentation){

      tmpl_iterate($template, '/UNQUALIFIEDIMPROVEMENTS/IMPROVEMENT');
      tmpl_set($template, '/UNQUALIFIEDIMPROVEMENTS/IMPROVEMENT',
               array('alternate'    => ($count_unqualified++ % 2 ? "" : "alternate"),
                     'modus'        => IMPROVEMENT_BUILDING_DETAIL,
                     'buildingID'   => $i,
                     'caveID'       => $caveID,
                     'dbFieldName'  => $building->dbFieldName,
                     'name'         => $building->name,
                     'dependencies' => $result));
    }
  }

  // Show the building queue

  if ($queue){         // display the building queue
    $row = $queue->nextRow();
    tmpl_set($template, 'IMPROVEMENT_QUEUE',
             array('name'    => $buildingTypeList[$row['expansionID']]->name,
                   'size'    => $details[$buildingTypeList[$row['expansionID']]->dbFieldName] + 1,
                   'finish'  => date("d.m.Y H:i:s" , time_timestampToTime($row['event_end'])),
                   'action'  => IMPROVEMENT_DETAIL,
                   'eventID' => $row['event_expansionID'],
                   'caveID'  => $caveID));
  }
  return tmpl_parse($template);
}
?>
