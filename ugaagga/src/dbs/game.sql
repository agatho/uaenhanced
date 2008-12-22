# phpMyAdmin MySQL-Dump
# version 2.4.0
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Generation Time: May 12, 2004 at 05:22 PM
# Server version: 4.0.16
# PHP Version: 4.3.4
# Database : `game_test2`
# --------------------------------------------------------

#
# Table structure for table `Artefact`
#

CREATE TABLE `Artefact` (
  `artefactID` int(11) unsigned NOT NULL auto_increment,
  `artefactClassID` int(11) unsigned NOT NULL default '0',
  `caveID` int(11) unsigned NOT NULL default '0',
  `initiated` int(4) NOT NULL default '0',
  PRIMARY KEY  (`artefactID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Artefact_class`
#

CREATE TABLE `Artefact_class` (
  `artefactClassID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `resref` varchar(128) NOT NULL default 'artefact_test',
  `description` text NOT NULL,
  `description_initiated` text NOT NULL,
  `initiationID` int(11) NOT NULL default '1',
  PRIMARY KEY  (`artefactClassID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Artefact_merge_general`
#

CREATE TABLE `Artefact_merge_general` (
  `keyClassID` int(11) unsigned NOT NULL default '0',
  `lockClassID` int(11) unsigned NOT NULL default '0',
  `resultClassID` int(11) unsigned NOT NULL default '0',
  UNIQUE KEY `keyClass_lockClass` (`keyClassID`,`lockClassID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Artefact_merge_special`
#

CREATE TABLE `Artefact_merge_special` (
  `keyID` int(11) unsigned NOT NULL default '0',
  `lockID` int(11) unsigned NOT NULL default '0',
  `resultID` int(11) unsigned NOT NULL default '0',
  UNIQUE KEY `key_lock` (`keyID`,`lockID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Artefact_rituals`
#

CREATE TABLE `Artefact_rituals` (
  `ritualID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `description` text NOT NULL,
  `duration` varchar(255) NOT NULL default '3600',
  PRIMARY KEY  (`ritualID`)
) TYPE=MyISAM;
# --------------------------------------------------------

# Table structure for table `Awards`
#
#

CREATE TABLE `Awards` (
  `awardID` int(10) unsigned NOT NULL auto_increment,
  `tag` varchar(32) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`awardID`),
  UNIQUE KEY `tag` (`tag`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Cave`
#

CREATE TABLE `Cave` (
  `caveID` int(11) unsigned NOT NULL default '0',
  `xCoord` int(11) unsigned NOT NULL default '0',
  `yCoord` int(11) unsigned NOT NULL default '0',
  `name` char(255) NOT NULL default '',
  `playerID` int(11) unsigned NOT NULL default '0',
  `terrain` int(11) unsigned NOT NULL default '0',
  `takeoverable` tinyint(1) NOT NULL default '0',
  `starting_position` tinyint(1) NOT NULL default '0',
  `secureCave` tinyint(1) NOT NULL default '0',
  `protection_end` char(14) NULL default NULL,
  `toreDownTimeout` char(14) NOT NULL default '',
  `artefacts` int(11) unsigned NOT NULL default '0',
  `monsterID` int(11) unsigned NOT NULL default '0',
  `regionID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`caveID`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `Coords` (`xCoord`,`yCoord`),
  KEY `playerID` (`playerID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `CaveBookmarks`
#

CREATE TABLE `CaveBookmarks` (
  `bookmarkID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned NOT NULL default '0',
  `caveID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`bookmarkID`),
  UNIQUE KEY `cavebookmark` (`playerID`,`caveID`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `Cave_takeover`
#

CREATE TABLE `Cave_takeover` (
  `playerID` int(11) unsigned NOT NULL default '0',
  `caveID` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `xCoord` int(11) unsigned NOT NULL default '0',
  `yCoord` int(11) unsigned NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  `lastAction` timestamp(14) NOT NULL,
  PRIMARY KEY  (`playerID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Cave_takeover`
#

CREATE TABLE Contacts (
  contactID int(11) unsigned NOT NULL auto_increment,
  playerID int(11) unsigned NOT NULL default '0',
  contactplayerID int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (contactID),
  UNIQUE KEY contact (playerID, contactplayerID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Election`
#

CREATE TABLE `Election` (
  `electionID` int(11) unsigned NOT NULL auto_increment,
  `tribe` varchar(8) NOT NULL default '',
  `voterID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`electionID`),
  UNIQUE KEY `voterID` (`voterID`),
  KEY `playerID` (`playerID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_artefact`
#

CREATE TABLE `Event_artefact` (
  `event_artefactID` int(10) unsigned NOT NULL auto_increment,
  `caveID` int(10) unsigned NOT NULL default '0',
  `artefactID` int(10) unsigned NOT NULL default '0',
  `event_typeID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_artefactID`),
  UNIQUE KEY `caveID` (`caveID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_defenseSystem`
#

CREATE TABLE `Event_defenseSystem` (
  `event_defenseSystemID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `defenseSystemID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_defenseSystemID`),
  UNIQUE KEY `caveID` (`caveID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_expansion`
#

CREATE TABLE `Event_expansion` (
  `event_expansionID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `expansionID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_expansionID`),
  UNIQUE KEY `caveID` (`caveID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_movement`
#

CREATE TABLE `Event_movement` (
  `event_movementID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `source_caveID` int(11) unsigned NOT NULL default '0',
  `target_caveID` int(11) unsigned NOT NULL default '0',
  `movementID` int(11) unsigned NOT NULL default '0',
  `speedFactor` double NOT NULL default '0',
  `exposeChance` double NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  `artefactID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_movementID`),
  KEY `caveID` (`caveID`),
  KEY `source_caveID` (`source_caveID`),
  KEY `target_caveID` (`target_caveID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_science`
#

CREATE TABLE `Event_science` (
  `event_scienceID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `playerID` int(11) unsigned NOT NULL default '0',
  `scienceID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_scienceID`),
  UNIQUE KEY `playerScienceUnique` (`playerID`,`scienceID`),
  UNIQUE KEY `caveID` (`caveID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_unit`
#

CREATE TABLE `Event_unit` (
  `event_unitID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `unitID` int(11) unsigned NOT NULL default '0',
  `quantity` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_unitID`),
  UNIQUE KEY `caveID` (`caveID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_wonder`
#

CREATE TABLE `Event_wonder` (
  `event_wonderID` int(11) unsigned NOT NULL auto_increment,
  `casterID` int(11) unsigned NOT NULL default '0',
  `sourceID` int(11) unsigned NOT NULL default '0',
  `targetID` int(11) unsigned NOT NULL default '0',
  `wonderID` int(11) unsigned NOT NULL default '0',
  `impactID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
	`specialdurationminutes` int(6) default '0',
  PRIMARY KEY  (`event_wonderID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
#
# Table structure for table `Event_wonderEnd`
#

CREATE TABLE `Event_wonderEnd` (
  `activeWonderID` int(11) unsigned NOT NULL auto_increment,
  `caveID` int(11) unsigned NOT NULL default '0',
  `casterID` int(11) unsigned NOT NULL default '0',
  `wonderID` int(11) unsigned NOT NULL default '0',
  `impactID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`activeWonderID`),
  KEY `end` (`end`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Event_weather`
#

CREATE TABLE `Event_weather` (
  `event_weatherID` int(11) unsigned NOT NULL auto_increment,
  `regionID` int(11) unsigned NOT NULL default '0',
  `weatherID` int(11) unsigned NOT NULL default '0',
  `impactID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`event_weatherID`),
  KEY `end` (`end`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
#
# Table structure for table `Event_weatherEnd`
#
CREATE TABLE `Event_weatherEnd` (
  `activeWeatherID` int(11) unsigned NOT NULL auto_increment,
  `regionID` int(11) unsigned NOT NULL default '0',
  `weatherID` int(11) unsigned NOT NULL default '0',
  `impactID` int(11) unsigned NOT NULL default '0',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `blocked` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`activeWeatherID`),
  KEY `end` (`end`)
) TYPE=MyISAM;


# --------------------------------------------------------



#
# Table structure for table `Hero`
#

CREATE TABLE `Hero` (
  `heldenID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `angriffsWert` int(11) unsigned NOT NULL default '0',
  `verteidigungsWert` int(11) unsigned NOT NULL default '0',
  `mentalKraft` int(11) unsigned NOT NULL default '0',
  `koerperKraft` int(11) unsigned NOT NULL default '0',
  `fluchtGrenze` int(11) unsigned NOT NULL default '0',
  `erfahrungsWert` int(11) unsigned NOT NULL default '0',
  `level` int(11) unsigned NOT NULL default '0',
  `bonusPunkte` int(11) unsigned NOT NULL default '0',
  `leichteSiege` int(11) unsigned NOT NULL default '0',
  `schatzHals` int(11) unsigned NOT NULL default '0',
  `schatzKopf` int(11) unsigned NOT NULL default '0',
  `schatzRing` int(11) unsigned NOT NULL default '0',
  `schatzRuestung` int(11) unsigned NOT NULL default '0',
  `schatzWaffe` int(11) unsigned NOT NULL default '0',
  `schatzSchild` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`heldenID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Hero_Monster`
#

CREATE TABLE `Hero_Monster` (
  `playerID` int(11) unsigned NOT NULL default '0',
  `caveID` int(11) unsigned NOT NULL default '0',
  `starttime` char(14) NOT NULL default '',
  UNIQUE KEY `playerID` (`playerID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Hero_tournament`
#

CREATE TABLE `Hero_tournament` (
  `playerID` int(11) unsigned NOT NULL default '0',
  `round` int(11) unsigned NOT NULL default '0',
  `gebot` int(11) unsigned NOT NULL default '0',
  `turnierID` smallint(6) NOT NULL default '0'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Hero_treasure`
#

CREATE TABLE `Hero_treasure` (
  `heroID` smallint(6) NOT NULL default '0',
  `treasureID` smallint(6) NOT NULL default '0'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Log_0`
#

CREATE TABLE `Log_0` (
  `logID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned default NULL,
  `caveID` int(11) unsigned default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Log_1`
#

CREATE TABLE `Log_1` (
  `logID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned default NULL,
  `caveID` int(11) unsigned default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Log_2`
#

CREATE TABLE `Log_2` (
  `logID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned default NULL,
  `caveID` int(11) unsigned default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Log_3`
#

CREATE TABLE `Log_3` (
  `logID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned default NULL,
  `caveID` int(11) unsigned default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Log_4`
#

CREATE TABLE `Log_4` (
  `logID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned default NULL,
  `caveID` int(11) unsigned default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Log_5`
#

CREATE TABLE `Log_5` (
  `logID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned default NULL,
  `caveID` int(11) unsigned default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Log_6`
#

CREATE TABLE `Log_6` (
  `logID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned default NULL,
  `caveID` int(11) unsigned default NULL,
  `ip` varchar(15) default NULL,
  `request` mediumtext,
  `time` timestamp(14) NOT NULL,
  `sessionID` varchar(55) default NULL,
  PRIMARY KEY  (`logID`),
  KEY `playerID` (`playerID`,`caveID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Message`
#

CREATE TABLE `Message` (
  `messageID` int(11) unsigned NOT NULL auto_increment,
  `recipientID` int(10) unsigned NOT NULL default '0',
  `senderID` int(11) unsigned NOT NULL default '0',
  `messageClass` int(11) unsigned NOT NULL default '0',
  `messageSubject` varchar(255) NOT NULL default '',
  `messageText` mediumtext NOT NULL,
  `messageTime` varchar(14) default NULL,
  `recipientDeleted` int(11) unsigned NOT NULL default '0',
  `senderDeleted` int(11) unsigned NOT NULL default '0',
  `read` int(11) unsigned NOT NULL default '0',
  `flag` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`messageID`),
  KEY `recipientID` (`recipientID`),
  KEY `recipientDeleted` (`recipientDeleted`),
  KEY `senderID` (`senderID`),
  KEY `senderDeleted` (`senderDeleted`),
  KEY `read` (`read`),
  KEY `messageClass` (`messageClass`),
  KEY `messageTime` (`messageTime`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Monster`
#

CREATE TABLE `Monster` (
  `monsterID` int(11) unsigned NOT NULL auto_increment,
  `name` char(255) default NULL,
  `angriff` int(11) unsigned NOT NULL default '0',
  `verteidigung` int(11) unsigned NOT NULL default '0',
  `mental` int(11) unsigned NOT NULL default '0',
  `koerperkraft` int(11) unsigned NOT NULL default '0',
  `erfahrung` int(11) unsigned NOT NULL default '0',
  `eigenschaft` char(255) default NULL,
  PRIMARY KEY  (`monsterID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Player`
#

CREATE TABLE `OldTribes` (
  `tag` varchar(8) NOT NULL default '',
  `password` varchar(100) NOT NULL default '',
  `used` int(1) NOT NULL default 0,
  `points_rank` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY (`tag`)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `Player`
#

CREATE TABLE `Player` (
  `playerID` int(11) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `npcID` int(11) unsigned NOT NULL default '0',
  `email2` varchar(255) NOT NULL default '',
  `name` varchar(90) NOT NULL default '',
  `fame` int(11) NOT NULL default '0',
  `tribe` varchar(8) NOT NULL default '',
  `tribeBlockEnd` varchar(14) NOT NULL default '',
  `sex` char(1) default NULL,
  `origin` varchar(90) default NULL,
  `created` datetime NOT NULL default '1970-01-01 00:00:00',
  `icq` varchar(15) default NULL,
  `avatar` varchar(255) default NULL,
  `template` int(2) unsigned NOT NULL default '0',
  `secureCaveCredits` int(2) unsigned NOT NULL default '0',
  `questionCredits` int(10) unsigned NOT NULL default '0',
  `takeover_max_caves` int(11) unsigned NOT NULL default '0',
  `description` mediumtext,
  `gfxpath` varchar(255) NOT NULL default 'http://www.uga-agga.de/game/gfx',
  `awards` varchar(255) NOT NULL default '',
  `lastVote` int(10) unsigned NOT NULL default '0',
  `language` varchar(32) NOT NULL default 'de_DE',
  `timeCorrection` tinyint(4) NOT NULL default '0',
  `body_count` int(11) unsigned NOT NULL default '0',
  `suggestion_credits` smallint(6) default '0',
  `referer_count` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`playerID`),
  UNIQUE KEY `name` (`name`),
  KEY `tribe` (`tribe`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `PlayerHistory`
#

CREATE TABLE `player_history` (
  `historyID` INTEGER      UNSIGNED NOT NULL AUTO_INCREMENT,
  `playerID`  INTEGER      UNSIGNED NOT NULL DEFAULT '0',
  `timestamp` DATETIME              NOT NULL DEFAULT '1970-01-01 00:00:00',
  `entry`     TEXT,
  PRIMARY KEY  (`historyID`),
  KEY `timestamp` (`timestamp`),
  KEY `playerID` (`playerID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Questionnaire_answers`
#

CREATE TABLE `Questionnaire_answers` (
  `playerID` int(11) unsigned NOT NULL default '0',
  `questionID` int(11) unsigned NOT NULL default '0',
  `choiceID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`playerID`,`questionID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Questionnaire_choices`
#

CREATE TABLE `Questionnaire_choices` (
  `questionID` int(11) unsigned NOT NULL default '0',
  `choiceID` int(11) unsigned NOT NULL default '0',
  `choiceText` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`questionID`,`choiceID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Questionnaire_presents`
#

CREATE TABLE `Questionnaire_presents` (
  `presentID` int(11) unsigned NOT NULL auto_increment,
  `hour` varchar(255) NOT NULL default '*',
  `day_of_month` varchar(255) NOT NULL default '*',
  `month` varchar(255) NOT NULL default '*',
  `phase_of_moon` varchar(255) NOT NULL default '*',
  `name` varchar(255) NOT NULL default '',
  `credits` int(11) unsigned NOT NULL default '0',
  `use_count` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`presentID`),
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Questionnaire_questions`
#

CREATE TABLE `Questionnaire_questions` (
  `questionID` int(11) unsigned NOT NULL auto_increment,
  `questionText` varchar(255) NOT NULL default '',
  `expiresOn` varchar(14) default NULL,
  `credits` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`questionID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Ranking`
#

CREATE TABLE `Ranking` (
  `playerID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(90) NOT NULL default '',
  `religion` varchar(20) NOT NULL default '0',
  `rank` int(11) unsigned NOT NULL default '0',
  `average` int(11) unsigned NOT NULL default '0',
  `average_0` int(11) unsigned NOT NULL default '0',
  `average_1` int(11) unsigned NOT NULL default '0',
  `average_2` int(11) unsigned NOT NULL default '0',
  `military` int(11) unsigned NOT NULL default '0',
  `military_rank` int(11) unsigned NOT NULL default '0',
  `resources` int(11) unsigned NOT NULL default '0',
  `resources_rank` int(11) unsigned NOT NULL default '0',
  `buildings` int(11) unsigned NOT NULL default '0',
  `buildings_rank` int(11) unsigned NOT NULL default '0',
  `sciences` int(11) unsigned NOT NULL default '0',
  `sciences_rank` int(11) unsigned NOT NULL default '0',
  `artefacts` int(11) unsigned NOT NULL default '0',
  `artefacts_rank` int(11) unsigned NOT NULL default '0',
  `tribePoints` int(11) unsigned NOT NULL default '0',
  `caves` int(11) unsigned NOT NULL default '0',
  `tribeFame` int(11) NOT NULL default '0',
  `playerPoints` int(11) unsigned NOT NULL default '0',
  `fame` int(11) NOT NULL default '0',
  PRIMARY KEY  (`playerID`),
  KEY `rank` (`rank`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `RankingTribe`
#

CREATE TABLE `RankingTribe` (
  `rankingID` int(11) unsigned NOT NULL auto_increment,
  `calculateTime` int(11) unsigned NOT NULL default '0',
  `tribe` varchar(90) NOT NULL default '',
  `rank` int(11) unsigned NOT NULL default '0',
  `members` int(11) unsigned NOT NULL default '0',
  `fame` int(11) NOT NULL default '0',
  `fame_rank` int(11) NOT NULL default '0',
  `caves` int(11) unsigned NOT NULL default '0',
  `points_rank` int(11) unsigned NOT NULL default '0',
  `playerAverage` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`rankingID`),
  UNIQUE KEY `tribe` (`tribe`),
  KEY `rank` (`rank`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Rank_history`
#

CREATE TABLE `Rank_history` (
`playerID` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL ,
`name` VARCHAR( 90 ) ,
`day_1` INT( 11 ) UNSIGNED NOT NULL ,
`day_2` INT( 11 ) UNSIGNED NOT NULL ,
`day_3` INT( 11 ) UNSIGNED NOT NULL ,
`day_4` INT( 11 ) UNSIGNED NOT NULL ,
`day_5` INT( 11 ) UNSIGNED NOT NULL ,
`day_6` INT( 11 ) UNSIGNED NOT NULL ,
`day_7` INT( 11 ) UNSIGNED NOT NULL ,
`day_8` INT( 11 ) UNSIGNED NOT NULL ,
`day_9` INT( 11 ) UNSIGNED NOT NULL ,
`day_10` INT( 11 ) UNSIGNED NOT NULL ,
`curr_day` INT( 1 ) UNSIGNED NOT NULL ,
UNIQUE (
`playerID` 
)
);
# ------------------------------------------------------

#
# Table structure for table `Regions`
#

CREATE TABLE `Regions` (
  `regionID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(90) NOT NULL default '',
  `description` text NOT NULL,
  `startRegion` tinyint(1) unsigned NOT NULL default '0',
  `weather` int(11) NOT NULL default '-1',
  PRIMARY KEY  (`regionID`),
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Relation`
#

CREATE TABLE `Relation` (
  `relationID` int(11) unsigned NOT NULL auto_increment,
  `tribe` char(8) NOT NULL default '',
  `tribe_target` char(8) NOT NULL default '',
  `relationType` int(11) NOT NULL default '0',
  `timestamp` char(14) default NULL,
  `duration` char(15) NOT NULL default '',
  `tribe_rankingPoints` int(11) unsigned NOT NULL default '0',
  `target_rankingPoints` int(11) unsigned NOT NULL default '0',
  `defenderMultiplicator` double NOT NULL default '0',
  `attackerMultiplicator` double NOT NULL default '0',
  `attackerReceivesFame` int(11) unsigned NOT NULL default '0',
  `defenderReceivesFame` int(11) unsigned NOT NULL default '0',
  `fame` int(11) NOT NULL default '0',
  `moral` int(11) NOT NULL default '0',
  `target_members` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`relationID`),
  UNIQUE KEY `oneRelationUnique` (`tribe`,`tribe_target`),
  KEY `tribe_target` (`tribe_target`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Session`
#

CREATE TABLE `Session` (
  `sessionID` int(11) unsigned NOT NULL auto_increment,
  `s_id` varchar(35) NOT NULL default '',
  `s_id_used` tinyint(4) NOT NULL default '0',
  `lastAction` timestamp(14) NOT NULL,
  `playerID` int(11) unsigned NOT NULL default '0',
  `microtime` decimal(20,4) NOT NULL default '0.0000',
  `loginip` varchar(15) NOT NULL default '',
  `loginchecksum` varchar(32) NOT NULL default '',
  `loginnoscript` int(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`sessionID`),
  UNIQUE KEY `playerID` (`playerID`),
  UNIQUE KEY `s_id` (`s_id`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `StartValue`
#

CREATE TABLE `StartValue` (
  `dbFieldName` varchar(255) NOT NULL default '',
  `value` int(11) unsigned NOT NULL default '0',
  `easyStart` tinyint(1) NOT NULL default '0',
  UNIQUE KEY `dbFieldName` (`dbFieldName`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Tabellenstruktur f¸r Tabelle `stats`
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
# Tabellenstruktur fÅr Tabelle `Suggestions`
#

CREATE TABLE `Suggestions` (
  `suggestionID` int(11) unsigned NOT NULL auto_increment,
  `playerID` int(11) unsigned NOT NULL default '0',
  `Suggestion` mediumtext NOT NULL,
  PRIMARY KEY  (`suggestionID`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Tournament`
#

CREATE TABLE `Tournament` (
  `turnierID` int(11) unsigned NOT NULL default '0',
  `turnierName` varchar(255) NOT NULL default '',
  `playerID` int(11) unsigned NOT NULL default '0',
  `art` int(11) unsigned NOT NULL default '0',
  `gebot` int(11) unsigned NOT NULL default '0',
  `starttime` varchar(14) NOT NULL default '',
  PRIMARY KEY  (`turnierID`),
  UNIQUE KEY `turnierName` (`turnierName`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Treasure`
#

CREATE TABLE `Treasure` (
  `schatz_id` mediumint(9) NOT NULL default '0',
  `name` varchar(255) default NULL,
  `art` char(3) default NULL,
  `wert` int(11) unsigned default NULL,
  `truhenart` varchar(20) default NULL,
  `b` varchar(255) default NULL,
  `eigenschaften` varchar(255) default NULL,
  PRIMARY KEY  (`schatz_id`)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `Tribe`
#

CREATE TABLE Tribe (
  tag varchar(8) NOT NULL default '',
  `name` varchar(90) NOT NULL default '',
  description mediumtext NOT NULL,
  leaderID int(11) unsigned NOT NULL default '0',
  juniorLeaderID int(11) unsigned default '0',
  created varchar(14) NOT NULL default '',
  `password` varchar(100) NOT NULL default '',
  governmentID enum('1','2') NOT NULL default '1',
  duration varchar(14) NOT NULL default '',
  fame int(11) NOT NULL default '0',
  awards varchar(255) NOT NULL default '',
  valid int(1) NOT NULL default '0',
  validatetime varchar(14) NOT NULL default '0',
  PRIMARY KEY  (tag),
  UNIQUE KEY `name` (`name`),
  KEY leaderID (leaderID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `TribeHistory`
#

CREATE TABLE `TribeHistory` (
  `tribeHistoryID` int(11) unsigned NOT NULL auto_increment,
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
# Table structure for table `TribeMessage`
#

CREATE TABLE `TribeMessage` (
  `tribeMessageID` int(11) unsigned NOT NULL auto_increment,
  `tag` varchar(8) NOT NULL default '0',
  `messageClass` int(11) unsigned NOT NULL default '0',
  `messageSubject` varchar(255) NOT NULL default '',
  `messageText` mediumtext NOT NULL,
  `messageTime` varchar(14) default NULL,
  `recipientDeleted` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`tribeMessageID`),
  KEY `messageClass` (`messageClass`),
  KEY `recipientDeleted` (`recipientDeleted`),
  KEY `tag` (`tag`),
  KEY `messageTime` (`messageTime`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `doYouKnow` (
  `id` int(11) NOT NULL auto_increment,
  `titel` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
