<?
/*
 * sessionstart.php - 
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

require_once("include/config.inc.php");
require_once("include/db.inc.php");
require_once("include/page.inc.php");
require_once("include/params.inc.php");
require_once("include/Player.php");

// set session id
if (function_exists('posix_getpid')) {
  session_id(md5(microtime().posix_getpid()));
} else {
  session_id(md5(microtime().rand()));
}

// start session
session_start();

$params = new Params();
$config = new Config();

// keine Variablen angegeben
if (!($params->POST->id)  || !($params->POST->userID))
  page_error403(__FILE__ . ":" . __LINE__ . ": Fehlende Loginvariablen.");

// connect to database
$db = new Db();
if (!$db) page_dberror();

//check user from Session-table with id
$query = "SELECT * FROM Session ".
         "WHERE s_id = '{$params->POST->id}' ".
         "AND playerID = '{$params->POST->userID}'";
$dbresult = $db->query($query);
if (!$dbresult || $dbresult->isEmpty())
  page_error403(__FILE__ . ":" . __LINE__ . ": Falsche SessionID.");

$session_row = $dbresult->nextRow(MYSQL_ASSOC);

// sessionstart sollte nur einmal augerufen werden können
$query = "UPDATE `Session` SET s_id_used = 1 ".
         "WHERE s_id = '{$params->POST->id}' ".
         "AND playerID = '{$params->POST->userID}' ".
         "AND s_id_used = 0";
$dbresult = $db->query($query);
if (!$dbresult || !$db->affected_rows() == 1)
  page_error403(__FILE__ . ":" . __LINE__ . ": Ungültige SessionID.");

// get player by playerID for session
$player = Player::getPlayer($params->POST->userID);
if (!$player)
  page_error403(__FILE__ . ":" . __LINE__ . ": Ungültige SpielerID.");

// put user, its session and nogfx flag into session
$_SESSION['player']    = $player;
$_SESSION['nogfx']     = ($params->POST->nogfx == 1);
$_SESSION['session']   = $session_row;
$_SESSION['logintime'] = date("YmdHis");

// initiate Session messages
$_SESSION['messages'] = array();

// go to ugastart.php
Header("Location: $config->GAME_START_URL");
exit;
?>
