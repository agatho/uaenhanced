<?
/*
 * wonder.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function wonder_getWonderContent($playerID, $caveID, &$details){
  global 
    $buildingTypeList,
    $resourceTypeList,
    $wonderTypeList,
    $unitTypeList,
    $config,
    $params,
    $db;

  // messages
  $messageText = array (
    -3=> "Die angegebene Zielh&ouml;hle wurde nicht gefunden.",
    -2=> "Das Wunder kann nicht auf die angegbene Zielh&ouml;hle erwirkt ".
         "werden.",
    -1=> "Es ist ein Fehler bei der Verarbeitung Ihrer Anfrage aufgetreten. ".
         "Bitte wenden Sie sich an die Administratoren.",
    0 => "Das Wunder kann nicht erwirkt werden. Es fehlen die ".
         "notwendigen Voraussetzungen.",
    1 => "Das Erflehen des Wunders scheint Erfolg zu haben.",
    2 => "Die G&ouml;tter haben Ihr Flehen nicht erh&ouml;rt! Die ".
         "eingesetzten Opfergaben sind nat&uuml;rlich dennoch verloren. ".
         "Mehr Gl&uuml;ck beim n&auml;chsten Mal!");

  if (isset($params->POST->wonderID)){
    $messageID = wonder_processOrder($playerID, $params->POST->wonderID, 
				     $caveID, $params->POST->xCoord,
				     $params->POST->yCoord, $details, $db);
    $reload = 1;
  }

  if ($reload){  // this isn't that elegant...
    $r = getCaveSecure($caveID, $params->SESSION->player->playerID);

    if ($r->isEmpty()) page_dberror();
    $details = $r->nextRow();
  }

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'wonder.ihtml');

  // Show a special message

  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }

  // Show the wonder table
  for ($i = 0; $i < sizeof($wonderTypeList); $i++){

    $wonder = $wonderTypeList[$i]; // the current wonder

    $result = rules_checkDependencies($wonder, $details);
    if (($result === TRUE) && (!$wonder->nodocumentation)){
      tmpl_iterate($template, 'WONDER');

      tmpl_set($template, "WONDER/alternate", 
               ($count++ % 2 ? "alternate" : ""));

      tmpl_set($template, 'WONDER',
               array('name'       => $wonder->name,
                     'wonderID'   => $i,
                     'modus'      => WONDER_DETAIL,
                     'caveID'     => $caveID));

      // iterate ressourcecosts
      foreach ($wonder->resourceProductionCost as $resourceID => $function){

        $cost = ceil(eval('return '. 
                          formula_parseToPHP($function . ';', '$details')));

        if ($cost){

          tmpl_iterate($template, "WONDER/RESSOURCECOST");

          if ($details[$resourceTypeList[$resourceID]->dbFieldName] >= $cost){
            tmpl_set($template, "WONDER/RESSOURCECOST/ENOUGH/value", $cost);
          } else {
            tmpl_set($template, "WONDER/RESSOURCECOST/LESS/value", $cost);
          }
          tmpl_set($template, "WONDER/RESSOURCECOST/dbFieldName", $resourceTypeList[$resourceID]->dbFieldName);
          tmpl_set($template, "WONDER/RESSOURCECOST/name",        $resourceTypeList[$resourceID]->name);
        }
      }
      // iterate unitcosts
      foreach ($wonder->unitProductionCost as $unitID => $function){

        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "WONDER/UNITCOST");

          if ($details[$unitTypeList[$unitID]->dbFieldName] >= $cost){
            tmpl_set($template, "WONDER/UNITCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "WONDER/UNITCOST/LESS/value", $cost);
          }
          tmpl_set($template, "WONDER/UNITCOST/name", $unitTypeList[$unitID]->name);
        }
      }

      foreach ($wonder->buildingProductionCost as $unitID => $function){
        $cost = ceil(eval('return '. formula_parseToPHP($function . ';', '$details')));

        if ($cost){
          tmpl_iterate($template, "WONDER/UNITCOST");

          if ($details[$buildingTypeList[$unitID]->dbFieldName] >= $cost){
            tmpl_set($template, "WONDER/UNITCOST/ENOUGH/value", $cost);

          } else {
            tmpl_set($template, "WONDER/UNITCOST/LESS/value", $cost);
          }
          tmpl_set($template, "WONDER/UNITCOST/name", $buildingTypeList[$unitID]->name);
        }
      }


      

      // show the wonder link 
      tmpl_set($template, 'WONDER/BUILD_LINK',
	       array('action'     => WONDER,
		     'wonderID'   => $wonder->wonderID,
		     'caveID'     => $caveID));
   
      if($wonder->target != "same") { // show input field of target cave iff wonder may be cast on another cave
        tmpl_iterate($template, 'WONDER/BUILD_LINK/TARGET');  
      }
   
    } else if ($result !== FALSE && !$wonder->nodocumentation){

      tmpl_iterate($template, '/UNQUALIFIEDWONDERS/WONDER');
      tmpl_set($template, '/UNQUALIFIEDWONDERS/WONDER',
               array('alternate'    => ($count_unqualified++ % 2 ? "" : "alternate"),
                     'modus'        => WONDER_DETAIL,
                     'wonderID'     => $i,
                     'caveID'       => $caveID,
                     'name'         => $wonder->name,
                     'dependencies' => $result));
    }
  }

  tmpl_set($template, "rules_path", RULES_PATH);

  return tmpl_parse($template);
}
?>
