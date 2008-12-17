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


require_once("./include/config.inc.php");
require_once("./include/db.inc.php");
require_once("./include/page.inc.php");
require_once("./include/params.inc.php");

// set session id
//session_id(md5(microtime().posix_getpid()));
srand((double)microtime() * 1000000);
session_id(md5(microtime().rand()));

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
$query = "SELECT * FROM  Player WHERE playerID = '{$params->POST->userID}'";
$dbresult = $db->query($query);
if (!$dbresult || $dbresult->isEmpty())
  page_error403(__FILE__ . ":" . __LINE__ . ": Ungültige SpielerID.");

// put user, its session and nogfx flag into session
$_SESSION['user']      = $dbresult->nextRow(MYSQL_ASSOC);
$_SESSION['nogfx']     = ($params->POST->nogfx == 1);
$_SESSION['session']   = $session_row;
$_SESSION['logintime'] = date("YmdHis");

// initiate Session messages
$_SESSION['messages'] = array();


// calculate time that player was logged out
// last_logout is 0 if that player never pressed logout or if she is currently logged in

/*
$user = $_SESSION['user'];
if ($user['last_logout'] > 0){
  $diff = max(0, (int)(time() - $user['last_logout']));
  $cred = intval(intval($diff)/SECONDS_FOR_CREDIT);
  $query = "UPDATE Player SET last_logout = 0, " .
           "bot_credits = bot_credits + {$cred} " .
           "WHERE playerID = " . ((int)$user['playerID']);
  $db->query($query);
  
  if (SHOW_MESSAGES){
    $_SESSION['messages'][] = sprintf("Willkommen zurück! Du warst %02d:%02d:%02d " .
                                   "Stunden ausgelogged und erhälst damit " .
                                   "%d Bot Credits!",
                                   (int)($diff / 3600),
                                   (int)(($diff % 3600) / 60),
                                   (int)($diff % 60),
                                   $cred);  
  }
}
*/

// ---------------------------------------------------------
// Get the last login

    if (SHOW_MESSAGES){
	if ($params->POST->lt) {
	$t = $params->POST->lt;    
	    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." um ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};

	      $_SESSION['messages'][] = "Dein letzter Login war am ".$time." Uhr.";
	}
    }

// ----------------------------------------------------------

// go to ugastart.php
Header("Location: $config->GAME_START_URL");
exit;
?>