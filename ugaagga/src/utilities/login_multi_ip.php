<?php
/*
 * login_multi_ip.php - Finding users logging in using the same IP
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/***** COMMAND LINE *****/

// include PEAR::Console_Getopt
require_once('Console/Getopt.php');

// check for command line options
$options = Console_Getopt::getOpt(Console_Getopt::readPHPArgv(), 'dht:');
if (PEAR::isError($options)) {
  multiip_usage();
  exit(1);
}

// check for options
$debugFlag = FALSE;
$time_intervall = 12;
foreach ($options[0] as $option) {

  // option h
  if ('h' == $option[0]) {
    multiip_usage(); exit(1);

  // option d
  } else if ('d' == $option[0]) {
    $debugFlag = TRUE;

  // option t
  } else if ('t' == $option[0]) {
    $time_intervall = $option[1];
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
multiip_showHeader();

// connect to databases
$db_login = multiip_connectToLoginDB();

/***** GET GOING *****/


// ** IP **/
// get distinct ip's
$string = "ip";
multiip_showBetween($string);
$ips = multiip_getDistinct_($db_login, $string);

// check each ip
foreach ($ips as $ip) {
  $users = multiip_check_($db_login, $ip, $string);
  if (sizeof($users) > 1) {
    multiip_log("%s: (%s)", $ip, implode(',', $users));
  }
}

/**Passwords**/
//get distinct passwords
$string = "password";
multiip_showBetween($string);
$passwords = multiip_getDistinct_($db_login, $string);

//check each password
foreach ($passwords as $pass){
  $users = multiip_check_($db_login, $pass, $string);
  if(sizeof($users) > 1){
    multiip_log("%s: (%s)", $pass, implode(',', $users));
  }
}
/**PollID**/
//get distinct pollIDs
$string = "pollID";
multiip_showBetween($string);
$pollIDs = multiip_getDistinct_($db_login, $string);

//check each password
foreach ($pollIDs as $poll){
  $users = multiip_check_($db_login, $poll, $string);
  if(sizeof($users) > 1){
    multiip_log("%s: (%s)", $poll, implode(',', $users));
  }
}

// ***** FUNCTIONS ***** *******************************************************

/**
 * Shows usage
 */
function multiip_usage() {
  echo
    "Usage: php login_multi_ip.php [-d] [-h] [-t time_interval]\n".
    "  -d                Debug\n".
    "  -h                This help\n".
    "  -t time_interval  Only consider ips of the last time_interval hours\n";
}

/**
 * Logging function with printf syntax
 */
function multiip_log($format /* , .. */) {

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
function multiip_showHeader() {
  multiip_log('------------------------------------------------------------');
  multiip_log('- FINDING MULTIS -----------------------------------------');
  multiip_log('- from %s', date('r'));
  multiip_log('------------------------------------------------------------');
}
/**
 * Show Between
 */
function multiip_showBetween($string){
  multiip_log('');
  multiip_log('------------------------------------------------------------');
  multiip_log('-- searching %s --', $string);
  multiip_log('------------------------------------------------------------');
  multiip_log('');
  
}
/**
 * Connects to login DB
 *
 * @return  a DB link
 */
function multiip_connectToLoginDB() {
  global $config;

  $db_login = new Db($config->DB_LOGIN_HOST,
                     $config->DB_LOGIN_USER,
                     $config->DB_LOGIN_PWD,
                     $config->DB_LOGIN_NAME);

  if (!$db_login) {
    multiip_log('Failed to connect to login DB.');
    exit(1);
  }

  return $db_login;
}

function multiip_getDistinctIPs($db_login) {

  // prepare query
  global $time_intervall;
  $query = sprintf('SELECT ip FROM `LoginLog` WHERE success = 1 AND '.
                   'stamp > NOW() - INTERVAL %d HOUR '.
                   'GROUP BY ip HAVING COUNT(*) > 1', $time_intervall);

  // send query
  $r = $db_login->query($query);

  // on error
  if (!$r) {
    multiip_log('Could not retrieve ips from LoginLog');
    exit(1);
  }

  // collect records
  $ips = array();
  while ($row = $r->nextRow())
    $ips[] = $row['ip'];

  return $ips;
}

function multiip_checkIP($db_login, $ip) {

  // prepare query
  global $time_intervall;
  $query = sprintf('SELECT user FROM `LoginLog` WHERE ip = "%s" AND '.
                   'stamp > NOW() - INTERVAL %d HOUR AND '.
                   'success = 1 GROUP BY user', $ip, $time_intervall);

  // send query
  $r = $db_login->query($query);

  // on error
  if (!$r) {
    multiip_log('Could not check ip from LoginLog');
    exit(1);
  }
  
  // collect records
  $users = array();
  while ($row = $r->nextRow())
    $users[] = $row['user'];

  return $users;
}
function multiip_getDistinct_($db_login, $string) {

  // prepare query
  global $time_intervall;
  $query = sprintf('SELECT %s FROM `LoginLog` WHERE success = 1 AND '.
                   'stamp > NOW() - INTERVAL %d HOUR '.
                   'GROUP BY %s HAVING COUNT(*) > 1',$string, $time_intervall, $string);

  // send query
  $r = $db_login->query($query);

  // on error
  if (!$r) {
    multiip_log('Could not retrieve %ss from LoginLog', $string);
    exit(1);
  }

  // collect records
  $result = array();
  while ($row = $r->nextRow())
    $result[] = $row[$string];

  return $result;
}

function multiip_check_($db_login, $search, $string) {

  // prepare query
  global $time_intervall;
  $query = sprintf('SELECT user FROM `LoginLog` WHERE %s = "%s" AND '.
                   'stamp > NOW() - INTERVAL %d HOUR AND '.
                   'success = 1 GROUP BY user', $string, $search, $time_intervall);

  // send query
  $r = $db_login->query($query);

  // on error
  if (!$r) {
    multiip_log('Could not check %s from LoginLog', $string);
    exit(1);
  }

  // collect records
  $users = array();
  while ($row = $r->nextRow())
    $users[] = $row['user'];

  return $users;
}

?>
