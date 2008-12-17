<?
/*
 * ticker_archive.html.php - show ticker messages
 * Copyright (c) 2004  chris---
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function ticker_getMessages($playerID){

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
  $template = @tmpl_open('./templates/' . $config->template_paths[$params->SESSION->user['template']] . '/ticker_archive.ihtml');


// Getting the messages

$messages = getTickerMessages($db);




// Templating

$data = array();

  if (!sizeof($messages))
    $data['NOMESSAGES'] = array('dummy' => "");
  else {
    $data['MESSAGES'] = $messages;
    tmpl_iterate($template, 'MESSAGES');
  }


  tmpl_set($template, "/", $data );

  return tmpl_parse($template);
}

?>