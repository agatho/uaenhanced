<?
/*
 * module_login.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("session.inc.php");
require_once("game.inc.php");

/** This function tries to get the client's IP
 *
 *  @return
 */
function get_ip(){
	if($_SERVER['HTTP_X_FORWARDED_FOR']) { // case 1.A: proxy && HTTP_X_FORWARDED_FOR is defined
		$array = extractIP($_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($array && count($array) >= 1) {
			return $array[0]; // first IP in the list
		}
	}
	if($_SERVER['HTTP_X_FORWARDED']) { // case 1.B: proxy && HTTP_X_FORWARDED is defined
		$array = extractIP($_SERVER['HTTP_X_FORWARDED']);
		if ($array && count($array) >= 1) {
			return $array[0]; // first IP in the list
		}
	}
	if($_SERVER['HTTP_FORWARDED_FOR']) { // case 1.C: proxy && HTTP_FORWARDED_FOR is defined
		$array = extractIP($_SERVER['HTTP_FORWARDED_FOR']);
		if ($array && count($array) >= 1) {
			return $array[0]; // first IP in the list
		}
	}
	if($_SERVER['HTTP_FORWARDED']) { // case 1.D: proxy && HTTP_FORWARDED is defined
		$array = extractIP($_SERVER['HTTP_FORWARDED']);
		if ($array && count($array) >= 1) {
			return $array[0]; // first IP in the list
		}
	}
	if($_SERVER['HTTP_CLIENT_IP']) { // case 1.E: proxy && HTTP_CLIENT_IP is defined
		$array = extractIP($_SERVER['HTTP_CLIENT_IP']);
		if ($array && count($array) >= 1) {
			return $array[0]; // first IP in the list
		}
	}
	// case 4: no proxy (or tricky case: proxy+refresh)
	if($_SERVER['REMOTE_HOST']) {
		$array = extractIP($_SERVER['REMOTE_HOST']);
		if ($array && count($array) >= 1) {
			return $array[0]; // first IP in the list
		}
	}
	return $_SERVER['REMOTE_ADDR'];
}

function extractIP($ip) {
	$b = ereg ("^([0-9]{1,3}\.){3,3}[0-9]{1,3}", $ip, $array);
	if ($b) return $array;
	return false;
}

/** This function...
 *
 *  @return
 */
function module_getLogin(){
  
  global $cfg, $params;
  
  $template = @tmpl_open('./templates/module_login.ihtml');
  tmpl_set($template, array('login_link'     => "portal.php",
                            'create_link'    => "portal.php?modus=" . CREATE_ACCOUNT,
                            'forgotten_link' => "portal.php?modus=" . PASSWORD_FORGOTTEN,
                            'password'       => $params->password,
                            'username'       => $params->username,
                            'ARGUMENT'       => array('name' => "modus", 'value' => PASSWORD_CHECK)));
  if ($cfg['USE_SEC_CODE'])
    tmpl_set($template, 'SECURITY_CODE/sid', SID);

  return tmpl_parse($template);
}

define("LOGIN_SUCCESS",              1);
define("LOGIN_FAILURE",              0);
define("LOGIN_WRONG_SECURITY_CODE", -1);
define("LOGIN_ACTIVATE_FIRST",       2);

define("LOGIN_VACATION",	     3); // ADDED by chris--- for urlaub

/** This function writes useful data to the LoginLog DB table
 */
function login_writeLog($db_login, $user, $password, $security_code, $success){

  global $cfg, $params;

  $ip              = clean(get_ip());
  $request_method  = clean($_SERVER['REQUEST_METHOD']);
  $request_uri     = clean($_SERVER['REQUEST_URI']);
  $http_user_agent = clean($_SERVER['HTTP_USER_AGENT']);

  $donotlog = array("HTTP_USER_AGENT", "HTTP_HOST", "HTTP_CONNECTION", "HTTP_CACHE_CONTROL", "HTTP_KEEP_ALIVE");
  
  $misc = "";
  $server_keys = array_keys($_SERVER);
  foreach ($server_keys AS $key){
    if (strncmp($key, "HTTP_", 5) == 0){
      if (!in_array($key, $donotlog))
        $misc .= "$key:\t{$_SERVER[$key]}\n";
    }
  }

  $cookie_value = trim(htmlentities(strip_tags($_COOKIE["UAPOLLID"]), ENT_QUOTES));

  $query = "INSERT INTO LoginLog (".
           "user, ".
           "password, ".
           "success, ".
           "ip, ".
           "request_method, ".
           "request_uri, ".
           "http_user_agent, ".
           "pollID, " .
           "security_code, " .
           "typed_security_code, " .
           "seccode_time, " .
           "misc) " .
           "VALUES (".
           "'{$user}', ".
           "'{$password}', ".
           "'{$success}', ".
           "'{$ip}', ".
           "'{$request_method}', ".
           "'{$request_uri}', ".
           "'{$http_user_agent}', ".
           "'{$cookie_value}', ".
           "'{$_SESSION['seccode']}', ".
           "'{$security_code}', ".
           "'" . (time() - $_SESSION['show_seccode_time']) . "', ".
           "'{$misc}')";
  $db_login->query($query);

}

/** This function checks the username, password and security code.
 *  If successful, it refers directly to the game...
 */
function login_checkUserPassword($db_login, $user, $password, $security_code){

  global $cfg, $params;

  // hol die Benutzerdaten
  $query = "SELECT *,Now()+0 as jetzt, DATE_FORMAT(ban, '%e.%c.%Y %k:%i') as time FROM Login WHERE user='{$user}' AND password='{$password}'";
  $result = $db_login->query($query);
  if (!$result){
    return "Datenbankfehler beim Login: " . mysql_error();
  }

  // Benutzer-Passwort Kombination nicht korrekt
  if ($result->isEmpty()){
    login_writeLog($db_login, $user, $password, $security_code, LOGIN_FAILURE);
    return "Falscher User oder falsches Passwort.";
  }

  $row = $result->nextRow();
  $result->free();

  // Security Code richtig eingegeben?

  if (!$row['noseccode'] &&
      $cfg['USE_SEC_CODE'] && ($security_code != $_SESSION['seccode'] || $security_code == '')){
    login_writeLog($db_login, $user, $password, $security_code, LOGIN_WRONG_SECURITY_CODE);
    return "Bitte gib den korrekten Security Code ein!";
  }

  if ($row['deleted'] == 1){
    login_writeLog($db_login, $user, $password, $security_code, LOGIN_FAILURE);
    return "Dieser Account ist gel&ouml;scht worden.";
  }

  if ($row['ban']!=0 & $row['jetzt']<$row['ban']){
    login_writeLog($db_login, $user, $password, $security_code, LOGIN_FAILURE);
    return "Dieser Account ist noch bis zum ".$row['time']." Uhr gesperrt.";
  }

  if ($row['multi'] != 0) {

    login_writeLog($db_login, $user, $password, $security_code, LOGIN_FAILURE);
    switch($row['multi']){

      default:

        $sql = "SELECT * FROM Block WHERE blockid = '{$row['multi']}'";
        $block = $db_login->query($sql);
        if (!$block->isEmpty()){
          $block_row = $block->nextRow();
          $block->free();
          return $block_row['reason'];
        }
        break;    // slange: break hinzugefügt. steht nun für eine multi id kein eintrag in block,
	          //         wird der spieler in fällen != 1, 2 auch nicht gesperrt. das wird 
		  //         konkret bei multi=8 für die scripter verwendet, die nicht gesperrt werden!
		  //         bitte nicht ändern, da die NPC Leute öfter mal Fehler in den scripten haben.
	
      case '1':

        return "Dieser Account ist Multi - Verd&auml;chtig.<br>" .
               "Wenn Du Dich nicht innerhalb der naechsten 24 " .
               "Stunden bei uns meldest wird dein Account gel&ouml;scht.<br>" .
               "Melde dich per email an multi@uga-agga.de.";

      case '2':

        return "Dieser Account ist Cheat - Verd&auml;chtig.<br>" .
               "Wenn Du Dich nicht innerhalb der naechsten 24 " .
               "Stunden bei uns meldest wird dein Account gel&ouml;scht.<br>" .
               "Melde dich per email an cheat@uga-agga.de.";

    }
  }

  // Noch nicht aktiviert
  if ($row['activated'] != 1){
    login_writeLog($db_login, $user, $password, $security_code, LOGIN_ACTIVATE_FIRST);
    return login_activateAccount($db_login, $user, $password);
  }

// ADDED by chris--- for urlaubsmod -----------------------------------------------------
  // Im Urlaub
  if ($row['urlaub'] != 0){
    login_writeLog($db_login, $user, $password, $security_code, LOGIN_VACATION);
    return login_vacation($db_login, "", "");
  }
// --------------------------------------------------------------------------------------

// ADDED by chris--- for last login time ------------------------------------------------

  $query = "SELECT stamp FROM `loginlog` WHERE user='".$user."' AND success = ".LOGIN_SUCCESS." ORDER BY LoginLogID DESC LIMIT 0 , 1";

  $result = $db_login->query($query);

  if ($result && !$result->isEmpty()) {
    $loginrow = $result->nextRow(MYSQL_ASSOC);
    $last_login_time = $loginrow['stamp'];
  }

// --------------------------------------------------------------------------------------

  login_writeLog($db_login, $user, $password, $security_code, LOGIN_SUCCESS);

  // get link to DB 'game'
  $db_game = new DB($cfg['DB_GAME']['HOST'], $cfg['DB_GAME']['USER'],
                    $cfg['DB_GAME']['PWD'], $cfg['DB_GAME']['NAME']);
  if (!$db_game)
    return "Game DB Fehler...";

  // login
  $s_id = uniqid(rand(), 1);
  $checksum = md5($_SERVER['HTTP_USER_AGENT'] .
                  $_SERVER['HTTP_ACCEPT_CHARSET'] .
                  $_SERVER['HTTP_ACCEPT_LANGUAGE']);
  $noscript = (int)$params->noscript;

  // session übernehmen
  $query = "REPLACE INTO `Session` ".
           "(s_id, playerID, loginip, loginchecksum, loginnoscript) ".
           "VALUES ('{$s_id}', '{$row['LoginID']}', ".
           "'$_SERVER[REMOTE_ADDR]', '$checksum', $noscript)";
  $result = $db_game->query($query);

  if (!$result)
    return "Fehler in der DB 3";

  $serverID = getFreeServerID();

  session_destroy_session();

  // base url
  $url = $cfg['GAME_BASE'][$serverID] . $cfg['GAME_URL'][$serverID];

  // attach ids
  $url .= "?id=" . $s_id . "&userID=" . $row['LoginID'];

// ADDED by chris--- for last loging time --------------------------

  $url .= "&lt=".$last_login_time;

// -----------------------------------------------------------------

  // use server's gfxs?
  if ($params->nogfx == 1) $url .= "&nogfx=1";

  header("Location: " . $url);
  exit();
}

function module_getRegisterEmail(){
  
  global $cfg, $params;
  
  $template = @tmpl_open('./templates/module_register.ihtml');
  tmpl_set($template, array('register_link'  => "portal.php",
                            'ARGUMENT'       => array('name' => "modus", 'value' => REGISTER_EMAIL)));

  return tmpl_parse($template);
}

function register_registerEmail($db_login, $email) {
  global $cfg;

  $query = 
    "INSERT INTO RegisteredEmail ".
    "(email) values('$email')";

  $result = $db_login->query($query);
  if (!$result){
    return "Fehler in der DB. Vermutlich ist Ihre Email schon registriert.";
  }
  
  return "Ihre Email '$email' wurde erfolgreich registriert.";
}

/** This function resends the password...
 *
 */
function login_resendPassword($db_login, $username){

  global $cfg;
    

  $template =  @tmpl_open('./templates/login_password_forgotten.ihtml');
  $show_form = FALSE;

  if ($username != ""){

    // hole User Daten
    $query = "SELECT * FROM Login WHERE user='" . $username . "'";
    $result = $db_login->query($query);

    // Fehler?
    if (!$result){
      $message = "Fehler in der DB!";

    // existiert gar nicht?
    } else if ($result->isEmpty()){
        $message = "Der Account: " . $username . " existiert nicht.";
        $show_form = TRUE;
      
    // existiert doch ...
    } else {
      $ud = $result->nextRow();
      
      // in letzter Zeit bereits zugesendet?
      if ($ud['lastResend'] > date("YmdHis") - $cfg['LOGIN_PWD_RESEND_TIMEOUT']){
        $message = "Das Passwort wurde Ihnen in den letzten " .
                   $cfg['LOGIN_PWD_RESEND_TIMEOUT'] .
                   " Sekunden schon einemal zugesandt.<br>" .
                   "Bitte warten Sie auf den Ablauf dieser Zeit, bevor Sie es ein ".
                   "weiteres Mal versuchen.";
      
      // schick das Passwort!
      } else {

        if(!mail($ud['email'],
             "Ihr Passwort bei uga-agga@chris",
             "Hier Ihre Account-Informationen:\n" .
             "Spieler: " . $ud['user'] . "\nPasswort:" . $ud['password'],
             "From: chris@tntchris.dyndns.org"))
		   $message = "Email konnte nicht gesendet werden. Bitte wenden Sie sich an den Support!<p>";
		else $message = "Die Email wurde Ihnen zugesandt.<p>";
      }
    }
  } else {
    $show_form = TRUE;
  }
  
  if ($show_form){
    tmpl_set($template, array('FORM/forgotten_link'  => "portal.php",
                              'FORM/ARGUMENT' => array('name' => "modus",
                                                       'value' => PASSWORD_FORGOTTEN)));    
  }
  
  tmpl_set($template, 'message', $message);  
  return tmpl_parse($template);
}

/** This function creates a new Account
 *
 */
function login_createAccount($db_login){
  
  global $cfg, $params;
  
  // calculate the distance to forbidden names
  $distance = 99.;
  if ($params->username != "") {
    foreach($cfg[FORBIDDEN_NAMES] AS $k => $name) {
      if (strstr($params->username, $name)) {
	$distance = 0.5;    // contains forbidden word
      }
      else if (($tmp=levenshtein($params->username, $name, 2, 1, 2)) 
               < $distance) {
	$distance = $tmp;
      }
    }
  }

  $template = @tmpl_open('./templates/login_create_account.ihtml');
  $show_form = FALSE;
  
  // count users, and check whether smaller than LOGIN_MAX_USERS  
  $query = "SELECT COUNT(LoginId) AS n FROM Login";
  $result = $db_login->query($query);
  
  // Fehler
  if (!$result){
    $message = "Fehler in der DB (1.)!";
  
  // check ...
  } else {
    
    $row = $result->nextRow();
    
    // Kapazität erreicht
    if ($row['n'] >= $cfg['LOGIN_MAX_USERS']) {
      $message = "Es wurde bisher nur eine begrenzte Anzahl von Accounts " .
                 "bereitgestellt (derzeit: " . $cfg['LOGIN_MAX_USERS'] .
                 "). Es werden aber in unregelmäßigen Zeitabständen weitere " .
                 "Kontingente folgen. ".
		 "<p><b>Wenn Sie im Kasten rechts Ihre Email ".
		 "registrieren, benachrichtigen wir Sie, sobald eine gr&ouml;&szlig;ere ".
		 " Menge neuer Accounts freigeschaltet werden.</b>".
		 "<p>Sie k&ouml;nnen es auch sp&auml;ter noch einmal versuchen: ".
		 "Es werden zweimal am Tag inaktive Spieler gel&ouml;scht und deren ".
		 "Accounts f&uuml;r Neuanmeldungen frei gegeben.";
    
    // noch nichts eingegeben
    } else if (!($params->username  != "" ||
                 $params->password  != "" ||
                 $params->password2 != "" ||
                 $params->email     != "" ||
                 $params->email2    != "" ||
                 $params->agb       != "")){
      $show_form = TRUE;
    
    // Eingabe unvollständig
    } else if (!($params->username  != "" &&
                 $params->password  != "" &&
                 $params->password2 != "" &&
                 $params->email     != "" &&
                 $params->email2    != "" &&
                 $params->agb       != "")){
      $show_form = TRUE;
      $message = "Bitte f&uuml;llen Sie alle Felder aus!";
  
    // Passwörter stimmen nicht  
    } else if($params->password != $params->password2){
      $show_form = TRUE;
      $message = "Die Pa&szlig;w&ouml;rter stimmen nicht &uuml;berein!";

    // Email stimmen nicht  
    } else if($params->email != $params->email2){
      $show_form = TRUE;
      $message = "Die Emailadressen stimmen nicht &uuml;berein!";

    // Name matched nicht mit REGEXP_PLAYER
    } else if(!preg_match(REGEXP_PLAYER, unhtmlentities($params->username))){
      $show_form = TRUE;
      $message = "Der Benutzername enthält ungültige Zeichen!";

    // Passwort matched nicht mit REGEXP_PASSWORD
    } else if(!preg_match(REGEXP_PASSWORD, unhtmlentities($params->password))){
      $show_form = TRUE;
      $message = "Das Passwort enthält ungültige Zeichen!";

    // Geschlecht stimmt nicht
    } else if($params->sex != "m" && $params->sex != "w"){
      $show_form = TRUE;
      $message = "Ungültiges Geschlecht!";

    // alles OK, Login Namen checken
    } else if($distance < 3) {
      $show_form = TRUE;
      $message   = "Der Benutzername &auml;hnelt einem der verbotenen ".
                   "W&ouml;rter. Bitte w&auml;hlen Sie daher einen anderen ".
                   "Namen. Zu beachten ist: Namen, die eines der verbotenen ".
                   "Wörter enthalten oder einem dieser Worte &auml;hneln, ".
                   "sind nicht erlaubt.<br>Die Ausschlussliste umfasst ".
                   "zur Zeit: ";
      foreach($cfg[FORBIDDEN_NAMES] AS $k => $name) {
        $message .= $name ." ";
      }
    } else {
      $query = "SELECT user FROM LoginReserved ".
               "WHERE user LIKE '$params->username' ".
               "AND password NOT LIKE '$params->password'";
      $reserved_user = $db_login->query($query);
      
      $query = "SELECT user FROM Login WHERE user='" . $params->username . "'";
      $existing_user = $db_login->query($query);
      
      $query = "SELECT email FROM Login WHERE email='" . $params->email . "'";
      $existing_email = $db_login->query($query);
      
      // Fehler
      if (!$existing_user || !$existing_email || !$reserved_user ){
        $message = "Fehler in der DB (2.)!";
      
      // benutzername existiert schon
      } else if (!$existing_user->isEmpty()){
        $message = "Der Benutzername <b>" . $params->username . "</b> ist schon vergeben.";
        $show_form = TRUE;
      }
  
      // benutzername reserviert ?
      else if (!$reserved_user->isEmpty()) {
        $message = "Der Benutzername <b>" . $params->username . "</b> ist reserviert. Bitte geben Sie das korrekte Passwort aus der letzten Runde ein, wenn Sie diesen Namen wiederverwenden wollen.";
        $show_form = TRUE;     

      // email existiert schon
      } else if (!$existing_email->isEmpty()){
        $message = "Auf die Email <b>" .  $params->email . "</b> ist schon ein Benutzer registriert.";
        $show_form = TRUE;
           
      // alles klar, versuch einzutragen ...
      } else {
        
        if (!game_createAccount($db_login,
                                $params->username,
                                $params->password,
                                $params->email,
                                EASY_START && $params->easyStart,
                                $params->sex)){
          $message = "Schwerer Fehler beim Anlegen des Benutzerkontos!";
        } else {
          $message = "Ihr Benutzerkonto wurde angelegt. Ihnen wurde ein Aktivierungskode zugesendet. " .
                     "Bitte aktivieren Sie Ihr Konto innerhalb der nächsten 48 Stunden, " .
                     "andernfalls wird es automatisch gelöscht.";
        }        
      }          
        
    }
  }
  
  if ($show_form){
    tmpl_set($template, array('CREATEFORM/create_link'  => "portal.php",
                              'CREATEFORM/agb_link'     => "portal.php?modus=" . SHOW_AGB,    
                              'CREATEFORM/ARGUMENT' => array('name' => "modus",
                                                       'value' => CREATE_ACCOUNT)));    
    if (EASY_START) {
      tmpl_set($template, 
	       array('CREATEFORM/EASY_START_OPTION/selected' => (EASY_START_SELECTED?"checked":"")));
    }
  }
  
  tmpl_set($template, 'message', $message);  
  return tmpl_parse($template);
}

/** This function activates an Account
 *
 */
function login_activateAccount($db_login, $username, $password){
  global $cfg, $params;
  
  $template = @tmpl_open('./templates/login_activate_account.ihtml');
  $show_form = FALSE;
  
  if ($username == "" || $password == ""){
    $message = "Bitte loggen Sie sich erst ein.";
  
  } else if (!isset($params->actcode)){
    $show_form = TRUE;
  
  } else {
    $query = "SELECT user FROM Login WHERE user='{$username}' " .
             "AND password='{$password}' " .
             "AND activationID='{$params->actcode}' " .
             "AND NOT (activated='1')";
    $result = $db_login->query($query);
    if (!$result || $result->isEmpty()){
      $message = "Fehler beim Aktivieren!";
    } else {
      
      $query = "UPDATE Login SET activated=1 " .
               "WHERE user='{$username}' " .
               "AND password='{$password}' " .
               "AND activationID='{$params->actcode}'";
      if ($db_login->query($query)){
        $message = "Das Konto {$username} wurde erfolgreich aktiviert <br>" .
                   "Sie koennen sich nun eingeloggen.";
      }

    }
  }
    
  
  if ($show_form){
    tmpl_set($template, array('ACTIVATEFORM/activate_link' => "portal.php",
                              'ACTIVATEFORM/ARGUMENT'      => array(array('name' => "modus", 'value' => ACTIVATE_ACCOUNT),
                                                                    array('name' => "username", 'value' => $username),
                                                                    array('name' => "password", 'value' => $password))));    
  }
  
  tmpl_set($template, 'message', $message);  
  return tmpl_parse($template);
}

function getActiveConnections($url) {
    if (!($id = @fopen($url."/users.php", "r"))) {
	return 9999;
    }
    fscanf($id, "%d", $num);
    fclose($id);    

    return $num;
}

/**
 * load balances the connections to different servers. returns the id of
 * the server, that should be used.
 */
function getFreeServerID() {
    global $cfg;

    $load = array();
    for ($i=0; $i < count($cfg['GAME_LOAD']); $i++) {
	$load[$i] = getActiveConnections($cfg['GAME_BASE'][$i])  / 
	    $cfg['GAME_LOAD'][$i] +
            (rand(0, 1000) / 1000) * 0.5 ;  // add epsilon [0 .. 0.5]
    }
    return array_search(min($load), $load);  // key of the smallest loaded s.
}

// ADDED by chris--- for urlaubsmod
// -----------------------------------------------------------------------------------------

function login_vacation($db_login, $username, $password){
  global $cfg, $params;

  $template =  @tmpl_open('./templates/login_vacation.ihtml');
  $show_form = FALSE;

  if ($username != ""){

    // hole User Daten
    $query = "SELECT * FROM Login WHERE user='" . $username . "'";
    $result = $db_login->query($query);

    // Fehler?
    if (!$result){
      $message = "Fehler in der DB!";

    // existiert gar nicht?
    }   else if ($result->isEmpty()){
        $message = "Der Account: " . $username . " existiert nicht.";
        $show_form = TRUE;
      
      // existiert doch ...
      } else {
        $ud = $result->nextRow();
        // Passwort richtig?
        if ($ud['password'] == $password) {
          // Sperrfrist verstrichen?
          if ($ud['urlaub_begin'] < time()-3*24*60*60) {
            if (end_vacation($db_login, $username)) $message = "Urlaubsmodus beendet<br><br>Bitte log dich neu ein!<br><br>";
              else $message = "Es ist ein Fehler aufgetreten. Bitte melden das einem Admin wenn das Problem weiter besteht.<br><br>";
          } else {
            $datum = date("d.m.Y u\m H:i:s", $ud['urlaub_begin']);
            $datum2 = date("d.m.Y u\m H:i:s", $ud['urlaub_begin']+3*24*60*60);
            $message = "Du bist noch innehalb der Sperrfrist (3 Tage ab Urlaubsbeginn).<br>Dein Urlaubsbeginn war am ".$datum."<br><br>Du kannst dich fr&uuml;hstens am ".$datum2." wieder einloggen.<br><br>";
          }
        } else {
          $message = "Das Passwort ist falsch.";
          $show_form = TRUE;
        }
      }
  } else {
    $show_form = TRUE;
  }
  
  if ($show_form){
    tmpl_set($template, array('FORM/link'  => "portal.php",
                              'FORM/ARGUMENT' => array('name' => "modus", 'value' => VACATION)));    
  }
  
  tmpl_set($template, 'message', $message);  
  return tmpl_parse($template);
}

function end_vacation($db_login, $username){
  global $cfg, $params;

  // get link to DB 'game'
  $db_game = new DB($cfg['DB_GAME']['HOST'], $cfg['DB_GAME']['USER'],
                    $cfg['DB_GAME']['PWD'], $cfg['DB_GAME']['NAME']);
  if (!$db_game) return FALSE;

  // playerID holen
  $query = "SELECT playerID FROM Player WHERE Name = '".$username."'";
  if (!($result=$db_game->query($query))) {
//	echo $query."<br>";
	return FALSE;
  }
  if ($result->isEmpty()) {
//	echo $query."<br>";
	return FALSE;
  }
  $game = $result->nextRow();
  $playerID = $game['playerID'];

  // Reset Player Table
  $query = "UPDATE Player SET urlaub = 0 WHERE playerID = ".$playerID;
  if (!$db_game->query($query)) {
//	echo $query."<br>";
	return FALSE;
  }

  // Reset Cave Table
  $query = "UPDATE Cave SET secureCave = secureCave_was WHERE playerID = ".$playerID;
  if (!$db_game->query($query)) {
//	echo $query."<br>";
	return FALSE;
  }
  $endtime = date("YmdHis", time());
  $query = "UPDATE Cave SET urlaub = 0, protection_end = ".$endtime." WHERE playerID = ".$playerID;
  if (!$db_game->query($query)) {
//	echo $query."<br>";
	return FALSE;
  }

  // get link to DB 'login'
  $db_login = new DB($cfg['DB_LOGIN']['HOST'], $cfg['DB_LOGIN']['USER'],
                    $cfg['DB_LOGIN']['PWD'], $cfg['DB_LOGIN']['NAME']);
  if (!$db_login) return FALSE;

  // Reset login table
  $query = "UPDATE login SET urlaub=0, urlaub_end=".time()." WHERE user='".$username."'";
  if (!$db_login->query($query)) {
//	echo $query."<br>";
	return FALSE;
  }

// echo "Alles ok!<br>";
return TRUE;
}
// END urlaubsmod --------------------------------------------------------------------------


?>
