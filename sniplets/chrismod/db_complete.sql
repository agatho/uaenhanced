# phpMyAdmin SQL Dump
# version 2.5.7
# http://www.phpmyadmin.net
#
# Host: localhost
# Erstellungszeit: 22. Februar 2005 um 16:57
# Server Version: 4.0.16
# PHP-Version: 4.3.4
# 
# Datenbank: `uga_login`
# 

CREATE DATABASE `uga_login`;
USE uga_login;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `block`
#

CREATE TABLE `block` (
  `blockid` tinyint(4) NOT NULL auto_increment,
  `reason` text NOT NULL,
  PRIMARY KEY  (`blockid`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `login`
#

CREATE TABLE `login` (
  `LoginID` int(11) NOT NULL auto_increment,
  `user` varchar(90) NOT NULL default '',
  `password` varchar(90) NOT NULL default '',
  `activationID` int(11) NOT NULL default '0',
  `activated` int(1) NOT NULL default '0',
  `email` varchar(40) NOT NULL default '',
  `creation` varchar(14) NOT NULL default '',
  `lastChange` timestamp(14) NOT NULL,
  `lastResend` varchar(14) NOT NULL default '0',
  `countResend` int(11) NOT NULL default '0',
  `deleted` tinyint(1) NOT NULL default '0',
  `multi` tinyint(4) NOT NULL default '0',
  `ban` varchar(14) NOT NULL default '0',
  `noseccode` int(1) NOT NULL default '0',
  `urlaub` tinyint(4) unsigned NOT NULL default '0',
  `urlaub_begin` int(11) unsigned NOT NULL default '0',
  `urlaub_end` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`LoginID`),
  UNIQUE KEY `user` (`user`),
  UNIQUE KEY `email` (`email`),
  KEY `deleted` (`deleted`),
  KEY `creation` (`creation`),
  KEY `user_2` (`user`)
) TYPE=MyISAM PACK_KEYS=0;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `loginlog`
#

CREATE TABLE `loginlog` (
  `LoginLogID` int(11) NOT NULL auto_increment,
  `user` varchar(90) default NULL,
  `password` varchar(90) default NULL,
  `success` int(1) default NULL,
  `ip` varchar(15) default NULL,
  `request_method` varchar(10) NOT NULL default '',
  `request_uri` varchar(255) NOT NULL default '',
  `http_user_agent` varchar(255) NOT NULL default '',
  `pollID` varchar(32) NOT NULL default 'none',
  `stamp` timestamp(14) NOT NULL,
  `security_code` varchar(4) NOT NULL default '',
  `typed_security_code` varchar(4) NOT NULL default '',
  `seccode_time` int(11) NOT NULL default '0',
  `misc` text NOT NULL,
  PRIMARY KEY  (`LoginLogID`),
  KEY `stamp` (`stamp`),
  KEY `success` (`success`),
  KEY `user` (`user`),
  KEY `ip` (`ip`),
  KEY `pollID` (`pollID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `loginreserved`
#

CREATE TABLE `loginreserved` (
  `user` varchar(90) NOT NULL default '',
  `password` varchar(90) NOT NULL default '',
  `email` varchar(40) NOT NULL default '',
  `multi` tinyint(4) NOT NULL default '0',
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `user` (`user`),
  KEY `user_2` (`user`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `portal_news`
#

CREATE TABLE `portal_news` (
  `newsID` int(11) NOT NULL auto_increment,
  `category` varchar(255) NOT NULL default 'note',
  `archive` tinyint(1) NOT NULL default '0',
  `author` varchar(255) NOT NULL default 'chris---',
  `date` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `content` text NOT NULL,
  PRIMARY KEY  (`newsID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `portal_poll_check`
#

CREATE TABLE `portal_poll_check` (
  `ip` varchar(20) NOT NULL default '',
  `time` varchar(14) NOT NULL default '',
  `pollID` int(10) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `portal_poll_data`
#

CREATE TABLE `portal_poll_data` (
  `pollID` int(11) NOT NULL default '0',
  `optionText` char(50) NOT NULL default '',
  `optionCount` int(11) NOT NULL default '0',
  `voteID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`pollID`,`voteID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `portal_poll_desc`
#

CREATE TABLE `portal_poll_desc` (
  `pollID` int(11) NOT NULL auto_increment,
  `pollTitle` char(255) NOT NULL default '',
  `timeStamp` timestamp(14) NOT NULL,
  `voters` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`pollID`),
  KEY `pollID` (`pollID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `registeredemail`
#

CREATE TABLE `registeredemail` (
  `email` varchar(95) NOT NULL default '',
  UNIQUE KEY `email` (`email`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `rules_default_module`
#

CREATE TABLE `rules_default_module` (
  `default_module` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`default_module`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `rules_modules`
#

CREATE TABLE `rules_modules` (
  `moduleID` int(10) NOT NULL auto_increment,
  `modus` varchar(255) NOT NULL default '',
  `custom_title` varchar(255) NOT NULL default '',
  `active` int(1) NOT NULL default '0',
  `view` int(1) NOT NULL default '0',
  `showSelector` tinyint(1) NOT NULL default '1',
  `weight` int(11) NOT NULL default '0',
  PRIMARY KEY  (`moduleID`),
  KEY `moduleID` (`moduleID`),
  KEY `modus` (`modus`),
  KEY `custom_title` (`custom_title`)
) TYPE=MyISAM;

# 
# Datenbank: `uga_game`
# 

CREATE DATABASE `uga_game`;
USE uga_game;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `activewonder`
#

CREATE TABLE `activewonder` (
  `activeWonderID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `wonderID` int(11) unsigned NOT NULL default '0',
  `impactID` int(11) NOT NULL default '0',
  `casterID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  `start` char(14) NOT NULL default '',
  `end` char(14) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`activeWonderID`),
  KEY `end` (`end`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `adressbook`
#

CREATE TABLE `adressbook` (
  `playerID` int(11) NOT NULL default '0',
  `entry_playerID` int(11) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `artefact`
#

CREATE TABLE `artefact` (
  `artefactID` int(11) unsigned NOT NULL auto_increment,
  `artefactClassID` int(11) NOT NULL default '0',
  `caveID` int(11) NOT NULL default '0',
  `initiated` int(4) NOT NULL default '0',
  PRIMARY KEY  (`artefactID`),
  UNIQUE KEY `artefactID` (`artefactID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `artefact_class`
#

CREATE TABLE `artefact_class` (
  `artefactClassID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `resref` varchar(128) NOT NULL default 'artefact_test',
  `description` text NOT NULL,
  `description_initiated` text NOT NULL,
  `initiationID` int(11) NOT NULL default '1',
  `destroy_chance` double unsigned NOT NULL default '0',
  `quest_item` tinyint(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`artefactClassID`),
  UNIQUE KEY `artefactID` (`artefactClassID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `artefact_merge_general`
#

CREATE TABLE `artefact_merge_general` (
  `keyClassID` int(11) unsigned NOT NULL default '0',
  `lockClassID` int(11) unsigned NOT NULL default '0',
  `resultClassID` int(11) unsigned NOT NULL default '0',
  UNIQUE KEY `keyClass_lockClass` (`keyClassID`,`lockClassID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `artefact_merge_special`
#

CREATE TABLE `artefact_merge_special` (
  `keyID` int(11) unsigned NOT NULL default '0',
  `lockID` int(11) unsigned NOT NULL default '0',
  `resultID` int(11) unsigned NOT NULL default '0',
  UNIQUE KEY `key_lock` (`keyID`,`lockID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `artefact_rituals`
#

CREATE TABLE `artefact_rituals` (
  `ritualID` int(11) NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `description` text NOT NULL,
  `duration` varchar(255) NOT NULL default '3600',
  PRIMARY KEY  (`ritualID`),
  UNIQUE KEY `ritualID` (`ritualID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `awards`
#

CREATE TABLE `awards` (
  `awardID` int(10) unsigned NOT NULL auto_increment,
  `tag` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`awardID`),
  UNIQUE KEY `tag` (`tag`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `cave`
#

CREATE TABLE `cave` (
  `caveID` int(11) unsigned NOT NULL default '0',
  `xCoord` int(11) unsigned NOT NULL default '0',
  `yCoord` int(11) unsigned NOT NULL default '0',
  `name` char(255) NOT NULL default '',
  `playerID` int(11) unsigned NOT NULL default '0',
  `terrain` int(11) unsigned NOT NULL default '0',
  `takeoverable` tinyint(1) NOT NULL default '0',
  `starting_position` tinyint(1) NOT NULL default '0',
  `secureCave` tinyint(1) NOT NULL default '0',
  `protection_end` char(17) NOT NULL default '',
  `toreDownTimeout` char(14) NOT NULL default '',
  `artefacts` int(11) unsigned NOT NULL default '0',
  `monsterID` int(11) NOT NULL default '0',
  `priority` int(2) NOT NULL default '0',
  `urlaub` tinyint(4) unsigned NOT NULL default '0',
  `secureCave_was` tinyint(1) unsigned NOT NULL default '0',
  `quest_cave` tinyint(1) NOT NULL default '0',
  `invisible_to_non_quest_players` tinyint(1) NOT NULL default '0',
  `invisible_name` char(255) NOT NULL default '',
  `terrain_was` int(11) unsigned default '0',
  `takeoverable_was` tinyint(1) default '0',
  PRIMARY KEY  (`caveID`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `caveID` (`caveID`),
  UNIQUE KEY `Coords` (`xCoord`,`yCoord`),
  KEY `name_2` (`name`),
  KEY `xCoord` (`xCoord`),
  KEY `yCoord` (`yCoord`),
  KEY `takeoverable` (`takeoverable`),
  KEY `playerID` (`playerID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `cave_takeover`
#

CREATE TABLE `cave_takeover` (
  `playerID` int(11) unsigned NOT NULL default '0',
  `caveID` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `xCoord` int(11) unsigned NOT NULL default '0',
  `yCoord` int(11) unsigned NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  `lastAction` timestamp(14) NOT NULL,
  PRIMARY KEY  (`playerID`),
  FULLTEXT KEY `name` (`name`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `cavebook`
#

CREATE TABLE `cavebook` (
  `playerID` int(11) NOT NULL default '0',
  `entry_caveID` int(11) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `election`
#

CREATE TABLE `election` (
  `electionID` int(11) NOT NULL auto_increment,
  `tribe` varchar(8) NOT NULL default '',
  `voterID` int(11) NOT NULL default '0',
  `playerID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`electionID`),
  UNIQUE KEY `voterID` (`voterID`),
  KEY `playerID` (`playerID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_artefact`
#

CREATE TABLE `event_artefact` (
  `event_artefactID` int(10) unsigned NOT NULL auto_increment,
  `caveID` int(10) unsigned NOT NULL default '0',
  `artefactID` int(10) unsigned NOT NULL default '0',
  `event_typeID` int(11) NOT NULL default '0',
  `event_start` timestamp(14) NOT NULL,
  `event_end` timestamp(14) NOT NULL,
  `blocked` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`event_artefactID`),
  UNIQUE KEY `event_artefactID` (`event_artefactID`),
  UNIQUE KEY `caveID` (`caveID`),
  KEY `event_start` (`event_start`,`event_end`),
  KEY `event_end` (`event_end`),
  KEY `caveID_2` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_defensesystem`
#

CREATE TABLE `event_defensesystem` (
  `event_defenseSystemID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `defenseSystemID` int(11) unsigned NOT NULL default '0',
  `event_start` char(17) NOT NULL default '',
  `event_end` char(17) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`event_defenseSystemID`),
  UNIQUE KEY `caveID` (`caveID`),
  UNIQUE KEY `event_expansionID` (`event_defenseSystemID`),
  KEY `event_end` (`event_end`),
  KEY `event_start` (`event_start`),
  KEY `caveID_2` (`caveID`)
) TYPE=MyISAM PACK_KEYS=0;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_expansion`
#

CREATE TABLE `event_expansion` (
  `event_expansionID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `expansionID` int(11) unsigned NOT NULL default '0',
  `event_start` char(17) NOT NULL default '',
  `event_end` char(17) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`event_expansionID`),
  UNIQUE KEY `caveID` (`caveID`),
  UNIQUE KEY `event_expansionID` (`event_expansionID`),
  KEY `event_end` (`event_end`),
  KEY `event_start` (`event_start`),
  KEY `caveID_2` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_gods`
#

CREATE TABLE `event_gods` (
  `id` int(11) NOT NULL auto_increment,
  `target_caveID` int(11) NOT NULL default '0',
  `source_caveID` int(11) NOT NULL default '0',
  `impact` char(17) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  `event` tinyint(1) NOT NULL default '0',
  `eventID` int(11) NOT NULL default '0',
  `playerID` int(11) NOT NULL default '0',
  KEY `id` (`id`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_movement`
#

CREATE TABLE `event_movement` (
  `event_movementID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `source_caveID` int(11) unsigned NOT NULL default '0',
  `target_caveID` int(11) unsigned NOT NULL default '0',
  `movementID` int(11) unsigned NOT NULL default '0',
  `speedFactor` double NOT NULL default '0',
  `event_start` char(17) NOT NULL default '',
  `event_end` char(17) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  `artefactID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_movementID`),
  UNIQUE KEY `event_movementID` (`event_movementID`),
  KEY `caveID` (`caveID`),
  KEY `source_caveID` (`source_caveID`),
  KEY `target_caveID` (`target_caveID`),
  KEY `event_start` (`event_start`),
  KEY `event_end` (`event_end`)
) TYPE=MyISAM PACK_KEYS=0;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_science`
#

CREATE TABLE `event_science` (
  `event_scienceID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) NOT NULL default '0',
  `scienceID` int(11) unsigned NOT NULL default '0',
  `event_start` char(17) NOT NULL default '',
  `event_end` char(17) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`event_scienceID`),
  UNIQUE KEY `playerScienceUnique` (`playerID`,`scienceID`),
  UNIQUE KEY `event_expansionID` (`event_scienceID`),
  UNIQUE KEY `caveID` (`caveID`),
  KEY `caveID_2` (`caveID`),
  KEY `playerID` (`playerID`),
  KEY `event_start` (`event_start`),
  KEY `event_end` (`event_end`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_unit`
#

CREATE TABLE `event_unit` (
  `event_unitID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `unitID` int(11) unsigned NOT NULL default '0',
  `quantity` int(11) NOT NULL default '0',
  `event_start` char(17) NOT NULL default '',
  `event_end` char(17) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`event_unitID`),
  UNIQUE KEY `caveID` (`caveID`),
  UNIQUE KEY `event_untitID` (`event_unitID`),
  KEY `event_end` (`event_end`),
  KEY `event_start` (`event_start`),
  KEY `caveID_2` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `event_wonder`
#

CREATE TABLE `event_wonder` (
  `event_wonderID` int(11) unsigned NOT NULL auto_increment,
  `casterID` int(11) NOT NULL default '0',
  `sourceID` int(11) unsigned NOT NULL default '0',
  `targetID` int(11) NOT NULL default '0',
  `wonderID` int(11) unsigned NOT NULL default '0',
  `impactID` int(11) NOT NULL default '0',
  `event_start` char(17) NOT NULL default '',
  `event_end` char(17) NOT NULL default '',
  `blocked` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`event_wonderID`),
  KEY `event_end` (`event_end`),
  KEY `event_start` (`event_start`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `farmschutz`
#

CREATE TABLE `farmschutz` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `timestamp` varchar(14) default NULL,
  `grenze` int(10) unsigned default NULL,
  `targetID` int(10) unsigned default NULL,
  `target_x` int(10) unsigned NOT NULL default '0',
  `target_y` int(10) unsigned NOT NULL default '0',
  `target_lastAction` varchar(14) default NULL,
  `target_protection` int(10) default NULL,
  `target_points` int(10) default NULL,
  `target_max_points` int(11) NOT NULL default '0',
  `target_tribe` varchar(8) NOT NULL default '',
  `target_over` tinyint(3) default NULL,
  `attackerID` int(10) unsigned default NULL,
  `attacker_protection` int(10) default NULL,
  `attacker_points` int(10) default NULL,
  `attacker_max_points` int(11) NOT NULL default '0',
  `attacker_tribe` varchar(8) NOT NULL default '',
  `tribe_relation` int(10) unsigned NOT NULL default '0',
  `attacker_over` tinyint(3) default NULL,
  `Einoede` varchar(10) NOT NULL default '',
  `Gott` varchar(10) NOT NULL default '',
  `beide_ueber_grenze` varchar(10) NOT NULL default '',
  `target_inactive` varchar(8) NOT NULL default '',
  `can_attack` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `hero`
#

CREATE TABLE `hero` (
  `heldenID` int(11) NOT NULL auto_increment,
  `playerID` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `angriffsWert` int(11) NOT NULL default '0',
  `verteidigungsWert` int(11) NOT NULL default '0',
  `mentalKraft` int(11) NOT NULL default '0',
  `koerperKraft` int(11) NOT NULL default '0',
  `fluchtGrenze` int(11) NOT NULL default '0',
  `erfahrungsWert` int(11) NOT NULL default '0',
  `level` int(11) NOT NULL default '0',
  `bonusPunkte` int(11) NOT NULL default '0',
  `leichteSiege` int(11) NOT NULL default '0',
  `schatzHals` int(11) NOT NULL default '0',
  `schatzKopf` int(11) NOT NULL default '0',
  `schatzRing` int(11) NOT NULL default '0',
  `schatzRuestung` int(11) NOT NULL default '0',
  `schatzWaffe` int(11) NOT NULL default '0',
  `schatzSchild` int(11) NOT NULL default '0',
  PRIMARY KEY  (`heldenID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `hero_monster`
#

CREATE TABLE `hero_monster` (
  `playerID` int(11) NOT NULL default '0',
  `caveID` int(11) NOT NULL default '0',
  `starttime` char(14) NOT NULL default '',
  UNIQUE KEY `playerID` (`playerID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `hero_tournament`
#

CREATE TABLE `hero_tournament` (
  `playerID` int(11) NOT NULL default '0',
  `round` int(11) NOT NULL default '0',
  `gebot` int(11) NOT NULL default '0',
  `turnierID` smallint(6) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `hero_treasure`
#

CREATE TABLE `hero_treasure` (
  `heroID` smallint(6) NOT NULL default '0',
  `treasureID` smallint(6) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log_0`
#

CREATE TABLE `log_0` (
  `logID` int(11) NOT NULL auto_increment,
  `playerID` int(11) default NULL,
  `caveID` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`),
  KEY `caveID` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log_1`
#

CREATE TABLE `log_1` (
  `logID` int(11) NOT NULL auto_increment,
  `playerID` int(11) default NULL,
  `caveID` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`),
  KEY `caveID` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log_2`
#

CREATE TABLE `log_2` (
  `logID` int(11) NOT NULL auto_increment,
  `playerID` int(11) default NULL,
  `caveID` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`),
  KEY `caveID` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log_3`
#

CREATE TABLE `log_3` (
  `logID` int(11) NOT NULL auto_increment,
  `playerID` int(11) default NULL,
  `caveID` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`),
  KEY `caveID` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log_4`
#

CREATE TABLE `log_4` (
  `logID` int(11) NOT NULL auto_increment,
  `playerID` int(11) default NULL,
  `caveID` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`),
  KEY `caveID` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log_5`
#

CREATE TABLE `log_5` (
  `logID` int(11) NOT NULL auto_increment,
  `playerID` int(11) default NULL,
  `caveID` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`),
  KEY `caveID` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log_6`
#

CREATE TABLE `log_6` (
  `logID` int(11) NOT NULL auto_increment,
  `playerID` int(11) default NULL,
  `caveID` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`),
  KEY `caveID` (`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `message`
#

CREATE TABLE `message` (
  `messageID` int(11) unsigned NOT NULL auto_increment,
  `recipientID` int(10) unsigned NOT NULL default '0',
  `senderID` int(11) unsigned NOT NULL default '0',
  `messageClass` int(11) unsigned NOT NULL default '0',
  `messageSubject` varchar(255) NOT NULL default '',
  `messageText` mediumtext NOT NULL,
  `messageTime` varchar(14) default NULL,
  `recipientDeleted` int(11) NOT NULL default '0',
  `senderDeleted` int(11) NOT NULL default '0',
  PRIMARY KEY  (`messageID`),
  UNIQUE KEY `messageID` (`messageID`),
  KEY `recipientID` (`recipientID`),
  KEY `senderID` (`senderID`),
  KEY `messageClass` (`messageClass`),
  KEY `recipientDeleted` (`recipientDeleted`),
  KEY `senderDeleted` (`senderDeleted`),
  KEY `messageTime` (`messageTime`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `monster`
#

CREATE TABLE `monster` (
  `monsterID` int(11) NOT NULL auto_increment,
  `name` char(255) default NULL,
  `angriff` int(11) NOT NULL default '0',
  `verteidigung` int(11) NOT NULL default '0',
  `mental` int(11) NOT NULL default '0',
  `koerperkraft` int(11) NOT NULL default '0',
  `erfahrung` int(11) NOT NULL default '0',
  `eigenschaft` char(255) default NULL,
  PRIMARY KEY  (`monsterID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `player`
#

CREATE TABLE `player` (
  `playerID` int(11) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `npcID` int(11) NOT NULL default '0',
  `last_logout` int(10) unsigned NOT NULL default '0',
  `bot_credits` int(10) unsigned NOT NULL default '0',
  `email2` varchar(255) NOT NULL default '',
  `name` varchar(90) NOT NULL default '',
  `fame` int(11) NOT NULL default '0',
  `tribe` varchar(8) NOT NULL default '',
  `tribeBlockEnd` varchar(14) NOT NULL default '',
  `sex` char(1) default NULL,
  `origin` varchar(90) default NULL,
  `age` int(2) default NULL,
  `icq` varchar(15) default NULL,
  `avatar` varchar(255) default NULL,
  `template` int(2) NOT NULL default '0',
  `farmschutz` int(10) unsigned NOT NULL default '90',
  `urlaub` tinyint(4) unsigned NOT NULL default '0',
  `show_unqualified` tinyint(4) NOT NULL default '0',
  `show_ticker` tinyint(4) NOT NULL default '1',
  `show_returns` tinyint(4) NOT NULL default '0',
  `secureCaveCredits` int(2) NOT NULL default '0',
  `questionCredits` int(10) unsigned NOT NULL default '0',
  `takeover_max_caves` int(11) NOT NULL default '0',
  `description` mediumtext,
  `gfxpath` varchar(255) NOT NULL default 'http://home.arcor.de/tntchris/gfx_neu',
  `awards` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`playerID`),
  UNIQUE KEY `playerID` (`playerID`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`),
  KEY `tribe` (`tribe`),
  KEY `npcID` (`npcID`)
) TYPE=MyISAM PACK_KEYS=0;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `questionnaire_answers`
#

CREATE TABLE `questionnaire_answers` (
  `playerID` int(11) unsigned NOT NULL default '0',
  `questionID` int(11) unsigned NOT NULL default '0',
  `choiceID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`playerID`,`questionID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `questionnaire_choices`
#

CREATE TABLE `questionnaire_choices` (
  `questionID` int(11) unsigned NOT NULL default '0',
  `choiceID` int(11) unsigned NOT NULL default '0',
  `choiceText` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`questionID`,`choiceID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `questionnaire_presents`
#

CREATE TABLE `questionnaire_presents` (
  `presentID` int(11) unsigned NOT NULL auto_increment,
  `hour` varchar(255) NOT NULL default '*',
  `day_of_month` varchar(255) NOT NULL default '*',
  `month` varchar(255) NOT NULL default '*',
  `phase_of_moon` varchar(255) NOT NULL default '*',
  `name` varchar(255) NOT NULL default '',
  `credits` int(11) unsigned NOT NULL default '0',
  `use_count` int(11) NOT NULL default '0',
  PRIMARY KEY  (`presentID`),
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `questionnaire_questions`
#

CREATE TABLE `questionnaire_questions` (
  `questionID` int(11) unsigned NOT NULL auto_increment,
  `questionText` varchar(255) NOT NULL default '',
  `expiresOn` varchar(14) default NULL,
  `credits` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`questionID`),
  KEY `questionID` (`questionID`)
) TYPE=MyISAM PACK_KEYS=0;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quests`
#

CREATE TABLE `quests` (
  `questID` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `abort_msg` text,
  `todo` text NOT NULL,
  `make_caves_visible` text NOT NULL,
  `isQuestBegin` tinyint(1) NOT NULL default '1',
  `isEndQuest` tinyint(1) NOT NULL default '0',
  `onlyOneWinner` tinyint(1) NOT NULL default '0',
  `quest_finished` tinyint(1) NOT NULL default '0',
  `nextQuestID` int(11) NOT NULL default '0',
  `prevQuestID` int(11) NOT NULL default '0',
  `questWonIf` text NOT NULL,
  PRIMARY KEY  (`questID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quests_aborted`
#

CREATE TABLE `quests_aborted` (
  `ID` int(11) unsigned NOT NULL auto_increment,
  `questID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  `timestamp` char(17) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quests_active`
#

CREATE TABLE `quests_active` (
  `ID` int(11) unsigned NOT NULL auto_increment,
  `questID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  `timestamp` char(17) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quests_failed`
#

CREATE TABLE `quests_failed` (
  `ID` int(11) unsigned NOT NULL auto_increment,
  `questID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  `timestamp` char(17) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quests_in_cave`
#

CREATE TABLE `quests_in_cave` (
  `ID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `questID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quests_succeeded`
#

CREATE TABLE `quests_succeeded` (
  `ID` int(11) unsigned NOT NULL auto_increment,
  `questID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  `timestamp` char(17) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quests_vis_to_player`
#

CREATE TABLE `quests_vis_to_player` (
  `ID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `rank_history`
#

CREATE TABLE `rank_history` (
  `playerID` int(11) unsigned NOT NULL default '0',
  `name` varchar(90) default NULL,
  `day_1` int(11) unsigned NOT NULL default '0',
  `day_2` int(11) unsigned NOT NULL default '0',
  `day_3` int(11) unsigned NOT NULL default '0',
  `day_4` int(11) unsigned NOT NULL default '0',
  `day_5` int(11) unsigned NOT NULL default '0',
  `day_6` int(11) unsigned NOT NULL default '0',
  `day_7` int(11) unsigned NOT NULL default '0',
  `day_8` int(11) unsigned NOT NULL default '0',
  `day_9` int(11) unsigned NOT NULL default '0',
  `day_10` int(11) unsigned NOT NULL default '0',
  `curr_day` int(1) unsigned NOT NULL default '0',
  UNIQUE KEY `playerID` (`playerID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `ranking`
#

CREATE TABLE `ranking` (
  `playerID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(90) NOT NULL default '',
  `religion` varchar(20) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  `average` int(11) NOT NULL default '0',
  `average_0` int(11) NOT NULL default '0',
  `average_1` int(11) NOT NULL default '0',
  `average_2` int(11) NOT NULL default '0',
  `military` int(11) NOT NULL default '0',
  `military_rank` int(11) NOT NULL default '0',
  `resources` int(11) NOT NULL default '0',
  `resources_rank` int(11) NOT NULL default '0',
  `buildings` int(11) NOT NULL default '0',
  `buildings_rank` int(11) NOT NULL default '0',
  `sciences` int(11) NOT NULL default '0',
  `sciences_rank` int(11) NOT NULL default '0',
  `artefacts` int(11) NOT NULL default '0',
  `artefacts_rank` int(11) NOT NULL default '0',
  `tribePoints` int(11) NOT NULL default '0',
  `caves` int(11) NOT NULL default '0',
  `tribeFame` int(11) NOT NULL default '0',
  `playerPoints` int(11) NOT NULL default '0',
  `fame` int(11) NOT NULL default '0',
  PRIMARY KEY  (`playerID`),
  UNIQUE KEY `playerID` (`playerID`),
  KEY `rank` (`rank`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `rankingtribe`
#

CREATE TABLE `rankingtribe` (
  `rankingID` int(11) NOT NULL auto_increment,
  `points` int(11) NOT NULL default '0',
  `tribe` varchar(90) NOT NULL default '',
  `rank` int(11) NOT NULL default '0',
  `members` int(11) NOT NULL default '0',
  `fame` int(11) NOT NULL default '0',
  `fame_rank` int(11) NOT NULL default '0',
  `points_sum` int(11) NOT NULL default '0',
  `caves` int(11) NOT NULL default '0',
  `points_rank` int(11) NOT NULL default '0',
  `playerAverage` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rankingID`),
  KEY `rank` (`rank`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `relation`
#

CREATE TABLE `relation` (
  `relationID` int(11) NOT NULL auto_increment,
  `tribe` char(8) NOT NULL default '',
  `tribe_target` char(8) NOT NULL default '',
  `relationType` int(11) NOT NULL default '0',
  `timestamp` char(14) default NULL,
  `duration` char(15) NOT NULL default '',
  `tribe_rankingPoints` int(11) NOT NULL default '0',
  `target_rankingPoints` int(11) NOT NULL default '0',
  `defenderMultiplicator` double NOT NULL default '0',
  `attackerMultiplicator` double NOT NULL default '0',
  `attackerReceivesFame` int(11) NOT NULL default '0',
  `defenderReceivesFame` int(11) NOT NULL default '0',
  `fame` int(11) NOT NULL default '0',
  `moral` int(11) NOT NULL default '0',
  `target_members` int(11) NOT NULL default '0',
  PRIMARY KEY  (`relationID`),
  UNIQUE KEY `oneRelationUnique` (`tribe`,`tribe_target`),
  KEY `tribe` (`tribe`),
  KEY `tribe_target` (`tribe_target`),
  KEY `oneRelationIndex` (`tribe`,`tribe_target`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `session`
#

CREATE TABLE `session` (
  `sessionID` int(11) unsigned NOT NULL auto_increment,
  `s_id` varchar(35) NOT NULL default '',
  `s_id_used` tinyint(4) NOT NULL default '0',
  `lastAction` timestamp(14) NOT NULL,
  `playerID` int(11) unsigned NOT NULL default '0',
  `microtime` decimal(20,4) NOT NULL default '0.0000',
  `loginip` varchar(15) NOT NULL default '',
  `loginchecksum` varchar(32) NOT NULL default '',
  `loginnoscript` int(1) NOT NULL default '0',
  PRIMARY KEY  (`sessionID`),
  UNIQUE KEY `playerID` (`playerID`),
  UNIQUE KEY `s_id` (`s_id`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `startvalue`
#

CREATE TABLE `startvalue` (
  `dbFieldName` varchar(255) NOT NULL default '',
  `value` int(11) NOT NULL default '0',
  UNIQUE KEY `dbFieldName` (`dbFieldName`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `stats`
#

CREATE TABLE `stats` (
  `runden_start` varchar(14) NOT NULL default '',
  `kampfberichte` int(11) NOT NULL default '0',
  `spioberichte` int(11) NOT NULL default '0',
  `wunderberichte` int(11) NOT NULL default '0',
  `takeover_success` int(11) NOT NULL default '0',
  `ticker_downtime` varchar(14) NOT NULL default '0',
  `max_active` int(11) unsigned NOT NULL default '0',
  `max_date` varchar(14) NOT NULL default '',
  `ranking_date` varchar(14) NOT NULL default ''
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `ticker`
#

CREATE TABLE `ticker` (
  `ID` int(11) unsigned NOT NULL auto_increment,
  `senderID` int(11) unsigned NOT NULL default '0',
  `message` mediumtext NOT NULL,
  `time` varchar(14) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `tournament`
#

CREATE TABLE `tournament` (
  `turnierID` int(11) NOT NULL default '0',
  `turnierName` varchar(255) NOT NULL default '',
  `playerID` int(11) NOT NULL default '0',
  `art` int(11) NOT NULL default '0',
  `gebot` int(11) NOT NULL default '0',
  `starttime` varchar(14) NOT NULL default '',
  PRIMARY KEY  (`turnierID`),
  UNIQUE KEY `turnierName` (`turnierName`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `treasure`
#

CREATE TABLE `treasure` (
  `schatz_id` mediumint(9) NOT NULL default '0',
  `name` varchar(255) default NULL,
  `art` char(3) default NULL,
  `wert` int(11) default NULL,
  `truhenart` varchar(20) default NULL,
  `b` varchar(255) default NULL,
  `eigenschaften` varchar(255) default NULL,
  PRIMARY KEY  (`schatz_id`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `tribe`
#

CREATE TABLE `tribe` (
  `tag` varchar(8) NOT NULL default '',
  `name` varchar(90) NOT NULL default '',
  `description` mediumtext NOT NULL,
  `leaderID` int(11) NOT NULL default '0',
  `created` varchar(14) NOT NULL default '',
  `password` varchar(100) NOT NULL default '',
  `governmentID` int(11) NOT NULL default '0',
  `duration` varchar(14) NOT NULL default '',
  `fame` int(11) NOT NULL default '0',
  `awards` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`tag`),
  UNIQUE KEY `name` (`name`),
  KEY `leaderID` (`leaderID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `tribehistory`
#

CREATE TABLE `tribehistory` (
  `tribeHistoryID` int(11) NOT NULL auto_increment,
  `tribe` varchar(8) NOT NULL default '',
  `timestamp` timestamp(14) NOT NULL,
  `ingameTime` varchar(100) NOT NULL default '',
  `message` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`tribeHistoryID`),
  KEY `timestamp` (`timestamp`),
  KEY `tribe` (`tribe`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `tribemessage`
#

CREATE TABLE `tribemessage` (
  `tribeMessageID` int(11) unsigned NOT NULL auto_increment,
  `tag` varchar(8) NOT NULL default '0',
  `messageClass` int(11) unsigned NOT NULL default '0',
  `messageSubject` varchar(255) NOT NULL default '',
  `messageText` mediumtext NOT NULL,
  `messageTime` varchar(14) default NULL,
  `recipientDeleted` int(11) NOT NULL default '0',
  PRIMARY KEY  (`tribeMessageID`),
  KEY `messageClass` (`messageClass`),
  KEY `recipientDeleted` (`recipientDeleted`),
  KEY `tag` (`tag`),
  KEY `messageTime` (`messageTime`)
) TYPE=MyISAM;
