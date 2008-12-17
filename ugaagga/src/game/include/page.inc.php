<?
/*
 * page.inc.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once("include/params.inc.php");
require_once("include/config.inc.php");
require_once("include/db.inc.php");
require_once("include/Player.php");

function page_error403($message){
  global $config;

  @session_destroy();
  Header("Location: ".$config->ERROR403_URL."?message=".urlencode($message));
  exit;
}

function page_dberror(){
  global $config;

  Header("Location: ".$config->DBERROR_URL);
  exit;
}

function stopwatch($start=false) {
  static $starttime;

  list($usec, $sec) = explode(" ", microtime());
  $mt = ((float)$usec + (float)$sec);

  if (!empty($start))
    return ($starttime = $mt);
  else
    return $mt - $starttime;
}

function page_start(){
  global $db, $params, $config;

  // start stopwatch
  stopwatch('start');

  // get configuration
  $config = new Config();

  // check for cookie
  // FIXME german string..
  if (!sizeof($_COOKIE))
    page_error403('Sie müssen 3rd party cookies erlauben.');

  // start session
  session_start();

  // get request params
  $params = new Params();

  // check for valid session
  // FIXME german string..
  if (!($params->SESSION->player->playerID))
    page_error403(sprintf('Sie waren für %d Minuten oder mehr inaktiv.', date("i", ini_get("session.gc_maxlifetime"))));

  // connect to database
  if (!($db = new Db()))
    page_dberror();

  // init I18n
  $params->SESSION->player->init_i18n();
}

function page_refreshUserData() {
  global $db, $params, $config;

  $player = Player::getPlayer($params->SESSION->player->playerID);
  if (!$player)
    return FALSE;

  $_SESSION['player']      = $player;
  $params->SESSION->player = $player;

  return TRUE;
}

function page_end($watch = true){
  global $db, $config;
  if ($config->RUN_TIMER ||$watch){
    $proctime  = stopwatch();
    $dbpercent = round($db->time_queries/$proctime * 100, 2);
    // FIXME: has to be localized
    echo "<!-- Seite aufgebaut in ".$proctime." Sekunden (".
         (100 - $dbpercent)."% PHP - ".
         $dbpercent."% MySQL) mit ".
         $db->num_queries." Abfragen -->";
  }
}

function page_startTimer() {
  list($usec, $sec) = explode(" ", microtime());
  return ((float)$usec + (float)$sec);
}

function page_stopTimer($time) {
  $newTime = page_startTimer();
  return $newTime - $time;
}

function page_sessionExpired($params){
  return isset($params->SESSION->lastAction) && time() > $params->SESSION->lastAction + SESSION_MAX_LIFETIME;
}

function page_sessionValidate($params, $config){
  global $db;

  // calculate seconds with 1000s frac
  list($usec, $sec) = explode(" ", microtime());
  $microtime = $sec + $usec;

  $query = sprintf("UPDATE Session SET microtime = '%f' ".
                   "WHERE playerID = %d AND `sessionID` = %d AND ".
                   "((lastAction < (NOW() - INTERVAL 2 SECOND) + 0) OR ".
                   "microtime <= $microtime - %f)",
                   $microtime,
                   $params->SESSION->player->playerID,
                   $_SESSION['session']['sessionID'],
                   $config->WWW_REQUEST_TIMEOUT);
  if (!$db->query($query) || !$db->affected_rows())
    return FALSE;

  return md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_ACCEPT_CHARSET'] . $_SERVER['HTTP_ACCEPT_LANGUAGE']) == $params->SESSION->session['loginchecksum'];
}

function page_getModus($params, $config){
  $modus = $params->POST->modus;
  if (!isset($modus))
    $modus = CAVE_DETAIL;

  if (in_array($modus, $config->rememberModusInclude))
    $_SESSION['current_modus'] = $modus;
  else
    $_SESSION['current_modus'] = CAVE_DETAIL;

  return $modus;
}

function page_logRequest($modus, $caveID){
  global $config, $params, $db;

  if ($config->LOG_ALL && in_array($modus, $config->logModusInclude)){
    $query = sprintf("INSERT INTO Log_%d (playerID, caveID, ip, request, sessionID) " .
                     "VALUES (%d, %d, '%s', '%s', '%s')",
                     date("w"),
                     $params->SESSION->player->playerID,
                     $caveID,
                     $_SERVER['REMOTE_ADDR'],
                     addslashes(var_export($params->POST, TRUE)),
                     session_id());
    $db->query($query);
  }
}

function page_ore() {
  global $params, $db;

  $now = time();

  // increment time diff count
  $_SESSION['ore_time_diff'][$now - $_SESSION['ore_time']]++;

  // increment counter and log if required
  if (++$_SESSION['ore_counter'] == 50) {
    $query = sprintf("INSERT INTO ore_log (playerID, time_diff, stamp, sid) " .
                     "VALUES (%d, '%s', '%s', '%s')",
                     $params->SESSION->player->playerID,
                     addslashes(var_export($_SESSION['ore_time_diff'], TRUE)),
                     addslashes(time_toDatetime($now)),
                     session_id());
    $db->query($query);
    $_SESSION['ore_counter'] = 0;
  }

  // set new timestamp
  $_SESSION['ore_time'] = $now;
}
?>
