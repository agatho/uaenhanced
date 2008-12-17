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

function quest_getQuestDetails($questID, $playerID) {

// hm need to check this
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


  // open the template
  $template = @tmpl_open('./templates/' . $config->template_paths[$params->SESSION->user['template']] . '/quest_details.ihtml');


$data = array();

// checking if the given quest is really known to the given player

if (!questKnownByPlayer($questID, $playerID, $db)) {
  $data['QUESTNOTKNOWN'] = array('dummy' => "");
  tmpl_set($template, "/", $data );

  return tmpl_parse($template);
}


$data['QUESTKNOWN'] = array("title"  => getQuestTitle($questID, $db),
                            "description" => getQuestDescription($questID, $db),
			    "todo" => getQuestToDo($questID, $db));


// checking if the given quest is aborted to the given player

if (questAbortedToPlayer($questID, $playerID, $db)) {
  $data['QUESTABORTED'] = array('message' => getQuestAbortMsg($questID, $db));
}







  tmpl_set($template, "/", $data );

  return tmpl_parse($template);

}

?>