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

/** Set flag that this is a parent file */
define("_VALID_UA", 1);

require_once("config.inc.php");

require_once("include/page.inc.php");
require_once("include/db.functions.php");
require_once("include/time.inc.php");
require_once("include/basic.lib.php");
require_once("include/vote.html.php");
require_once("modules/Messages/Messages.php");

page_start();

// session expired?
if (page_sessionExpired($params))
  page_error403("Sie waren für " . ((int)(SESSION_MAX_LIFETIME/60)) . " Minuten oder mehr inaktiv. Letzte Aktion um " . date("H:i:s", $params->SESSION->lastAction . " Uhr."));
else
  $_SESSION['lastAction'] = time();

// session valid?
if (!page_sessionValidate($params, $config))
  page_error403(__FILE__ . ":" . __LINE__ . ": Session ist ungültig.");

// get modus
$modus = page_getModus($params, $config);

// get caves
$caveID = $params->SESSION->caveID;
$meineHoehlen = getCaves($params->SESSION->player->playerID);

// no caves left
if (!$meineHoehlen){
  if (!in_array($modus, $config->noCaveModusInclude))
    $modus = NO_CAVE_LEFT;

} else {

  // caveID is not sent
  if ($caveID == NULL){
    $temp = current($meineHoehlen);
    $caveID = $temp['caveID'];
    $_SESSION['caveID'] = $caveID;
    $params->SESSION->caveID = $caveID;
  }
  // my cave?
  if (!array_key_exists($caveID, $meineHoehlen)){
    $modus = NOT_MY_CAVE;
    $_SESSION['caveID'] = NULL;
    $params->SESSION->caveID = NULL;
  }
}

// include required files
if (is_array($require_files[$modus]))
  foreach($require_files[$modus] as $file)
    require_once('include/' . $file);

// log request
page_logRequest($modus, $caveID);

// log ore
page_ore();

################################################################################


///////////////////////////////////////////////////////////////////////////////

switch ($modus){

  /////////////////////////////////////////////////////////////////////////////
  // UEBERSICHTEN                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case NO_CAVE_LEFT:
    $pagetitle = _("Keine Höhle mehr");
    $content = _("Leider besitzen sie keine Höhle mehr.");
    break;

  case NOT_MY_CAVE:
    $pagetitle = _("Fehler");
    $content = _("Diese Höhle gehört nicht ihnen.");
    break;

  case CAVE_DETAIL:
    $pagetitle = _("Höhlendetails");
    $content = getCaveDetailsContent($meineHoehlen[$caveID]);
    break;

  case ALL_CAVE_DETAIL:
    $pagetitle = _("Höhlen-Übersicht");
    $content = getAllCavesDetailsContent($meineHoehlen);
    break;

  case CAVE_GIVE_UP_CONFIRM:
    $pagetitle = _("Höhle aufgeben");
    $content = cave_giveUpConfirm($meineHoehlen[$params->POST->giveUpCaveID]);
    break;

  case END_PROTECTION_CONFIRM:
    $pagetitle = _("Anfängerschutz beenden");
    $content = beginner_endProtectionConfirm($meineHoehlen[$caveID]);
    break;

  case EASY_DIGEST:
    $pagetitle = _("Termin-Übersicht");
    $content = digest_getDigest($meineHoehlen);
    break;

  case EVENT_REPORTS:
    list($pagetitle, $content) = eventReports_main($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // ARTEFAKTE                                                               //
  /////////////////////////////////////////////////////////////////////////////

  case ARTEFACT_DETAIL:
    $pagetitle = _("Artefaktdetail");
    $content = artefact_getDetail($caveID, $meineHoehlen, $params->POST->artefactID);
    break;
  case ARTEFACT_LIST:
    $pagetitle = _("Artefakte");
    $content = artefact_getList($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // NACHRICHTEN                                                             //
  /////////////////////////////////////////////////////////////////////////////

  case MESSAGES:
    $pagetitle = _("Nachrichten");
    $content = messages_getMessages($caveID, $params->POST->deletebox, $params->POST->box);
    break;

  case MESSAGESDETAIL:
    $pagetitle = _("Nachricht lesen");
    $content = messages_showMessage($caveID, $params->POST->messageID, $params->POST->box);
    break;

  case NEW_MESSAGE:
    $pagetitle = _("Nachricht schreiben");
    $content = messages_newMessage($caveID);
    break;

  case NEW_MESSAGE_RESPONSE:
    $pagetitle = _("Verschicken einer Nachricht");
    $content = messages_sendMessage($caveID);
    break;

  case CONTACTS:
    list($pagetitle, $content) = contacts_main($caveID, $meineHoehlen);
    break;

  case CAVE_BOOKMARKS:
    list($pagetitle, $content) = cavebookmarks_main($caveID, $meineHoehlen);
    break;

  case DONATIONS:
    list($pagetitle, $content) = donations_main($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // KARTEN                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case MAP:
    $pagetitle = _("Höhlenkarte");
    $content = getCaveMapContent($meineHoehlen, $caveID);
    break;

  case MAP_DETAIL:
    $pagetitle = _("Höhlenbericht");
    $content = getCaveReport($meineHoehlen, $caveID, $params->POST->targetCaveID);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // ERWEITERUNGEN                                                           //
  /////////////////////////////////////////////////////////////////////////////

  case IMPROVEMENT_DETAIL:
    $pagetitle = _("Erweiterungen errichten");
    $content = improvement_getImprovementDetail($caveID, $meineHoehlen[$caveID]);
    break;

  case IMPROVEMENT_BREAK_DOWN:
    $pagetitle = _("Erweiterungen einreissen");
    $content = improvement_deleteConfirm($caveID, $params->POST->buildingID);
    break;

  case IMPROVEMENT_BUILDING_DETAIL:
    $pagetitle = _("Gebäudeerweiterungen");
    $content = improvement_getBuildingDetails($params->POST->buildingID, $meineHoehlen[$caveID]);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // WONDERS                                                                 //
  /////////////////////////////////////////////////////////////////////////////

  case WONDER:
    $pagetitle = _("Wunder erwirken");
    $content = wonder_getWonderContent($params->SESSION->player->playerID, $caveID, $meineHoehlen[$caveID]);
    break;

  case WONDER_DETAIL:
    $pagetitle = _("Wunder");
    $content = wonder_getWonderDetailContent($params->POST->wonderID, $meineHoehlen[$caveID]);
    break;


  /////////////////////////////////////////////////////////////////////////////
  // VERTEIDIGUNGSANLAGEN                                                    //
  /////////////////////////////////////////////////////////////////////////////

  case EXTERNAL_BUILDER:
    $pagetitle = _("Verteidigungsanlagen und externe Gebäude errichten");
    $content = externals_builder($caveID, $meineHoehlen[$caveID]);
    break;

  case EXTERNAL_DEMOLISH:
    $pagetitle = _("Verteidigungsanlagen und externe Gebäude einreissen");
    $content = externals_demolish();
    break;

  case EXTERNAL_PROPERTIES:
    $pagetitle = _("Verteidigungsanlagen und externe Gebäude");
    $content = externals_showProperties($meineHoehlen[$caveID]);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // WISSENSCHAFT                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case SCIENCE:
    $pagetitle = _("Wissen erforschen");
    $content = science_getScienceDetail($caveID, $meineHoehlen[$caveID]);
    break;

  case SCIENCE_DETAIL:
    $pagetitle = _("Forschungen");
    $content = science_getScienceDetails($params->POST->scienceID, $meineHoehlen[$caveID]);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // EINHEITEN                                                               //
  /////////////////////////////////////////////////////////////////////////////

  case UNIT_BUILDER:
    $pagetitle = _("Einheiten bauen");
    $content = unit_getUnitDetail($caveID, $meineHoehlen[$caveID]);
    break;


  case UNIT_PROPERTIES:
    $pagetitle = _("Einheitsattribute");
    $content = unit_showUnitProperties($params->POST->unitID, $meineHoehlen[$caveID]);
    break;

  case UNIT_MOVEMENT:
    $pagetitle = _("Einheiten bewegen");
    $content = unitAction($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // MISSIONIEREN                                                            //
  /////////////////////////////////////////////////////////////////////////////

  case TAKEOVER:
    $pagetitle = _("Missionieren");
    $content = takeover_main($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // PUNKTZAHLEN                                                             //
  /////////////////////////////////////////////////////////////////////////////

  case RANKING:
    $pagetitle = _("Spielerranking");
    $offset  = ranking_checkOffset($params->SESSION->player->playerID, $params->POST->offset);
    $content = ranking_getContent($caveID, $offset);
    break;

  case RANKING_TRIBE:
    $pagetitle = _("Stammesranking");
    $offset  = rankingTribe_checkOffset($params->POST->offset);
    $content = rankingTribe_getContent($caveID, $offset);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // TRIBES                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case TRIBE:
    $pagetitle = _("Stämme");
    $content = tribe_getContent($params->SESSION->player->playerID, $params->SESSION->player->tribe);
    break;

  case TRIBE_ADMIN:
    $pagetitle = _("Stamm verwalten");
    $content = tribeAdmin_getContent($params->SESSION->player->playerID, $params->SESSION->player->tribe);
    break;

  case TRIBE_RELATION_LIST:
    $pagetitle = _("Beziehungen");
    $content = tribeRelationList_getContent($params->POST->tag);
    break;

  case TRIBE_HISTORY:
    $pagetitle = _("Stammesgeschichte");
    $content = tribeHistory_getContent($params->POST->tag);
    break;

  case TRIBE_DELETE:
    $pagetitle = _("Stamm verwalten");
    $content = tribeDelete_getContent($params->SESSION->player->playerID, $params->SESSION->player->tribe, $params->POST->confirm);
    break;

  case TRIBE_LEADER_DETERMINATION:
    $pagetitle = _("Stammesanführer bestimmen");
    $content = tribeLeaderDetermination_getContent($params->SESSION->player->playerID, $params->SESSION->player->tribe, $params->POST->data);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // FRAGEBÖGEN                                                              //
  /////////////////////////////////////////////////////////////////////////////

  case QUESTIONNAIRE:
    $pagetitle = _("Fragebogen");
    $content = questionnaire_getQuestionnaire($caveID, $meineHoehlen);
    break;

  case QUESTIONNAIRE_PRESENTS:
    $pagetitle = _("Fragebogen Treuebonus");
    $content = questionnaire_presents($caveID, $meineHoehlen);
    break;

  case SUGGESTIONS:
    list($pagetitle, $content) = suggestions_main($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // HELDEN                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case HERO_DETAIL:
    $pagetitle = _("Mein Held");
    $content = hero_getHeroDetail($caveID, $meineHoehlen);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // AWARDS                                                                  //
  /////////////////////////////////////////////////////////////////////////////

  case AWARD_DETAIL:
    $pagetitle = _("Auszeichnung");
    $content = award_getAwardDetail($params->POST->award);
    break;

  /////////////////////////////////////////////////////////////////////////////
  // DIVERSES                                                                //
  /////////////////////////////////////////////////////////////////////////////

  case EFFECTWONDER_DETAIL:
    $pagetitle = _("Aktive Effekte und Wunder");
    $content = effect_getEffectWonderDetailContent($caveID, $meineHoehlen[$caveID]);
    break;

  case WEATHER_REPORT:
    $pagetitle = _('Wetterbericht');
    $content = weather_getReport();
    break;

  case USER_PROFILE:
    $pagetitle = _("Einstellungen");
    $content = profile_main($caveID, $meineHoehlen);
    break;

  case DELETE_ACCOUNT:
    $pagetitle = _("Account löschen");
    $content = profile_deleteAccount($params->SESSION->player->playerID, $params->POST);
    break;

  case PLAYER_DETAIL:
    $pagetitle = _("Spielerbeschreibung");
    $content = player_getContent($caveID, $params->POST->detailID);
    break;

  case TRIBE_DETAIL:
    $pagetitle = _("Stammesbeschreibung");
    $content = tribe_getContent($caveID, $params->POST->tribe);
    break;

  case TRIBE_PLAYER_LIST:
    $pagetitle = _("Stammesmitglieder ...");
    $content = tribePlayerList_getContent($caveID, $params->POST->tag);
    break;

  case DYK:
    $pagetitle = _("Infos rund um Uga-Agga");
    $content = doYouKnow_getContent();
  break;
  
  case STATS:
    $pagetitel = _("Statistiken");
	$content = stats_stats($params->SESSION->player->playerID);

  case LOGOUT:
    session_destroy();
    Header("Location: logout.php");
    break;

  /////////////////////////////////////////////////////////////////////////////
  /////////////////////////////////////////////////////////////////////////////
  /////////////////////////////////////////////////////////////////////////////

  default:
    $pagetitle = _("Modus nicht bekannt");
    $content = "Modus " . $modus . " CaveID " . $caveID;
}

// prepare resource bar
$resources = array();
if (!$no_resource_flag && isset($resourceTypeList)){
  foreach ($resourceTypeList as $resource){
    $amount = floor($meineHoehlen[$caveID][$resource->dbFieldName]);
    if (!$resource->nodocumentation || $amount > 0) {
      $delta = $meineHoehlen[$caveID][$resource->dbFieldName . "_delta"];
      if ($delta > 0) $delta = "+" . $delta;
      $resources[] = array('dbFieldName'   => $resource->dbFieldName,
                           'name'          => $resource->name,
                           'amount'        => $amount,
                           'delta'         => $delta,
                           'maxLevel'      => round(eval('return ' . formula_parseToPHP("{$resource->maxLevel};", '$meineHoehlen[$caveID]'))));
    }
  }
}

// prepare new mail
list($nm_title, $nm_content) = messages_main($caveID, $meineHoehlen);

// prepare next and previous cave
$keys = array_keys($meineHoehlen);
$pos =  array_search($caveID, $keys);
$prev = isset($keys[$pos - 1]) ? $keys[$pos - 1] : $keys[count($keys)-1];
$next = isset($keys[$pos + 1]) ? $keys[$pos + 1] : $keys[0];

// open template
$template = tmpl_open($params->SESSION->player->getTemplatePath() . 'main.ihtml');

// fill it
tmpl_set($template, array('pagetitle'    => $pagetitle,
                          'content'      => $content,
                          'cave_name'    => $meineHoehlen[$caveID]['name'],
                          'cave_x_coord' => $meineHoehlen[$caveID]['xCoord'],
                          'cave_y_coord' => $meineHoehlen[$caveID]['yCoord'],
                          'bottom'       => vote_main(),
                          'new_mail'     => $nm_content,
                          'rules_path'   => RULES_PATH,
                          'help_path'    => HELP_PATH));

if (sizeof($resources)) tmpl_set($template, '/RESOURCES/RESOURCE', $resources);

if (!is_null($prev))
  tmpl_set($template, '/PREVCAVE', array('id' => $prev, 'name' => $meineHoehlen[$prev]['name']));

if (!is_null($next))
  tmpl_set($template, '/NEXTCAVE', array('id' => $next, 'name' => $meineHoehlen[$next]['name']));

// globally set GFX_PATH and output parsed template
$gfx = $params->SESSION->nogfx ? DEFAULT_GFX_PATH : $params->SESSION->player->gfxpath;
echo str_replace ('%gfx%', $gfx, tmpl_parse($template));

// close page
page_end();
?>
