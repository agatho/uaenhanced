<?php
/*
 * adjustEffects.php - adjusts all caves' effects
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
$options = Console_Getopt::getOpt(Console_Getopt::readPHPArgv(), 'ch');
if (PEAR::isError($options)) {
  adjust_usage();
  exit(1);
}

// check for options
$checkOnly = FALSE;
foreach ($options[0] as $option) {
  
  // option h
  if ('h' == $option[0]) {
    adjust_usage(); exit(1);
  
  // option c
  } else if ('c' == $option[0]) {
    $checkOnly = TRUE;
  }
}

/***** INIT *****/

// include necessary files
include "util.inc.php";
include INC_DIR . "config.inc.php";
include INC_DIR . "db.inc.php";

include INC_DIR . "effect_list.php";
include INC_DIR . "game_rules.php";
include INC_DIR . "wonder.rules.php";

include INC_DIR . "artefact.inc.php";
include INC_DIR . "basic.lib.php";
include INC_DIR . "wonder.inc.php";

// get globals
$config = new Config();

// show header
adjust_showHeader();

// connect to databases
$db = adjust_connectToGameDB();

// get caveIDs
$caveIDs = adjust_getCaveIDs($db);

// adjust caves
foreach ($caveIDs as $caveID) {
  adjust_adjustCave($db, $caveID);
}

// show footer
adjust_showFooter();



// ***** FUNCTIONS ***** *******************************************************

/**
 * Shows usage
 */
function adjust_usage() {
  echo "Usage: php adjustEffects.php [-c] [-h]\n".
       "  -c  Just check, do not update\n".
       "  -h  This help\n";
}


/**
 * Logging function with printf syntax
 */
function adjust_log($format /* , .. */) {

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
function adjust_showHeader() {
  adjust_log('------------------------------------------------------------');
  adjust_log('- ADJUST EFFECTS -------------------------------------------');
  adjust_log('- from %s', date('r'));
  adjust_log('------------------------------------------------------------');
}


/**
 * Connects to game DB
 *
 * @return  a DB link
 */
function adjust_connectToGameDB() {
  global $config;

  $db = new Db($config->DB_GAME_HOST, $config->DB_GAME_USER,
               $config->DB_GAME_PWD, $config->DB_GAME_NAME);

  if (!$db) {
    adjust_log('Failed to connect to login DB.');
    exit(1);
  }

  return $db;
}


/**
 * Returns all caves' ID
 *
 * @param dbgame
 *          the link to the game DB
 * @return  all caveIDs
 */
function adjust_getCaveIDs($db) {

  // prepare result
  $result = array();

  // prepare query
  $query = 'SELECT caveID FROM Cave ORDER BY caveID ASC';

    // send queries
  $dbresult = $db->query($query);

  // ignore errors
  if (!$dbresult || $dbresult->isEmpty()) {
    adjust_log('%s: Could not retrieve caveIDs', __FUNCTION__);
    return $result;
  }

  // collect caveIDs
  while ($row = $dbresult->nextRow())
    $result[] = $row['caveID'];

  return $result;
}


/**
 * Adjusts deviating cave's effects
 *
 * @param dbgame
 *          the link to the game DB
 * @return  all caveIDs
 */
function adjust_adjustCave($db, $caveID) {

  global $effectTypeList, $terrainList, $checkOnly;

  // get cave
  $cave = getCaveByID($caveID);

  // get artefact effects
  $artefactEffects = artefact_recalculateEffects($caveID);

  // get wonder effects
  $wonderEffects = wonder_recalc($caveID, $db);

  // check each effect
  $adjustments = array();
  foreach($effectTypeList AS $effectID => $effect) {

    // get actual value
    $actual = $cave[$effect->dbFieldName];

    // get nominal value
    $nominal = $artefactEffects[$effectID] + $wonderEffects[$effectID];
    $nominal += (double) $terrainList[$cave['terrain']]['effects'][$effectID];

    // check for deviation
    if ($actual != $nominal) {

      // log difference
      adjust_log('%4d:  %-30s  nominal:%f  actual:%f', $caveID,
                 $effect->dbFieldName, $nominal, $actual);

      // collect adjustments
      $adjustments[] = sprintf('%s = %f', $effect->dbFieldName, $nominal);
    }
  }

  // prepare query
  $query = sprintf('UPDATE Cave SET %s WHERE caveID = %d',
                   implode(", ", $adjustments), $caveID);

  // adjust cave
  if (0 != sizeof($adjustments) && !$checkOnly) {
    adjust_log('Adjusting cave %d (%s)', $caveID, $cave['name']);

    // send query
    if (!$db->query($query)) {
      adjust_log('Error! "%s": %s', $query, mysql_error());
    }
  }
}


/**
 * Shows footer
 */
function adjust_showFooter() {
  adjust_log('------------------------------------------------------------');
  adjust_log('- STOP -----------------------------------------------------');
  adjust_log('- at %s', date('r'));
  adjust_log('------------------------------------------------------------');
}
