<?php
/*
 * weather.script.php
 * Generates random weather in each region
 * Copyright (c) 2006  Johannes Roessel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

// include necessary files
include "util.inc.php";
include INC_DIR . "db.inc.php";
include INC_DIR . "config.inc.php";

include INC_DIR . "basic.lib.php";
include INC_DIR . "time.inc.php";
include INC_DIR . "effect_list.php";
include INC_DIR . "wonder.rules.php";
include INC_DIR . "wonder.inc.php";

// get globals
$config = new Config();

// show header
weather_showHeader();

// connect to databases
$db = weather_connectToGameDB();

init_Weathers();

// actually do something
weather_generate($db);

// show footer
weather_showFooter();


// ***** FUNCTIONS ***** *******************************************************

/**
 * Logging function with printf syntax
 */
function weather_log($format /* , .. */) {
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
function weather_showHeader() {
  weather_log('------------------------------------------------------------');
  weather_log('- GENERATE WEATHER -----------------------------------------');
  weather_log('- from %s', date('r'));
  weather_log('------------------------------------------------------------');
}

/**
 * Shows footer
 */
function weather_showFooter() {
  weather_log('------------------------------------------------------------');
  weather_log('- STOP -----------------------------------------------------');
  weather_log('- at %s', date('r'));
  weather_log('------------------------------------------------------------');
}

/**
 * Connects to game DB
 *
 * @return  a DB link
 */
function weather_connectToGameDB() {
  global $config;

  $db = new Db($config->DB_GAME_HOST, $config->DB_GAME_USER,
               $config->DB_GAME_PWD, $config->DB_GAME_NAME);

  if (!$db) {
    weather_log('Failed to connect to game DB.');
    exit(1);
  }

  return $db;
}

/**
 * Return all wonders that are applicable as weather (groupID == 2)
 */
function weather_getWeatherWonders($db) {
  global $weatherTypeList;

  $result = array();

  foreach ($weatherTypeList as $id => $weather) {
    $result[$id] = $weather;
  }

  return $result;
}

function weather_generate($db) {
  global $db;

  // get regions
  $regions = getRegions();

  // get weather
  $weather = weather_getWeatherWonders($db);

  foreach ($regions as $region) {
    if (!$region['startRegion']) {
      weather_log('Skipping unused region %s.', $region['name']);
      continue;
    }

    weather_log('Processing region %s.', $region['name']);

    // pick a random weather
    $regionweather = $weather[array_rand($weather)];

    weather_log('Selected Weather: %s', $regionweather->name);

    // save weather information in DB
    $query = "UPDATE `Regions` ".
             "SET `weather` = {$regionweather->weatherID} ".
             "WHERE `regionID` = {$region['regionID']}";
    if (!$db->query($query)) {
      weather_log('Failed to execute query: %s', $query);
      return -1;
    }

    foreach ($regionweather->impactList as $impactID => $impact) {
      $delay = (int)(($delayDelta + $impact['delay']) * WEATHER_TIME_BASE_FACTOR);

      $now = time();
      $query = sprintf("INSERT INTO Event_weather ".
                       "(`regionID`, `weatherID`, `impactID`, `start`, `end`) ".
                       "VALUES (%d, %d, %d, '%s', '%s')",
                       $region['regionID'], $regionweather->weatherID, $impactID, time_toDatetime($now), time_toDatetime($now + $delay));

      if (!$db->query($query)) {
        weather_log('Failed to execute query: %s', $query);
        return -1;
      }
      
    }
  }
}


?>
