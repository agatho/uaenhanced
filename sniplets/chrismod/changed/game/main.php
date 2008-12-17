<?
/*
 * main.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("./include/page.inc.php");
require_once("./include/db.functions.php");

page_start();

/* ***** SESSION EXPIRATION CHECK ***************************************************** */

// frisch eingeloggt
if (!isset($params->SESSION->lastAction))
  $_SESSION['lastAction'] = time();

// SESSION_MAX_LIFETIME überschritten
else if (time() > $params->SESSION->lastAction + SESSION_MAX_LIFETIME)
  page_error403("Sie waren für " . date("i", SESSION_MAX_LIFETIME) .
                " Minuten oder mehr inaktiv. Letzte Aktion um " .
                date("H:i:s", $params->SESSION->lastAction . " Uhr."));

// Session gültig
else $_SESSION['lastAction'] = time();

/* ***** SESSION CHECK ***************************************************** */
$checksum = md5($_SERVER['HTTP_USER_AGENT'] .
                $_SERVER['HTTP_ACCEPT_CHARSET'] .
                $_SERVER['HTTP_ACCEPT_LANGUAGE']);
if ($checksum != $params->SESSION->session['loginchecksum'])
  page_error403(__FILE__ . ":" . __LINE__ . ": Browserfehler.");

/* FIXME doesnt work with proxy servers
if (page_get_ip() != $params->SESSION->session['loginip'])
  //page_error403(__FILE__ . ":" . __LINE__ . ": Ihre IP hat gewechselt.");
  echo "Fehler:Ihre IP hat gewechselt. Bitte teilen Sie uns dies im ".
       "<a href=\"http://www.uga-agga.com/index.php\" target=\"_blank\">Forum</a> ".
       "mit.<br>";
*/

/* ***** INPUT VAR CHECKS ************************************************** */

/* *** wenn modus nicht gesetzt, waehle Modus 1 *** */
$modus  = $params->POST->modus;
if (!isset($modus)) $modus = CAVE_DETAIL;

/* *** modus merken wenn sinnvoll *** */
if (in_array($modus, $config->rememberModusInclude)){
  $_SESSION['current_modus'] = $modus;
} else {
  $_SESSION['current_modus'] = CAVE_DETAIL;
}

/* ***** INCLUDE NECESSARY FILES ******************************************* */
if (is_array($config->require_files['ALL']))
  foreach($config->require_files['ALL'] as $k => $file) require_once("include/".$file);
if (is_array($config->require_files[($modus)]))
  foreach($config->require_files[($modus)] as $k => $file) require_once("include/".$file);
/*************************** DB Security Check *******************************/

// get the caveID out of the Session var 'caveID', which is ONLY set in the ugastart.php
$caveID = $params->SESSION->caveID;

// alle Höhlen holen
$meineHoehlen = getCaves($params->SESSION->user['playerID']);

// keine Höhlen mehr?
if ($meineHoehlen === 0 || sizeof($meineHoehlen) == 0){
  $no_resource_flag = TRUE;
  if (!in_array($modus, $config->noCaveModusInclude)){
    $modus = NO_CAVE_LEFT;
  }
}

else {
  // caveID nicht übergeben, nimm die mit der kleinsten ID
  if ($caveID == NULL){
    $caveID = current($meineHoehlen);
    $caveID = $caveID['caveID'];
  }
  
  // diese caveID gehört mir nicht
  if (!array_key_exists($caveID, $meineHoehlen)){
    $no_resource_flag = TRUE;
    $modus = NOT_MY_CAVE;
  }
}
///////////////////////////////////////////////////////////////////////////////
// checken, ob session timeout                                               //
// this check FAILS during the two seconds around midnight!                  //
///////////////////////////////////////////////////////////////////////////////

list($usec, $sec) = explode(" ", microtime());
$microtime = $sec + $usec; // calculate seconds with 1000s frac

$query = "UPDATE Session SET microtime = '$microtime' " .
         "WHERE playerID = '{$params->SESSION->user['playerID']}' ".
         "AND `sessionID` = {$_SESSION['session']['sessionID']} " .
         "AND ((lastAction < (NOW() - INTERVAL 2 SECOND) + 0) " .
         "OR microtime <= $microtime - {$config->WWW_REQUEST_TIMEOUT})";

if (!$db->query($query))
  page_error403("Ihre Session konnte nicht aktualisiert werden.");

if (!$db->affected_rows())
  page_error403("Ihre Session ist ungültig.");

// Not Blocked, request Logging
if ($config->LOG_ALL && in_array($modus, $config->logModusInclude)){
  $query = "INSERT INTO Log_".date("w")." (playerID, caveID, ip, request, sessionID)" .
           " VALUES ('" .$params->SESSION->user['playerID']. "', '$caveID', '$_SERVER[REMOTE_ADDR]'," .
           " '".addslashes(var_export($params->POST, TRUE))."', '" . session_id() . "')";
  $db->query($query);
}

///////////////////////////////////////////////////////////////////////////////
// print all Session messages                                               //
///////////////////////////////////////////////////////////////////////////////

if (sizeof($params->SESSION->messages)){
  foreach ($params->SESSION->messages AS $sess_mess)
    echo $sess_mess . "<br>";
  $_SESSION['messages'] = array();
}
///////////////////////////////////////////////////////////////////////////////


/* ************************************************************************* */

// FRAMEWORK ANLEGEN
$template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/main.ihtml');

// CONTENT EINPARSEN

switch ($modus){

  /////////////////////////////////////////////////////////////////////////////
  // UEBERSICHTEN                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case NO_CAVE_LEFT:
    tmpl_set($template, 'pagetitle', 'Keine Siedlungen mehr');
    $content = "Leider besitzen sie keine Siedlungen mehr. " .
               "L&ouml;schen sie diesen Account, " .
               "und legen sie sich einen neuen an.";
    break;

  case NOT_MY_CAVE:
    tmpl_set($template, 'pagetitle', 'Fehler');
    $content = "Diese Siedlung geh&ouml;rt nicht ihnen.";
    break;

  case CAVE_DETAIL:
    tmpl_set($template, 'pagetitle', 'Siedlungsdetails');
    $content = getCaveDetailsContent($meineHoehlen[$caveID]);
    break;

  case ALL_CAVE_DETAIL:
    tmpl_set($template, 'pagetitle', 'Siedlungs-&Uuml;bersicht');
    $content = getAllCavesDetailsContent($meineHoehlen);
    break;

  case CAVE_GIVE_UP_CONFIRM:
    tmpl_set($template, 'pagetitle', 'Siedlung aufgeben');
    $content = cave_giveUpConfirm($params->POST->giveUpCaveID);
    break;

  case END_PROTECTION_CONFIRM:
    tmpl_set($template, 'pagetitle', 'Anf&auml;ngerschutz beenden');
    $content = beginner_endProtectionConfirm($caveID);
    break;

  case EASY_DIGEST:
    tmpl_set($template, 'pagetitle', 'Termin-&Uuml;bersicht');
    $content = digest_getDigest($meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // ARTEFAKTE                                                               //
  /////////////////////////////////////////////////////////////////////////////

  case ARTEFACT_DETAIL:
    tmpl_set($template, 'pagetitle', 'Artefaktdetail');
    $content = artefact_getDetail($caveID, $meineHoehlen, $params->POST->artefactID);
    break;
  case ARTEFACT_LIST:
    tmpl_set($template, 'pagetitle', 'Artefakte');
    $content = artefact_getList($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // NACHRICHTEN                                                             //
  /////////////////////////////////////////////////////////////////////////////

  case MESSAGES:
    tmpl_set($template, 'pagetitle', 'Nachrichten');
    $content = messages_getMessages($caveID, $params->POST->deletebox, $params->POST->box);
    break;

  case MESSAGESDETAIL:
    tmpl_set($template, 'pagetitle', 'Nachricht lesen');
    $content = messages_showMessage($caveID, $params->POST->messageID, $params->POST->box);
    break;

// ADDED by chris--- something for adressbook ($playerID)

  case NEW_MESSAGE:
    tmpl_set($template, 'pagetitle', 'Nachricht schreiben');
    $content = messages_newMessage($caveID, $playerID);
    break;

  case NEW_MESSAGE_RESPONSE:
    tmpl_set($template, 'pagetitle', 'Verschicken einer Nachricht');
    $content = messages_sendMessage($caveID);
    break;

// ADDED by chris--- for adressbook

  case MESSAGE_BOOK:
    tmpl_set($template, 'pagetitle', 'Adressbuch');
    $delete = FALSE;
    $content = show_adressbook($params->SESSION->user['playerID'], $delete);
    break;

  case MESSAGE_BOOK_DELETE:
    tmpl_set($template, 'pagetitle', 'Adressbuch');
    $content = show_adressbook($params->SESSION->user['playerID'], $delete);
    break;

  case MESSAGE_BOOK_ADD:
    tmpl_set($template, 'pagetitle', 'Adressbuch');
    $delete = FALSE;
    $content = show_adressbook($params->SESSION->user['playerID'], $delete);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // KARTEN                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case MAP:
    tmpl_set($template, 'pagetitle', 'Karte');
    $content = getCaveMapContent($meineHoehlen, $caveID, $params->SESSION->user['playerID']);
    break;

  case MAP_DETAIL:
    tmpl_set($template, 'pagetitle', 'Siedlungsbericht');
    $content = getCaveReport($meineHoehlen, $caveID, $params->POST->targetCaveID, $params->SESSION->user['playerID']);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // ERWEITERUNGEN                                                           //
  /////////////////////////////////////////////////////////////////////////////

  case IMPROVEMENT_DETAIL:
    tmpl_set($template, 'pagetitle', 'Erweiterungen errichten');
    $content = improvement_getImprovementDetail($caveID, $meineHoehlen[$caveID]);
    break;

  case IMPROVEMENT_BREAK_DOWN:
    tmpl_set($template, 'pagetitle', 'Erweiterungen einreissen');
    $content = improvement_deleteConfirm($caveID, $params->POST->buildingID);
    break;

  case IMPROVEMENT_BUILDING_DETAIL:
    tmpl_set($template, 'pagetitle', 'Geb&auml;udeerweiterungen');
    $content = improvement_getBuildingDetails($params->POST->buildingID, $meineHoehlen[$caveID]);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // WONDERS                                                                 //
  /////////////////////////////////////////////////////////////////////////////

  case WONDER:
    tmpl_set($template, 'pagetitle', 'Zauber erwirken');
    $content = wonder_getWonderContent($params->SESSION->user['playerID'], 
				       $caveID, $meineHoehlen[$caveID]);
    break;

  case WONDER_DETAIL:
    tmpl_set($template, 'pagetitle', 'Zauber');
    $content = wonder_getWonderDetailContent($params->POST->wonderID, 
					     $meineHoehlen[$caveID]);
    break;


  /////////////////////////////////////////////////////////////////////////////
  // VERTEIDIGUNGSANLAGEN                                                    //
  /////////////////////////////////////////////////////////////////////////////

  case DEFENSESYSTEM:
    tmpl_set($template, 'pagetitle', 
	     'Verteidigungen und externe Geb&auml;ude errichten');
    $content = defenseSystem_getDefenseSystemDetail($caveID, 
						    $meineHoehlen[$caveID]);
    break;

  case DEFENSESYSTEM_BREAK_DOWN:
    tmpl_set($template, 'pagetitle', 
	     'Verteidigungen und externe Geb&auml;ude einreissen');
    $content = defenseSystem_deleteConfirm($caveID, $params->POST->defenseSystemID);
    break;

  case DEFENSESYSTEM_DETAIL:
    tmpl_set($template, 'pagetitle', 
	     'Verteidigungen und externe Geb&auml;ude');
    $content = defenseSystem_getDefenseSystemDetails($params->POST->defenseSystemID, $meineHoehlen[$caveID]);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // WISSENSCHAFT                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case SCIENCE:
    tmpl_set($template, 'pagetitle', 'Wissen erforschen');
    $content = science_getScienceDetail($caveID, $meineHoehlen[$caveID]);
    break;

  case SCIENCE_DETAIL:
    tmpl_set($template, 'pagetitle', 'Forschungen');
    $content = science_getScienceDetails($params->POST->scienceID, $meineHoehlen[$caveID]);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // EINHEITEN                                                               //
  /////////////////////////////////////////////////////////////////////////////

  case UNIT_BUILDER:
    tmpl_set($template, 'pagetitle', 'Einheiten bauen');
    $content = unit_getUnitDetail($caveID, $meineHoehlen[$caveID]);
    break;


  case UNIT_PROPERTIES:
    tmpl_set($template, 'pagetitle', 'Einheitsattribute');
    $content = unit_showUnitProperties($params->POST->unitID, $meineHoehlen[$caveID]);
    break;

  case MOVEMENT:
    tmpl_set($template, 'pagetitle', 'Einheiten bewegen');
    $content = unitAction($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // MISSIONIEREN                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case TAKEOVER:
    tmpl_set($template, 'pagetitle', 'F&uuml;r eine Siedlung bieten');
    $content = takeover_getContent($params->SESSION->user['playerID'], $caveID, $params->POST->xCoord, $params->POST->yCoord);
    break;

  case TAKEOVER_CHANGE:
    tmpl_set($template, 'pagetitle', 'F&uuml;r eine Siedlung bieten');
    $content = takeover_changeConfirm($params->SESSION->user['playerID'], $params->POST->xCoord, $params->POST->yCoord, $params->POST->currentXCoord, $params->POST->currentYCoord);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // PUNKTZAHLEN                                                             //
  /////////////////////////////////////////////////////////////////////////////

  case RANKING:
    tmpl_set($template, 'pagetitle', 'Spielerranking');
    $offset  = ranking_checkOffset($params->SESSION->user['playerID'], $params->POST->offset);
    $content = ranking_getContent($caveID, $offset);
    break;

  case RANKING_TRIBE:
    tmpl_set($template, 'pagetitle', 'Clanranking');
    $offset  = rankingTribe_checkOffset($params->POST->offset);
    $content = rankingTribe_getContent($caveID, $offset);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // TRIBES                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case TRIBE:
    tmpl_set($template, 'pagetitle', 'Clans');
    $content = tribe_getContent($params->SESSION->user['playerID'],
		   	        $params->SESSION->user['tribe']);
    break;

  case TRIBE_ADMIN:
    tmpl_set($template, 'pagetitle', 'Clan verwalten');
    $content = tribeAdmin_getContent($params->SESSION->user['playerID'],
				     $params->SESSION->user['tribe']);
    break;

  case TRIBE_RELATION_LIST:
    tmpl_set($template, 'pagetitle', 'Beziehungen');
    $content = tribeRelationList_getContent($params->POST->tag);
    break;

  case TRIBE_HISTORY:
    tmpl_set($template, 'pagetitle', 
	     'Clangeschichte');
    $content = tribeHistory_getContent($params->POST->tag);
    break;


  case TRIBE_DELETE:
    tmpl_set($template, 'pagetitle', 'Clan verwalten');
    $content = tribeDelete_getContent($params->SESSION->user['playerID'],
				      $params->SESSION->user['tribe'],
				      $params->POST->confirm);
    break;

  case TRIBE_LEADER_DETERMINATION:
    tmpl_set($template, 'pagetitle', 
	     'Clananf&uuml;hrer bestimmen');
    $content = tribeLeaderDetermination_getContent(
      $params->SESSION->user['playerID'],
      $params->SESSION->user['tribe'],
      $params->POST->data);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // FRAGEBÖGEN                                                              //
  /////////////////////////////////////////////////////////////////////////////

  case QUESTIONNAIRE:
    tmpl_set($template, 'pagetitle', 'Fragebogen');
    $content = questionnaire_getQuestionnaire($caveID, $meineHoehlen);
    break;

  case QUESTIONNAIRE_PRESENTS:
    tmpl_set($template, 'pagetitle', 'Fragebogen Treuebonus');
    $content = questionnaire_presents($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // HELDEN                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case HERO_DETAIL:
    tmpl_set($template, 'pagetitle', 'Mein Held');
    $content = hero_getHeroDetail($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // QUESTS - ADDED by chris---                                                    //
  /////////////////////////////////////////////////////////////////////////////

  case QUEST:
    tmpl_set($template, 'pagetitle', 'Quests');
    $content = quest_getQuestOverview($params->SESSION->user['playerID']);
    break;

  case QUEST_DETAIL:
    tmpl_set($template, 'pagetitle', 'Quest Details');
    $content = quest_getQuestDetails($params->POST->questID, $params->SESSION->user['playerID']);
    break;

  case QUEST_HELP:
    tmpl_set($template, 'pagetitle', 'Quest Hilfe');
    $content = quest_getQuestHelp($params->SESSION->user['playerID']);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // TICKER - ADDED by chris---                                                    //
  /////////////////////////////////////////////////////////////////////////////

  case TICKER_ENTRY:
    tmpl_set($template, 'pagetitle', 'Nachricht eintragen');
    $content = ticker_newEntry($params->SESSION->user['playerID']);
    break;

  case TICKER_ARCHIVE:
    tmpl_set($template, 'pagetitle', 'Ticker Nachrichtenarchiv');
    $content = ticker_getMessages($params->SESSION->user['playerID']);
    break;

  case NEW_TICKER_MESSAGE_RESPONSE:
    tmpl_set($template, 'pagetitle', 'Verschicken einer Nachricht');
    $content = ticker_sendMessage($params->SESSION->user['playerID']);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // STATS - ADDED by chris---                                                     //
  /////////////////////////////////////////////////////////////////////////////

  case STATS:
    tmpl_set($template, 'pagetitle', 'Statistiken');
    $content = stats_stats($params->SESSION->user['playerID']);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // AWARDS                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case AWARD_DETAIL:
    tmpl_set($template, 'pagetitle', 'Auszeichnung');
    $content = award_getAwardDetail($params->POST->award);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // DIVERSES                                                                //
  /////////////////////////////////////////////////////////////////////////////


// ADDED by chris--- for cavebook

  case CAVE_BOOK:
    tmpl_set($template, 'pagetitle', 'Siedlungsliste');
    $delete = FALSE;
    $content = show_cavebook($params->SESSION->user['playerID'], $delete);
    break;

  case CAVE_BOOK_DELETE:
    tmpl_set($template, 'pagetitle', 'Siedlungsliste');
    $content = show_cavebook($params->SESSION->user['playerID'], $delete);
    break;

  case CAVE_BOOK_ADD:
    tmpl_set($template, 'pagetitle', 'Siedlungsliste');
    $delete = FALSE;
    $content = show_cavebook($params->SESSION->user['playerID'], $delete);
    break;




  case EFFECTWONDER_DETAIL:
    tmpl_set($template, 'pagetitle', 'Aktive Effekte und Zauber');
    $content = effect_getEffectWonderDetailContent($caveID,
						   $meineHoehlen[$caveID]);
    break;

  case USER_PROFILE:
    tmpl_set($template, 'pagetitle', 'Benutzerprofil');
    $content = profile_getContent($params->SESSION->user['playerID']);
    break;

  case DELETE_ACCOUNT:
    tmpl_set($template, 'pagetitle', 'Account l&ouml;schen');
    $content = profile_deleteAccount($params->SESSION->user['playerID'], 
				     $params->POST);
    break;

  case PLAYER_DETAIL:
    tmpl_set($template, 'pagetitle', 'Spielerbeschreibung');
    $content = player_getContent($caveID, $params->POST->detailID);
    break;

  case TRIBE_DETAIL:
    tmpl_set($template, 'pagetitle', 'Clanbeschreibung');
    $content = tribe_getContent($caveID, $params->POST->tribe);
    break;

  case TRIBE_PLAYER_LIST:
    tmpl_set($template, 'pagetitle', 'Clanmitglieder...');
    $content = tribePlayerList_getContent($caveID, $params->POST->tag);
    break;

  case LOGOUT:
    $query = "UPDATE Player SET last_logout = UNIX_TIMESTAMP() " .
             "WHERE playerID = " . ((int)$params->SESSION->user['playerID']);
    $db->query($query);
    session_destroy();
    Header("Location:	logout.php");
    break;

  /////////////////////////////////////////////////////////////////////////////
  /////////////////////////////////////////////////////////////////////////////
  /////////////////////////////////////////////////////////////////////////////

  default:
    tmpl_set($template, 'pagetitle', 'Modus nicht bekannt');
    $content = "Modus " . $modus . " CaveID " . $caveID;
}

tmpl_set($template, 'content', $content);

// RESOURCEN ZEIGEN

if ($no_resource_flag) tmpl_set($template, "NOTICKER", array('dummy' => ""));

if (!$no_resource_flag) {


// ADDED by chris--- for ticker
  if ($params->SESSION->user['show_ticker']) {

    // Getting the last 20 messages
    $ticker_text = ticker_text();

    tmpl_set($template, "/", array('ticker_text' => $ticker_text));

    tmpl_set($template, "TICKER", array('ticker_text' => $ticker_text,
					'link1'	      => TICKER_ENTRY,
					'link2'	      => TICKER_ARCHIVE));
  } else {
    tmpl_set($template, "NOTICKER", array('dummy' => ""));
  }



  $resources = array();
  for ($i = 0; $i < sizeof($resourceTypeList); ++$i){
    $delta = $meineHoehlen[$caveID][$resourceTypeList[$i]->dbFieldName . "_delta"];
    if ($delta > 0) $delta = "+" . $delta;

//echo formula_parseToPHP("{$resourceTypeList[$i]->maxLevel};", '$meineHoehlen[$caveID]')."<br>";

$maxlevel = round(eval('return ' . formula_parseToPHP("{$resourceTypeList[$i]->maxLevel};", '$meineHoehlen[$caveID]')));
$prozent = round(floor($meineHoehlen[$caveID][$resourceTypeList[$i]->dbFieldName])/$maxlevel*100,0);

    $resources[$i] = array('dbFieldName'   => $resourceTypeList[$i]->dbFieldName,
                           'name'          => $resourceTypeList[$i]->name,
                           'amount'        => floor($meineHoehlen[$caveID][$resourceTypeList[$i]->dbFieldName]),
                           'delta'         => $delta,
                           'maxLevel'      => $maxlevel,
			   'prozent'	   => $prozent);
}
  tmpl_set($template, "RESOURCES/RESOURCE", $resources);
}

tmpl_set($template, "", array('cave_name'    => $meineHoehlen[$caveID]['name'],
                              'cave_x_coord' => $meineHoehlen[$caveID]['xCoord'],
                              'cave_y_coord' => $meineHoehlen[$caveID]['yCoord']));

$gfx = $params->SESSION->nogfx ? DEFAULT_GFX_PATH : $params->SESSION->user['gfxpath'];
echo str_replace ('%gfx%', $gfx, tmpl_parse($template));

page_end();
?>
