<?
/*
 * deleteInactive.script.php - delete inactive players
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/***** CONSTANTS *****/
DEFINE('DELETE_SCRIPT', 'deletePlayer.script.php');
DEFINE('MAX_INACTIVE_DURATION', 14 * 24 * 60 * 60); // 30 days


/***** COMMAND LINE *****/

// include PEAR::Console_Getopt
require_once('Console/Getopt.php');

// check for command line options
$options = Console_Getopt::getOpt(Console_Getopt::readPHPArgv(), 'dh');
if (PEAR::isError($options)) {
  inactives_usage();
  exit(1);
}

// check for options
$debugFlag = FALSE;
foreach ($options[0] as $option) {

  // option h
  if ('h' == $option[0]) {
    inactives_usage(); exit(1);

  // option d
  } else if ('d' == $option[0]) {
    $debugFlag = TRUE;
  }
}


/***** INIT *****/

// include necessary files
include "util.inc.php";
include INC_DIR . "config.inc.php";
include INC_DIR . "db.inc.php";

// get globals
$config = new Config();

// show header
inactives_showHeader();

// connect to databases
$db_login = inactives_connectToLoginDB();
$db_game  = inactives_connectToGameDB();

// remove inconsistent Player/Session
// FIXME (slavomir) Ich hab das deletePlayer Skript angepasst. Die Inkonsistenz
//                  sollte eigentlich nun nicht mehr auftreten.
inactives_deleteInconsistentPlayerSession($db_game);

// create missing Session-Records
// FIXME (slavomir) Das dürfte eigentlich nicht notwendig sein. Es gibt nur zwei
//                  Gründe, warum die fehlen könnten. Entweder der Spieler hat
//                  sich noch nie eingeloggt, oder der Eintrag wurde gelöscht.
//                  In beiden Fällen ist ein Löschen nicht unbedingt anzuraten.

// get inactive players
$players = inactives_getInactives($db_game);

// and mark them as deleted if activated
inactives_markAsDeleted($players, $db_login);

// delete players marked as deleted
inactives_delete($db_login);



// ***** FUNCTIONS ***** *******************************************************

/**
 * Shows usage
 */
function inactives_usage() {
  echo "Usage: php deleteInactive.script.php [-d] [-h]\n".
       "  -d  Debug\n".
       "  -h  This help\n";
}


/**
 * Logging function with printf syntax
 */
function inactives_log($format /* , .. */) {

  // get args
  $args = func_get_args();

  // get format string
  $format = array_shift($args);

  // do something
  echo vsprintf($format, $args) . "\n";
}


/**
 * Shows header
 */
function inactives_showHeader() {
  inactives_log('------------------------------------------------------------');
  inactives_log('- DELETE INACTIVES -----------------------------------------');
  inactives_log('- from %s', date('r'));
  inactives_log('------------------------------------------------------------');
}


/**
 * Connects to login DB
 *
 * @return  a DB link
 */
function inactives_connectToLoginDB() {
  global $config;

  $db_login = new Db($config->DB_LOGIN_HOST,
                     $config->DB_LOGIN_USER,
                     $config->DB_LOGIN_PWD,
                     $config->DB_LOGIN_NAME);

  if (!$db_login) {
    inactives_log('Failed to connect to login DB.');
    exit(1);
  }

  return $db_login;
}


/**
 * Connects to game DB
 *
 * @return  a DB link
 */
function inactives_connectToGameDB() {
  global $config;

  $db_game = new Db($config->DB_GAME_HOST,
                    $config->DB_GAME_USER,
                    $config->DB_GAME_PWD,
                    $config->DB_GAME_NAME);

  if (!$db_game) {
    inactives_log('Failed to connect to login DB.');
    exit(1);
  }

  return $db_game;
}


/**
 * Deletes all players marked with the 'deleted' flag
 *
 * @param dblogin
 *          the DB link
 */
function inactives_delete($db_login) {

  // prepare query
  $query = 'SELECT user, LoginID, email, countResend, creation, deleted '.
           'FROM Login WHERE activated = 1 AND deleted = 1';

  // send query
  $r = $db_login->query($query);

  // error!
  if (!$r) {
    inactives_log('Could not retrieve users');
    exit(1);
  }

  // iterate through all the records
  while ($row = $r->nextRow()) {
    inactives_log('player to be deleted: %d, %s, %s, %d resends, %s',
                  $row['LoginID'], $row['user'], $row['email'],
                  $row['countResend'], $row['creation']);
    inactives_callDeleteScript($row['LoginID']);
  }
}


/**
 * Calls the delete script with a given playerID
 *
 * @param playerID
 *          the ID of the player who shall be deleted
 */
function inactives_callDeleteScript($playerID) {

  global $debugFlag;

  // check playerID
  if (intval($playerID) <= 0)
    return;

  $command = sprintf("\${PHP-php} %s %d", DELETE_SCRIPT, $playerID);
  if ($debugFlag) {
    inactives_log('%s (%d): %s', __FUNCTION__, __LINE__, $command);
  } else {
    system($command);
  }
}


/**
 * Deletes inconsistencies betweeen Player and Session Tables
 *
 * @param dbgame
 *          the link to the game DB
 */
function inactives_deleteInconsistentPlayerSession($db_game) {

  global $debugFlag;

  // prepare query
  $query = 'SELECT s.playerID FROM `Session` s LEFT JOIN Player p '.
            'ON s.playerID = p.playerID WHERE ISNULL(p.playerID)';

  // send queries
  $result = $db_game->query($query);

  // ignore errors
  if (!$result) {
    inactives_log('Could not retrieve inconsistent records');
    return;
  }

  // do nothing if result is empty
  if ($result->isEmpty())
    return;

  // collect inconsistent records
  $playerIDs = array();
  while ($row = $result->nextRow())
    $playerIDs[] = $row['playerID'];

  // join them
  $playerIDs = implode(", ", $playerIDs);

  // now delete all those
  $query = sprintf('DELETE FROM Session WHERE playerID IN (%s)', $playerIDs);
  if ($debugFlag) {
    inactives_log('%s (%d): %s', __FUNCTION__, __LINE__, $query);
  } else {
    $db_game->query($query);
  }
}


/**
 * Returns an array of all inactive players, that is players who last acted
 * MAX_INACTIVE_DURATION seconds ago.
 *
 * @param dbgame
 *          the link to the game DB
 */
function inactives_getInactives($db_game) {

  // prepare query
  $query = sprintf('SELECT s.playerID, p.name FROM `Session` s '.
                   'LEFT JOIN Player p ON s.playerID = p.playerID '.
                   'WHERE s.lastAction < (NOW() - INTERVAL %d SECOND) + 0',
                   MAX_INACTIVE_DURATION);

  // send query
  $r = $db_game->query($query);

  // on error
  if (!$r) {
    inactives_log('Could not retrieve lastAction from Session');
    exit(1);
  }

  // collect records
  $players = array();
  while ($row = $r->nextRow())
    $players[] = $row;

  return $players;
}


/**
 * Marks players as to with the 'deleted' flag
 *
 * @param players
 *          an array of players
 * @param dblogin
 *          the link to the login DB
 */
function inactives_markAsDeleted($players, $db_login) {

  global $debugFlag;

  // check $players
  if (0 == sizeof($players))
    return;

  // collect playerIDs
  $playerIDs = array();
  foreach ($players as $player) {
    inactives_log('possibly inactive player: %d, %s', $player['playerID'],
                  $player['name']);
    $playerIDs[] = $player['playerID'];
  }

  // join them
  $playerIDs = implode(", ", $playerIDs);

  // prepare query
  $query = sprintf('UPDATE Login SET deleted = 1 WHERE activated = 1 AND ' .
                   'LoginID IN (%s)', $playerIDs);

  // send query
  if ($debugFlag) {
    inactives_log('%s (%d): %s', __FUNCTION__, __LINE__, $query);
  } else {
    $db_login->query($query);
  }
}
