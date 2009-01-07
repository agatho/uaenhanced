<?
/*
 * modus.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');


DEFINE('ALL_CAVE_DETAIL',             'all_cave_detail');
DEFINE('ARTEFACT_DETAIL',             'artefact_detail');
DEFINE('ARTEFACT_LIST',               'artefact_list');
DEFINE('AWARD_DETAIL',                'award_detail');
DEFINE('CAVE_BOOKMARKS',              'CaveBookmarks');
DEFINE('CAVE_DETAIL',                 'cave_detail');
DEFINE('CAVE_GIVE_UP_CONFIRM',        'cave_give_up_confirm');
DEFINE('CONTACTS',                    'Contacts');
DEFINE('DONATIONS',                   'Donations');
DEFINE('EVENT_REPORTS',               'EventReports');
DEFINE('EXTERNAL_BUILDER',            'external_builder');
DEFINE('EXTERNAL_DEMOLISH',           'external_demolish');
DEFINE('EXTERNAL_PROPERTIES',         'external_properties');
DEFINE('DELETE_ACCOUNT',              'delete_account');
DEFINE('EASY_DIGEST',                 'easy_digest');
DEFINE('EFFECTWONDER_DETAIL',         'effectwonder_detail');
DEFINE('END_PROTECTION_CONFIRM',      'end_protection_confirm');
DEFINE('HERO_DETAIL',                 'hero_detail');
DEFINE('IMPROVEMENT_BREAK_DOWN',      'improvement_break_down');
DEFINE('IMPROVEMENT_BUILDING_DETAIL', 'improvement_building_detail');
DEFINE('IMPROVEMENT_DETAIL',          'improvement_detail');
DEFINE('LOGOUT',                      'logout');
DEFINE('MAP',                         'map');
DEFINE('MAP_DETAIL',                  'map_detail');
DEFINE('MESSAGES',                    'messages');
DEFINE('MESSAGESDETAIL',              'messagesdetail');
DEFINE('NEW_MESSAGE',                 'new_message');
DEFINE('NEW_MESSAGE_RESPONSE',        'new_message_response');
DEFINE('NO_CAVE_LEFT',                'no_cave_left');
DEFINE('NOT_MY_CAVE',                 'not_my_cave');
DEFINE('PLAYER_DETAIL',               'player_detail');
DEFINE('QUESTIONNAIRE',               'questionnaire');
DEFINE('QUESTIONNAIRE_PRESENTS',      'questionnaire_presents');
DEFINE('RANKING',                     'ranking');
DEFINE('RANKING_TRIBE',               'ranking_tribe');
DEFINE('SCIENCE',                     'science');
DEFINE('SCIENCE_DETAIL',              'science_detail');
DEFINE('SUGGESTIONS',                 'Suggestions');
DEFINE('TAKEOVER',                    'takeover');
DEFINE('TRIBE',                       'tribe');
DEFINE('TRIBE_ADMIN',                 'tribe_admin');
DEFINE('TRIBE_DELETE',                'tribe_delete');
DEFINE('TRIBE_DETAIL',                'tribe_detail');
DEFINE('TRIBE_HISTORY',               'tribe_history');
DEFINE('TRIBE_LEADER_DETERMINATION',  'tribe_leader_determination');
DEFINE('TRIBE_PLAYER_LIST',           'tribe_player_list');
DEFINE('TRIBE_RELATION_LIST',         'tribe_relation_list');
DEFINE('UNIT_BUILDER',                'unit_builder');
DEFINE('UNIT_MOVEMENT',               'unit_movement');
DEFINE('UNIT_PROPERTIES',             'unit_properties');
DEFINE('USER_PROFILE',                'user_profile');
DEFINE('VOTE'        ,                'vote');
DEFINE('WEATHER_REPORT',              'weather_report');
DEFINE('WONDER',                      'wonder');
DEFINE('WONDER_DETAIL',               'wonder_detail');
DEFINE('DYK',                         'doYouKnow');
DEFINE('STATS',                       'stats');

$require_files = array();

$require_files[ALL_CAVE_DETAIL]             = array('cave.html.php', 'formula_parser.inc.php');
$require_files[ARTEFACT_DETAIL]             = array('formula_parser.inc.php', 'artefact.html.php', 'artefact.inc.php');
$require_files[ARTEFACT_LIST]               = array('formula_parser.inc.php', 'artefact.html.php', 'artefact.inc.php');
$require_files[AWARD_DETAIL]                = array('award.html.php');
$require_files[CAVE_BOOKMARKS]              = array('../modules/CaveBookmarks/CaveBookmarks.php');
$require_files[CAVE_DETAIL]                 = array('cave.html.php', 'formula_parser.inc.php', 'tribes.inc.php', 'relation_list.php');
$require_files[CAVE_GIVE_UP_CONFIRM]        = array('cave.html.php', 'formula_parser.inc.php');
$require_files[CONTACTS]                    = array('../modules/Contacts/Contacts.php');
$require_files[DONATIONS]                   = array('../modules/Donations/Donations.php');
$require_files[EXTERNAL_BUILDER]            = array('formula_parser.inc.php', 'externals.html.php');
$require_files[EXTERNAL_DEMOLISH]           = array('formula_parser.inc.php', 'externals.html.php');
$require_files[EXTERNAL_PROPERTIES]         = array('formula_parser.inc.php', 'externals.html.php');
$require_files[DELETE_ACCOUNT]              = array('delete.html.php');
$require_files[EASY_DIGEST]                 = array('artefact.inc.php', 'formula_parser.inc.php', 'digest.html.php', 'digest.inc.php', 'movement.lib.php');
$require_files[EFFECTWONDER_DETAIL]         = array('formula_parser.inc.php', 'wonder.rules.php', 'effectWonderDetail.html.php', 'wonder.inc.php');
$require_files[END_PROTECTION_CONFIRM]      = array('cave.html.php', 'formula_parser.inc.php');
$require_files[EVENT_REPORTS]               = array('../modules/EventReports/EventReports.php');
$require_files[HERO_DETAIL]                 = array('hero.html.php');
$require_files[IMPROVEMENT_BREAK_DOWN]      = array('formula_parser.inc.php', 'improvementDeleteConfirm.html.php', 'improvement.inc.php');
$require_files[IMPROVEMENT_BUILDING_DETAIL] = array('formula_parser.inc.php', 'improvement_building_detail.html.php', 'improvement.inc.php');
$require_files[IMPROVEMENT_DETAIL]          = array('formula_parser.inc.php', 'improvement.html.php', 'improvement.inc.php');
$require_files[LOGOUT]                      = array();
$require_files[MAP]                         = array('formula_parser.inc.php', 'map.inc.php', 'map.html.php');
$require_files[MAP_DETAIL]                  = array('formula_parser.inc.php', 'map.inc.php', 'map.html.php');
$require_files[MESSAGES]                    = array('message.html.php', 'message.inc.php');
$require_files[MESSAGESDETAIL]              = array('message.html.php', 'message.inc.php');
$require_files[NEW_MESSAGE]                 = array('message.html.php', 'message.inc.php');
$require_files[NEW_MESSAGE_RESPONSE]        = array('message.html.php', 'message.inc.php');
$require_files[PLAYER_DETAIL]               = array('playerDetail.html.php');
$require_files[QUESTIONNAIRE]               = array('questionnaire.html.php');
$require_files[QUESTIONNAIRE_PRESENTS]      = array('questionnaire.html.php', 'formula_parser.inc.php');
$require_files[RANKING]                     = array('ranking.html.php', 'ranking.inc.php');
$require_files[RANKING_TRIBE]               = array('ranking.html.php', 'ranking.inc.php');
$require_files[SCIENCE]                     = array('formula_parser.inc.php', 'science.inc.php', 'science.html.php');
$require_files[SCIENCE_DETAIL]              = array('formula_parser.inc.php', 'science.inc.php', 'science_detail.html.php');
$require_files[SUGGESTIONS]                 = array('../modules/Suggestions/Suggestions.php');
$require_files[TAKEOVER]                    = array('takeover.html.php');
$require_files[TRIBE]                       = array('tribe.html.php', 'tribes.inc.php', 'message.inc.php', 'government.rules.php', 'relation_list.php');
$require_files[TRIBE_ADMIN]                 = array('tribeAdmin.html.php', 'tribes.inc.php', 'message.inc.php', 'relation_list.php', 'government.rules.php', 'wonder.rules.php');
$require_files[TRIBE_DELETE]                = array('tribeDelete.html.php', 'tribes.inc.php', 'relation_list.php', 'message.inc.php');
$require_files[TRIBE_DETAIL]                = array('tribeDetail.html.php', 'tribes.inc.php');
$require_files[TRIBE_HISTORY]               = array('tribeHistory.html.php', 'tribes.inc.php');
$require_files[TRIBE_LEADER_DETERMINATION]  = array('tribeLeaderDetermination.html.php', 'government.rules.php', 'tribes.inc.php');
$require_files[TRIBE_PLAYER_LIST]           = array('tribePlayerList.html.php');
$require_files[TRIBE_RELATION_LIST]         = array('tribeRelationList.html.php', 'tribes.inc.php', 'relation_list.php');
$require_files[UNIT_BUILDER]                = array('formula_parser.inc.php', 'unitbuild.html.php', 'unitbuild.inc.php');
$require_files[UNIT_MOVEMENT]               = array('formula_parser.inc.php', 'unitaction.html.php', 'unitaction.inc.php', 'artefact.inc.php', 'digest.inc.php', 'movement.lib.php', 'tribes.inc.php', 'relation_list.php');
$require_files[UNIT_PROPERTIES]             = array('formula_parser.inc.php', 'unit_properties.html.php', 'unitbuild.inc.php');
$require_files[USER_PROFILE]                = array('profile.html.php');
$require_files[VOTE]                        = array('vote.html.php');
$require_files[WEATHER_REPORT]              = array('weather.html.php', 'wonder.rules.php', 'basic.lib.php');
$require_files[WONDER]                      = array('formula_parser.inc.php', 'wonder.rules.php', 'wonder.html.php', 'wonder.inc.php', 'message.inc.php');
$require_files[WONDER_DETAIL]               = array('formula_parser.inc.php', 'wonder.rules.php', 'wonderDetail.html.php', 'wonder.inc.php');
$require_files[DYK]                         = array('doYouKnow.html.php');
$require_files[STATS]                       = array('formula_parser.inc.php','stats.html.php','stats.inc.php');
?>
