<?
/*
 * tribeAdmin.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function tribeAdmin_getContent($playerID, $tag) {
  global 
    $config, $params, $db, $no_resource_flag,
    $relationList, $governmentList;

  $no_resource_flag = 1;

  // check, for sercurity reasons!
  if (!tribe_isLeader($playerID, $tag, $db))
    page_dberror();

  // messages
  $messageText = array (
    -13=>"Die Beziehung wurde nicht ge&auml;ndert, weil der ausgew&auml;hlte ".
         "Beziehungstyp bereits eingestellt ist.",
    -12=>"Eure Untergebenen weigern sich, ".
    "diese Beziehung gegen&uuml;ber einem so kleinen Clan einzugehen.",
    -11=>"Die Moral des Gegners ist noch nicht schlecht genug. Sie muss unter ".
    RELATION_FORCE_MORAL_THRESHOLD." sinken. Eine weitere Chance besteht, ".
    "wenn die Mitglierzahl des gegnerischen Clans um 30 Prozent gesunken ist.",
    "Euren Gunsten verschoben. Das Verh&auml;ltnis Eurer Rankingpunkte zu ".
    "denen des Gegners muss sich seit Kriegsbeginn verdoppelt haben.",
    -10=>"Die zu &auml;ndernde Beziehung wurde nicht gefunden!",
    -9=> "Die Regierung konnte nicht ge&auml;ndert werden, weil sie erst ".
    "vor kurzem ge&auml;ndert wurde.",
    -8=> "Die Regierung konnte aufgrund eines Fehlers nicht aktualisiert ".
    "werden",
    -7=> "Zu sich selber kann man keine Beziehungen aufhehmen!",
    -6=> "Den Clan gibt es nicht!",
    -5=> "Von der derzeititgen Beziehung kann nicht dirket auf die ".
    "ausgew&auml;hlte Beziehungsart gewechselt werden.",
    -4=> "Die Mindestlaufzeit l&auml;uft noch!",
    -3=> "Die Beziehung konnte aufgrund eines Fehlers nicht aktualisiert ".
    "werden.",
    -2=> "Der Spieler ist ebenfalls Clananf&uuml;hrer und kann nicht ".
    "gekickt werden. Er kann nur freiwillig gehen.",
    -1=> "Der Spieler konnte nicht gekickt werden!",
    0 => "Die Daten wurden erfolgreich aktualisiert.",
    1 => "Der Spieler wurde erfolgreich gekickt.",
    2 => "Die Daten konnten gar nicht oder zumindest nicht vollst&auml;ndig ".
    "aktualisiert werden.",
    3 => "Die Beziehung wurde umgestellt.",
    4 => "Die Regierung wurde ge&auml;ndert."
    );
  
  // proccess form data   

  if ($params->POST->forceRelationData) {
    $messageID = relation_processForceRelation($tag,
					       $params->POST->forceRelationData,
					       $db);
  }
  else if ($params->POST->relationData) {
    $messageID = relation_processRelationUpdate($tag,
                                                $params->POST->relationData,
						$db);
  }
  else if ($params->POST->data) { 
    $messageID = tribe_processAdminUpdate($playerID, 
					  $tag,
					  $params->POST->data,
					  $db); 
  }
  else if ($params->POST->kick) {
    $messageID = tribe_processKickMember($params->POST->playerID,
					 $tag,
					 $db);
  }
  else if ($params->POST->governmentData) {
    $messageID = government_processGovernmentUpdate(
                     $tag,
		     $params->POST->governmentData,
		     $db);
  }

  // get the tribe data

  if (!($tribeData = tribe_getTribeByTag($tag, $db)))
    page_dberror();

  $tribeData[description] = str_replace("<br />", "", $tribeData[description]);

  if (!($memberData = tribe_getAllMembers($tag, $db)))
    page_dberror();

  // get government
  if (!($tribeGovernment = government_getGovernmentForTribe($tag, $db)))
    page_dberror();

  $tribeGovernment[name] = 
    $governmentList[$tribeGovernment[governmentID]][name];

  // get relations

  if (!($tribeRelations = relation_getRelationsForTribe($tag, $db)))
    page_dberror();
  
  $template = 
    @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/tribeAdmin.ihtml");
   
  // Show a special message

  if (isset($messageID)) { 
    tmpl_set($template, '/MESSAGE/message',
	     $messageText[$messageID]);
  }

  // show the profile's data

  tmpl_set($template, 'modus_name', 'modus');
  tmpl_set($template, 'modus_value', TRIBE_ADMIN);

  ////////////// user data //////////////////////

  tmpl_set($template, 'DATA_GROUP/heading', 'Clandaten');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/name',  'Tag');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INFO/value', $tribeData[tag]);
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INFO');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Name');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'name');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $tribeData[name]);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '20');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '90');
  tmpl_iterate($template, 'DATA_GROUP/ENTRY_INPUT');

  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/name',      'Password');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/dataentry', 'password');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/value',     $tribeData[password]);
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/size',      '15');
  tmpl_set($template, 'DATA_GROUP/ENTRY_INPUT/maxlength', '15');

  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/name',      'Beschreibung');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/dataarray', 'data');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/dataentry', 'description');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/value',     $tribeData[description]);
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/cols',      '25');
  tmpl_set($template, 'DATA_GROUP/ENTRY_MEMO/rows',      '8');


  ////////////// government /////////////////////

  if ($tribeGovernment[isChangeable]) {
    tmpl_set($template, 'GOVERNMENT', array(
      'modus_name'=> "modus",
      'modus'     => TRIBE_ADMIN,
      'caption'   => "&Auml;ndern",
      'SELECTOR'  => array(
        'dataarray' => 'governmentData',
        'dataentry' => 'governmentID'
        )
      ));

    foreach($governmentList AS $governmentID => $typeData) {
      
      tmpl_iterate($template, 'GOVERNMENT/SELECTOR/OPTION');

      tmpl_set($template, 'GOVERNMENT/SELECTOR/OPTION', array (
	'value'    => $governmentID,
	'selected' => 
	($governmentID == $tribeGovernment[governmentID] ? 
	 "selected" : ""),
	'text'     => $typeData[name]
      ));      
  }

    
  }
  else {
    tmpl_set($template, 'GOVERNMENT_INFO', array(
      'name'     => $tribeGovernment[name],
      'duration' => $tribeGovernment[time],
      ));
  }

  ////////////// relations //////////////////////

  tmpl_set($template, 'RELATION_NEW', array(
    'modus_name'=> "modus",
    'modus'     => TRIBE_ADMIN,
    'dataarray' => "relationData",
    'dataentry' => "tag",
    'value'     => $params->POST->relationData['tag'],
    'size'      => 8,
    'maxlength' => 8,
    'caption'   => "&Auml;ndern"
    ));

  tmpl_set($template, 'RELATION_NEW/SELECTOR', array(
    'dataarray' => "relationData",
    'dataentry' => "relationID"
    ));
	   
  foreach($relationList AS $relationID => $typeData) {
    
    tmpl_iterate($template, 'RELATION_NEW/SELECTOR/OPTION');

    tmpl_set($template, 'RELATION_NEW/SELECTOR/OPTION', array (
      'value'    => $relationID,
      'selected' => 
      ($relationID == $params->POST->relationData[relationID] ? 
       "selected" : ""),
      'text'     => $typeData[name]
      ));      
  }

  // existing relations towards other clans //////////////////

  foreach($tribeRelations[own] AS $target => $targetData) {
    
    if (! $targetData[changeable]) {
      // relation, that couldn't be changed at the moment

      tmpl_iterate($template, 'RELATION_INFO');
      
      tmpl_set($template, 'RELATION_INFO', array(
	'tag'            => $target,
	'relation'       => $relationList[$targetData[relationType]][name],
	'duration'       => $targetData[time],
	'their_relation' =>
	($tribeRelations[other][$target] ?
	 $relationList[$tribeRelations[other][$target][relationType]][name] :
	 $relationList[0][name])
	));

      continue;
    }
    else {
      // relation, that is changeable
      tmpl_iterate($template, 'RELATION');
      
      tmpl_set($template, 'RELATION', array(
        'modus_name'=> "modus",
	'modus'          => TRIBE_ADMIN,
	'dataarray'      => "relationData",
	'dataentry'      => "tag",
	'value'          => $target,
	'target_points'  => $targetData[target_rankingPoints],
	'tribe_points'   => $targetData[tribe_rankingPoints],
	'their_relation' =>
	($tribeRelations[other][$target] ?
	 $relationList[$tribeRelations[other][$target][relationType]][name] :
	 $relationList[0][name]),
	'caption'        => "&Auml;ndern"
	));
      
      tmpl_set($template, 'RELATION/SELECTOR', array(
        'dataarray' => "relationData",
	'dataentry' => "relationID"
	));

      // check, if it is possible to get or loose fame, and display if true
      if ($targetData[attackerReceivesFame] || $targetData[defenderReceivesFame] ||
          $tribeRelations[other][$target][attackerReceivesFame] ||
	  $tribeRelations[other][$target][defenderReceivesFame]) {
        tmpl_set($template, 'RELATION/FAME', array(
	  'tribe_fame'   => $targetData[fame],
	  'target_fame'  => $tribeRelations[other][$target][fame],
	  'tribe_moral'  => $targetData[moral],
	  'target_moral' => $tribeRelations[other][$target][moral]
	  ));
      }

      foreach($relationList AS $relationType => $typeData) {
	
	if ($tribeRelations[other][$tag]) {     // get relation of target to tr.
	  $relationTypeTowardsTribe = 
	    $tribeRelations[other][$tag][relationType];
	}

	// check, if switch to relationType is possible
	if (($relationTypeTowardsTribe != $relationType) &&
	    ($relationType != $targetData[relationType]) &&
	    ! relation_isPossible($relationType, $targetData[relationType])) {
	  continue;
	}	  
	tmpl_iterate($template, 'RELATION/SELECTOR/OPTION');

	tmpl_set($template, 'RELATION/SELECTOR/OPTION', array (
	  'value'    => $relationType,
	  'selected' => 
	  ($relationType == $targetData[relationType] ? 
	   "selected" : ""),
	  'text'     => $typeData[name]
	  ));     
      }
      
      if ($targetData[relationType] == RELATION_FORCE_FROM_ID) {
	tmpl_set($template, 'RELATION/FORCE', array(
	  'modus_name'  => "modus",
	  'modus'       => TRIBE_ADMIN,
	  'dataarray'   => forceRelationData,
	  'dataentry'   => "tag",
	  'value'       => $target,
	  'caption'     => "Kapitulation von $target erzwingen"
	  ));
      }
    }
    
  }  
  

  ////////////// memberliste ////////////////////

  foreach($memberData AS $playerID => $playerData) 
  {
    tmpl_iterate($template, 'MEMBER');
    tmpl_set($template, 'MEMBER', array(
      "name"             => $playerData['name'],
      "lastAction"       => $playerData['lastAction'],
      "player_link"      => "modus=".PLAYER_DETAIL."&detailID=$playerID",
      "player_kick_link" => "modus=".TRIBE_ADMIN."&playerID=$playerID&kick=1"
      ));
  }

  ////////////// delete tribe ////////////////////

  tmpl_set($template, 'DELETE/modus_name', 'modus');
  tmpl_set($template, 'DELETE/modus',      TRIBE_DELETE);
  tmpl_set($template, 'DELETE/heading',    'Clan aufl&ouml;sen');
  tmpl_set($template, 'DELETE/text',       'Den gesamten Clan aufl&ouml;sen. Alle Mitglieder sind danach Clanlos.');
  tmpl_set($template, 'DELETE/caption',    "$tribe aufl&ouml;sen");

  return tmpl_parse($template);
}

?>
