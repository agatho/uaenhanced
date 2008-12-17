<?
/*
 * science.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function science_getScienceDetail($caveID, &$details){
  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $unitTypeList,
         $scienceTypeList,
         $config,
         $params,
         $db;

  // messages
  $messageText = array(
    0 => _('Der Forschungsauftrag wurde erfolgreich gestoppt.'),
    1 => _('Es konnte kein Forschungsauftrag gestoppt werden.'),
    2 => _('Der Auftrag konnte nicht erteilt werden. Es fehlen die notwendigen Voraussetzungen.'),
    3 => _('Der Auftrag wurde erteilt'),
    4 => _('Dieses Wissen wird schon in einer anderen Höhle erforscht.'),
    5 => _('Es wird gerade in einer anderen Höhle Wissen erforscht, dass dieses Wissen ausschließt.'));

  // proccess a cancel-order request
  if (isset($params->POST->eventID)) {
    $messageID = science_processOrderCancel($params->POST->eventID, $caveID, $db);
  }

  if (isset($params->POST->scienceID)){
    $messageID = science_processOrder($params->POST->scienceID,
                                      $caveID,
                                      $params->SESSION->player->playerID,
                                      $details, $db);

    $r = getCaveSecure($caveID, $params->SESSION->player->playerID);
    if ($r->isEmpty()) page_dberror();
    $details = $r->nextRow();
  }

  $queue = science_getScienceQueueForCave($params->SESSION->player->playerID, $caveID);

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'science.ihtml');

  // Show a special message
  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }

  // Show the science table
  for ($i = 0; $i < sizeof($scienceTypeList); $i++){
    $science = $scienceTypeList[$i]; // the current science
    $maxLevel = round(eval('return '.formula_parseToPHP("{$science->maxLevel};", '$details')));
    $notenough=FALSE;
		
    $result = rules_checkDependencies($science, $details);
    if ($result === TRUE){

      tmpl_iterate($template, 'SCIENCE');

      tmpl_set($template, "SCIENCE/alternate", ($count++ % 2 ? "alternate" : ""));

      tmpl_set($template, 'SCIENCE',
               array('dbFieldName' => $science->dbFieldName,
                     'name'        => $science->name,
                     'scienceID'   => $i,
                     'modus'       => SCIENCE_DETAIL,
                     'caveID'      => $caveID,
                     'size'        => "0" + $details[$science->dbFieldName],
                     'time'        => time_formatDuration(eval('return ' .
                                       formula_parseToPHP($science->productionTimeFunction . ";", '$details'))
                                       * SCIENCE_TIME_BASE_FACTOR)));

      // iterate ressourcecosts
      foreach ($science->resourceProductionCost as $resourceID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){

          tmpl_iterate($template, "SCIENCE/RESSOURCECOST");

          if ($details[$resourceTypeList[$resourceID]->dbFieldName] >= $cost){
            tmpl_set($template, "SCIENCE/RESSOURCECOST/ENOUGH/value", $cost);
          } else {
            tmpl_set($template, "SCIENCE/RESSOURCECOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "SCIENCE/RESSOURCECOST/dbFieldName", $resourceTypeList[$resourceID]->dbFieldName);
          tmpl_set($template, "SCIENCE/RESSOURCECOST/name",        $resourceTypeList[$resourceID]->name);
        }
      }
      // iterate unitcosts
      foreach ($science->unitProductionCost as $unitID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "SCIENCE/UNITCOST");

          if ($details[$unitTypeList[$unitID]->dbFieldName] >= $cost){
            tmpl_set($template, "SCIENCE/UNITCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "SCIENCE/UNITCOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "SCIENCE/UNITCOST/name", $unitTypeList[$unitID]->name);
        }
      }
      // iterate buildingcosts
      foreach ($science->buildingProductionCost as $buildingID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/BUILDINGCOST");

          if ($details[$buildingTypeList[$buildingID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/BUILDINGCOST/name", $buildingTypeList[$buildingID]->name);
        }
      }
      // iterate externalcosts
      foreach ($science->externalProductionCost as $externalID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "DEFENSESYSTEM/EXTERNALCOST");

          if ($details[$defenseSystemTypeList[$externalID]->dbFieldName] >= $cost){
            tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/LESS/value", $cost);
						$notenough=TRUE;
          }
          tmpl_set($template, "DEFENSESYSTEM/EXTERNALCOST/name", $defenseSystemTypeList[$externalID]->name);
        }
      }

      // show the science link ?!
      if ($queue)
        tmpl_set($template, 'SCIENCE/RESEARCH_LINK_NO/message', _('Erforschung im Gange'));

      else if ($notenough && $maxLevel > $details[$science->dbFieldName])
				        tmpl_set($template, 'SCIENCE/RESEARCH_LINK_NO/message', _('Zu wenig Rohstoffe'));

      else if ($maxLevel > $details[$science->dbFieldName]){
        tmpl_set($template, 'SCIENCE/RESEARCH_LINK',
                 array('action'     => SCIENCE,
                       'scienceID'  => $science->scienceID,
                       'caveID'     => $caveID));
      } else
        tmpl_set($template, '/SCIENCE/RESEARCH_LINK_NO/message', _('Max. Stufe'));

    } else if ($result !== FALSE && !$science->nodocumentation){

      tmpl_iterate($template, '/UNQUALIFIEDSCIENCES/SCIENCE');
      tmpl_set($template, '/UNQUALIFIEDSCIENCES/SCIENCE',
               array('alternate'    => ($count_unqualified++ % 2 ? "" : "alternate"),
                     'modus'        => SCIENCE_DETAIL,
                     'scienceID'    => $science->scienceID,
                     'caveID'       => $caveID,
                     'dbFieldName'  => $science->dbFieldName,
                     'name'         => $science->name,
                     'dependencies' => $result));
    }
  }
  // Show the science queue
  if ($queue){         // display the science queue
    $row = $queue->nextRow();
    tmpl_set($template, 'SCIENCE_QUEUE',
             array('name'    => $scienceTypeList[$row['scienceID']]->name,
                   'size'    => $details[$scienceTypeList[$row['scienceID']]->dbFieldName] + 1,
                   'finish'  => time_formatDatetime($row['end']),
                   'action'  => SCIENCE,
                   'eventID' => $row['event_scienceID'],
                   'caveID'  => $caveID));
  }

  tmpl_set($template, "rules_path", RULES_PATH);

  return tmpl_parse($template);
}
?>
