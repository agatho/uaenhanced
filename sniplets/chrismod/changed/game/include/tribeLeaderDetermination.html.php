<?
/*
 * tribeLeaderDetermination.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function tribeLeaderDetermination_getContent($playerID, $tribe, $data) {

  global
    $no_resource_flag, $governmentList, $leaderDeterminationList, $db, $config,
    $params;

  $no_resource_flag = 1;

  if (!($governmentData = government_getGovernmentForTribe($tribe, $db)))
    page_dberror();

  $handlers[1]  = "leaderDetermination_infoHandler";
  $handlers[2]  = "leaderDetermination_electionHandler";

  $templates[1] = "leaderDeterminationInfo.ihtml";
  $templates[2] = "leaderDeterminationElection.ihtml";

  $id = $governmentList[$governmentData[governmentID]][leaderDeterminationID];

  $template = 
    tmpl_open("./templates/" .  
	       $config->template_paths[$params->SESSION->user['template']] . 
	       "/".$templates[$id]);


  if (!($templateData = 
	$handlers[$id]($playerID,
		       $tribe,
		       $governmentData,
		       $data))) page_dberror();

  tmpl_set($template, 'LEADERDETERMINATION', $templateData);

  return tmpl_parse($template);
}

function leaderDetermination_infoHandler($playerID, 
					 $tribe, 
					 $governmentData, 
					 $data) 
{
  global
    $leaderDeterminationList, $governmentList;

  $id = $governmentList[$governmentData[governmentID]][leaderDeterminationID];

  $content = array(
    "message"     => "Sie haben keinen Einflu&szlig; auf die Bestimmung ".
                     "des Clananf&uuml;hrers",
    "name"        => $leaderDeterminationList[$id][name],
    "description" => $leaderDeterminationList[$id][description]
    );

  return $content;
}

function leaderDetermination_electionHandler($playerID,
					     $tribe,
					     $governmentData,
					     $data)
{
  global
    $leaderDeterminationList, $governmentList, $db;

  $messages = array(
    -1 => "Die Stimme konnte wegen eines Fehlers nicht abgegeben werden.",
     1 => "Die Stimme wurde erfolgreich gez&auml;hlt."
    );

  $id = $governmentList[$governmentData[governmentID]][leaderDeterminationID];

  if ($data) {
    $message = leaderDetermination_processChoiceUpdate($playerID,
						       $data[playerID],
						       $tribe,
						       $db);
  }
 
  $votes =
    leaderDetermination_getElectionResultsForTribe($tribe, $db); 

  $choice =
    leaderDetermination_getVoteOf($playerID, $db);

  $possibleChoices =
    tribe_getAllMembers($tribe, $db);

  $possibleChoices[0] = array (
    "name"     => "Keiner",
    "playerID" => 0
    );

  foreach($possibleChoices AS $key => $value) {
    if ($key == $choice) {
      $possibleChoices[$key][selected] = "selected";
    }
  }

  $choiceData = array (
    "modus_name" => "modus",
    "modus"      => TRIBE_LEADER_DETERMINATION,
    "dataarray"  => "data",
    "dataentry"  => "playerID",
    "OPTION"     => $possibleChoices,
    "caption"    => "W&auml;hlen"
    );
  
  $content = array (
    "name"    => $leaderDeterminationList[$id][name],
    "VOTES"   => $votes,
    "CHOICE"  => $choiceData
    );
  
  if ($messages[$message])
    $content['MESSAGE/message'] = $messages[$message];
    
  return $content;
}

?>
