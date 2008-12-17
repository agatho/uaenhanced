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
    -4=> "Die Zielsiedlung steht unter Schutz. Der Zauber kann nicht erwirkt werden.",
    -3=> "Die angegebene Zielsiedlung wurde nicht gefunden.",
    -2=> "Der Zauber kann nicht auf die angegbene Zielsiedlung erwirkt ".
         "werden.",
    -1=> "Es ist ein Fehler bei der Verarbeitung Ihrer Anfrage aufgetreten. ".
         "Bitte wenden Sie sich an die Administratoren.",
    0 => "Der Zauber kann nicht erwirkt werden. Es fehlen die ".
         "notwendigen Voraussetzungen.",
    1 => "Das Erwirken des Zaubers scheint Erfolg zu haben.",
    2 => "Die G&ouml;tter haben Ihr Flehen nicht erh&ouml;rt! Die ".
         "eingesetzten Opfergaben sind nat&uuml;rlich dennoch verloren. ".
         "Mehr Gl&uuml;ck beim n&auml;chsten Mal!");

// ADDED by chris--- for cavebook -----------------------

if ($params->POST->targetCaveID != -1) {
  $targetCave = getCaveByID($params->POST->targetCaveID);
  $x = $targetCave[xCoord];
  $y = $targetCave[yCoord];
} else {
  $x = $params->POST->xCoord;
  $y = $params->POST->yCoord;
}

// ------------------------------------------------------

// and changed $params->POST->xCoord to $x etc

  if (isset($params->POST->wonderID)){
    $messageID = wonder_processOrder($playerID, $params->POST->wonderID, 
				     $caveID, $x,
				     $y, $details, $db);
    $reload = 1;
  }

  if ($reload){  // this isn't that elegant...
    $r = getCaveSecure($caveID, $params->SESSION->user['playerID']);

    if ($r->isEmpty()) page_dberror();
    $details = $r->nextRow();
  }

  $template = 
    @tmpl_open("./templates/" .  
               $config->template_paths[$params->SESSION->user['template']] . 
               "/wonder.ihtml");

  // Show a special message

  if (isset($messageID)) {
    tmpl_set($template, '/MESSAGE/message', $messageText[$messageID]);
  }


// ADDED by chris--- for cavebook ---------------------------------

  // Getting entries
 $cavelist = cavebook_getEntries($params->SESSION->user['playerID']);

  // Show the cave table

$cavebook = array();
  for($ix = 0; $ix < sizeof($cavelist[id]); $ix++) {

$cavebook[$ix][cavebook_entry] = $cavelist[name][$ix];
$cavebook[$ix][cavebook_id] = $cavelist[id][$ix];
$cavebook[$ix][cavebook_x] = $cavelist[x][$ix];
$cavebook[$ix][cavebook_y] = $cavelist[y][$ix];

  }

// --------------------------------------------------------------


  // Show the wonder table
  for ($i = 0; $i < sizeof($wonderTypeList); $i++){

    $wonder = $wonderTypeList[$i]; // the current building

    $result = rules_checkDependencies($wonder, $details);
    if ($result === TRUE){
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



      // show the wonder link 
      tmpl_set($template, 'WONDER/BUILD_LINK',
	       array('action'     => WONDER,
		     'wonderID'   => $wonder->wonderID,
		     'cave_book_link' => CAVE_BOOK, // ADDED by chris--- for cavebook
'BOOKENTRY' => $cavebook,
		     'caveID'     => $caveID));
    
    } else if ($params->SESSION->user['show_unqualified'] && 
	       $result !== FALSE && !$wonder->nodocumentation){

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

  return tmpl_parse($template);
}
?>
