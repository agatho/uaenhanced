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

function quest_getQuestOverview($playerID){

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
  $template = @tmpl_open('./templates/' . $config->template_paths[$params->SESSION->user['template']] . '/quest.ihtml');


// Getting the active quests

$activeQuests = getActiveQuests($playerID, $db);

if ($activeQuests) {
    $activeQuestsData = array();
    foreach ($activeQuests AS $key => $data){

	$activeQuestsData[] = array("title"  => getQuestTitle($data['questID'], $db),
                                    "description" => getQuestToDo($data['questID'], $db),
				    "questID" => $data['questID'],
				    "playerID" => $playerID,
				    "modus" => QUEST_DETAIL);

	}
}


// Getting the successful quests

$succeededQuests = getSucceededQuests($playerID, $db);

if ($succeededQuests) {
    $succeededQuestsData = array();
    foreach ($succeededQuests AS $key => $data){

	$succeededQuestsData[] = array("title"  => getQuestTitle($data['questID'], $db),
                                       "description" => getQuestToDo($data['questID'], $db),
				       "questID" => $data['questID'],
				       "playerID" => $playerID,
				       "modus" => QUEST_DETAIL);

	}
}


// Getting the failed quests

$failedQuests = getFailedQuests($playerID, $db);

if ($failedQuests) {
    $failedQuestsData = array();
    foreach ($failedQuests AS $key => $data){

	$failedQuestsData[] = array("title"  => getQuestTitle($data['questID'], $db),
                                    "description" => getQuestToDo($data['questID'], $db),
				    "questID" => $data['questID'],
				    "playerID" => $playerID,
				    "modus" => QUEST_DETAIL);

	}
}


// Getting the aborted quests

$abortedQuests = getAbortedQuests($playerID, $db);

if ($abortedQuests) {
    $abortedQuestsData = array();
    foreach ($abortedQuests AS $key => $data){

	$abortedQuestsData[] = array("title"  => getQuestTitle($data['questID'], $db),
                                     "description" => getQuestAbortMsg($data['questID'], $db),
				     "questID" => $data['questID'],
				     "playerID" => $playerID,
				     "modus" => QUEST_DETAIL);

	}
}




// Templating

$data = array();

  if (!sizeof($activeQuestsData))
    $data['NOACTIVEQUESTS'] = array('dummy' => "");
  else {
    $data['ACTIVEQUESTS'] = $activeQuestsData;
    tmpl_iterate($template, 'ACTIVEQUESTS');
  }

  if (!sizeof($succeededQuestsData))
    $data['NOWONQUESTS'] = array('dummy' => "");
  else {
    $data['WONQUESTS'] = $succeededQuestsData;
    tmpl_iterate($template, 'WONQUESTS');
  }

  if (!sizeof($failedQuestsData))
    $data['NOFAILEDQUESTS'] = array('dummy' => "");
  else {
    $data['FAILEDQUESTS'] = $failedQuestsData;
    tmpl_iterate($template, 'FAILEDQUESTS');
  }

  if (!sizeof($abortedQuestsData))
    $data['NOABORTEDQUESTS'] = array('dummy' => "");
  else {
    $data['ABORTEDQUESTS'] = $abortedQuestsData;
    tmpl_iterate($template, 'ABORTEDQUESTS');
  }


$data['LINK'] = array('link' => "?modus=" . QUEST_HELP);


  tmpl_set($template, "/", $data );

  return tmpl_parse($template);
}

?>