<?
/*
 * unitbuild.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function unit_getUnitDetail($caveID, &$details) {
  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $unitTypeList,
         $config,
         $params,
         $db,
         $MAX_RESOURCE;

  // messages
  $messageText = array (
    0 => "Der Arbeitsauftrag wurde erfolgreich gestoppt.",
    1 => "Es konnte kein Arbeitsauftrag gestoppt werden.",
    2 => "Der Auftrag konnte nicht erteilt werden. Es fehlen die ".
         "notwendigen Voraussetzungen.",
    3 => "Der Auftrag wurde erteilt",
    4 => "Bitte korrekte Anzahl der Einheiten Angeben (1 ... ".MAX_SIMULTAN_BUILDED_UNITS.")",);

  // proccess a cancel-order request

  if (isset($params->POST->eventID)){
    $messageID = unit_processOrderCancel($params->POST->eventID, $caveID, $db);
  }

  // proccess a new order request
  if (isset($params->POST->unitID)){
    $messageID = unit_processOrder($params->POST->unitID, intval($params->POST->quantity), $caveID, $db, $details);

    $r = getCaveSecure($caveID, $params->SESSION->user['playerID']);
    if ($r->isEmpty()) page_dberror();
    $details = $r->nextRow();
  }
  $queue = unit_getUnitQueueForCave($params->SESSION->user['playerID'], $caveID);

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/unitbuild.ihtml");

  // Show a special message
  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }

  // Show the unit table
  for($i = 0; $i < sizeof($unitTypeList); $i++) {

$notenough = FALSE;

    $unit = $unitTypeList[$i]; // the current unit

    $result = rules_checkDependencies($unit, $details);
    if ($result === TRUE){

      tmpl_iterate($template, '/UNIT');

      tmpl_set($template, "UNIT/alternate", ($count++ % 2 ? "alternate" : ""));

      tmpl_set($template, 'UNIT', array('name'        => $unit->name,
                                        'dbFieldName' => $unit->dbFieldName,
                                        'unitID'      => $i,
                                        'modus'       => UNIT_PROPERTIES,
                                        'caveID'      => $caveID,
                                        'size'        => "0" + $details[$unit->dbFieldName],
                                        'time'        => time_formatDuration(
                                                           eval('return '.
                                                             formula_parseToPHP(
                                                               $unit->productionTimeFunction.
                                                                 ";", '$details')) * BUILDING_TIME_BASE_FACTOR)));

      // iterate ressourcecosts
      foreach ($unit->resourceProductionCost as $resourceID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){

          tmpl_iterate($template, "UNIT/RESSOURCECOST");

          if ($details[$resourceTypeList[$resourceID]->dbFieldName] >= $cost){
            tmpl_set($template, "UNIT/RESSOURCECOST/ENOUGH/value", $cost);
          } else {
            tmpl_set($template, "UNIT/RESSOURCECOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "UNIT/RESSOURCECOST/dbFieldName", $resourceTypeList[$resourceID]->dbFieldName);
          tmpl_set($template, "UNIT/RESSOURCECOST/name",        $resourceTypeList[$resourceID]->name);
        }
      }
      // iterate unitcosts
      foreach ($unit->unitProductionCost as $unitID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "UNIT/UNITCOST");

          if ($details[$unitTypeList[$unitID]->dbFieldName] >= $cost){
            tmpl_set($template, "UNIT/UNITCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "UNIT/UNITCOST/LESS/value", $cost);
$notenough = TRUE;
          }
          tmpl_set($template, "UNIT/UNITCOST/name", $unitTypeList[$unitID]->name);
        }
      }
      // iterate buildingcosts
      foreach ($unit->buildingProductionCost as $buildingID => $function){

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
      foreach ($unit->externalProductionCost as $externalID => $function){

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

      // show the improvement link ?!
      if ($queue)
        tmpl_set($template, "UNIT/UNIT_LINK_NO/message", "Ausbildung im Gange");

      else if ($notenough)
        tmpl_set($template, "UNIT/UNIT_LINK_NO/message", "");

      else {
        $formParams = array(array( 'name' => 'modus',  'value' => UNIT_BUILDER ),
                            array( 'name' => 'caveID', 'value' => $caveID ),
                            array( 'name' => 'unitID', 'value' => $unit->unitID));
        tmpl_set($template, "UNIT/UNIT_LINK/PARAMS", $formParams );
      }
    } else if ($params->SESSION->user['show_unqualified'] && $result !== FALSE && !$unit->nodocumentation){

      tmpl_iterate($template, '/UNQUALIFIEDUNITS/UNIT');
      tmpl_set($template, '/UNQUALIFIEDUNITS/UNIT',
               array('alternate'    => ($count_unqualified++ % 2 ? "" : "alternate"),
                     'modus'        => UNIT_PROPERTIES,
                     'unitID'       => $i,
                     'caveID'       => $caveID,
                     'dbFieldName'  => $unit->dbFieldName,
                     'name'         => $unit->name,
                     'dependencies' => $result));
    }
  }

  // Show the building queue

  if ($queue){ // display the unit building queue
    $row = $queue->nextRow();
    tmpl_set($template, 'UNIT_QUEUE' , array('name'     => $unitTypeList[$row[unitID]]->name,
                                             'quantity' => $row['quantity'],
                                             'finish'   => date("d.m.Y H:i:s", time_timestampToTime($row[event_end])),
                                             'action'   => UNIT_BUILDER,
                                             'eventID'  => $row['event_unitID'],
                                             'caveID'   => $caveID));
  }

  return tmpl_parse($template);
}
?>
