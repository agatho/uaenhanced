<?php
/*
 * class_login.inc - Show View
 * Copyright (c) 2007 David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Show form.
 *
 * @author  David Unger
 * @package    ualogin
 * @subpackage includes
 */

define("LOGIN_SUCCESS",              1);
define("LOGIN_FAILURE",              0);

define("LOGIN_WRONG_SECURITY_CODE", -1);
define("LOGIN_ACTIVATE_FIRST",       2);
define("LOGIN_CREATED",              3);

class ualogin_login {

  var $username, $password, $captcha, $captcha_id, $nogfx, $noscript;
  var $user, $output;

  /**
   * create class
   */
	function ualogin_login() {

	  $this->validate();
	  $this->display();
	}

  /**
   * Display Page
   */
  function display() {

    $data = array();
    $disabled = ( variable_get("ualogin_disable_game", 1) == 1 ) ? 1 : variable_get("ualogin_disable_login", 1);
    if ( $disabled ) {
      $data['/DISABLED/iterate'] = '';
    }
    else {
      $show_captcha = variable_get("ualogin_show_captcha", 1);
      $GameName     = variable_get("ualogin_game_mame", '');
      $GameImage    = variable_get("ualogin_game_image", '');
      $GameText     = variable_get("ualogin_game_text", '');

      $data['/LOGIN/iterate'] = '';
      if ( variable_get("ualogin_game_enable", 1) && !(empty($GameName) && empty($GameImage) && empty($GameText)) ) {
        $data['/LOGIN/GAME/iterate'] = '';

        if ( !empty($GameName) ) {
          $data['/LOGIN/GAME/GAMENAME/name']    = $GameName;
        }

        if ( !empty($GameImage) ) {
          $data['/LOGIN/GAME/GAMEIMAGE/image']  = $GameImage;
        }

        if ( !empty($GameText) ) {
          $data['/LOGIN/GAME/GAMETEXT/text']    = $GameText;
        }
      }

      if ( !empty($show_captcha) ) {
        require_once(drupal_get_path('module', 'ualogin') .'/includes/class_captcha.inc');

        $captcha = new ualogin_captcha;
        $captcha->generate();
        $data['/LOGIN/CAPTCHA/captcha_id'] = $captcha->captcha_id;
        unset($captcha);
      }
    }

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('Login');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }

  /**
   * check form
   */
  function validate() {
    global $input;

    if ( !isset($_POST['submit']) ) {
      return TRUE;
    }

    $this->username   = $input->read('username', TYPE_NO_HTML, true);
    $this->password   = $input->read('password', TYPE_NO_HTML, true);
    $this->captcha    = $input->read('captcha', TYPE_NO_HTML, true);
    $this->captcha_id = $input->read('captcha_id', TYPE_NO_HTML, true);
    $this->nogfx      = $input->read('nogfx', TYPE_INT, true);
    $this->noscript   = $input->read('noscript', TYPE_INT, true);

    if ( !isset($this->username) || strlen($this->username) == 0 ) {
      drupal_set_message(t('Du hast keinen Usernamen eingegeben.'));
      return FALSE;
    }

    if ( !isset($this->password) || strlen($this->password) == 0 ) {
      drupal_set_message(t('Du hast kein Passwort eingegeben.'));
      return FALSE;
    }

    if ( !$this->check_user() ) {
      return FALSE;
    }

    if ( !$this->login() ) {
    	return FALSE;
    }
  }

  function check_user() {
    global $db_login, $input;

    /**
     * fetch user from database
     */
    $query = 'SELECT *, NOW() + 0 AS jetzt, DATE_FORMAT(ban, \'%%e.%%c.%%Y %%k:%%i\') AS time
               FROM Login
               WHERE user = \'' . $db_login->sql_escape_string($this->username) . '\'
               LIMIT 0,1';
    $result = $db_login->query($query);

    // db error
    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror'));
      return FALSE;
    }
    else if ( $db_login->numrows($result) != 1 ) {
      $this->log(LOGIN_FAILURE);
      drupal_set_message(t('Kein User mit diesem Namen.'));
      return FALSE;
    }
    else {
      $this->user = $db_login->fetchobjekt($result);
    }

    /**
     * check captcha
     */
    if ( $this->user->noseccode == 0 && variable_get("ualogin_show_captcha", 1) ) {
      if ( !isset($this->captcha) || strlen($this->captcha) == 0 ) {
        drupal_set_message(t('Bitte gib den korrekten Security Code ein!'));
        return FALSE;
      }

      if ( !isset($this->captcha_id) || strlen($this->captcha_id) == 0 ) {
        drupal_set_message(t('Session Fehler bei der Security Code Überprüfung!'));
        return FALSE;
      }

      require_once(drupal_get_path('module', 'ualogin') .'/includes/class_captcha.inc');

      $captcha = new ualogin_captcha;
      $check = $captcha->check($this->captcha, $this->captcha_id);
      $captcha->delete($this->captcha_id);
      unset($captcha);

      if ( $check === FALSE ) {
        $this->log(LOGIN_WRONG_SECURITY_CODE);
        drupal_set_message(t('Bitte gib den korrekten Security Code ein!'));
        return FALSE;
      }
    }

    /**
     * check password
     */
    if ( strcmp($this->user->password, $this->password) ) {
      $this->log(LOGIN_FAILURE);
      drupal_set_message(t('Du hast das falsche Passwort eingegeben.'));
      return FALSE;
    }

    /**
     * delete?
     */
    if ( $this->user->deleted == 1 ) {
      $this->log(LOGIN_FAILURE);
      drupal_set_message(t('Dieser Account ist gel&ouml;scht worden.'));
      return FALSE;
    }

    /**
     * ban?
     */
    if ( $this->user->multi != 0 ) {
      $message = array();

      $query = 'SELECT *
                 FROM Block
                 WHERE blockid = ' . intval($this->user->multi);
      $result = $db_login->query($query);

      if ($result === FALSE) {
        drupal_set_message(t('ua_dberror'));
        return FALSE;
      }

      $block = $db_login->fetchobjekt($result);

      if ( !isset($block->reason) || empty($block->reason) ) {
        $block->reason = 'Dieser Account wurde gesperrt und ein Login ist nicht m&ouml;glich.';
      }

      if ( $this->user->ban == 0 ) {
        $message[] = utf8_encode($block->reason);
      }
      else if ( $this->user->ban != 0 && $this->user->jetzt < $this->user->ban ) {
        $message[] = utf8_encode($block->reason);

        $banYear   = substr($this->user->ban, 0, 4);
        $banMonth  = substr($this->user->ban, 4, 2);
        $banDay    = substr($this->user->ban, 6, 2);
        $banHour   = substr($this->user->ban, 8, 2);
        $banMinute = substr($this->user->ban, 10, 2);
        $banDate   = $banDay . '.' . $banMonth . '.' . $banYear  . ' ' . $banHour . ':' . $banMinute;

        $message[] = 'Dieser Account ist noch bis ' . $banDate . ' Uhr gesperrt.';
      }

      if ( !empty($message) ) {
        $this->log(LOGIN_FAILURE);

        foreach ( $message as $msg ) {
          drupal_set_message($msg);
        }

        return FALSE;
      }
    }

    /**
     * activate?
     */
    if ( $this->user->activated != 1 ) {
      $input->session_add('username', $this->username);

      $this->log(LOGIN_ACTIVATE_FIRST);
      drupal_set_message(t('Du mußt dich vor dem einloggen erst Aktivieren.'));
      drupal_goto("ualogin/Activate");
      return FALSE;
    }

    return TRUE;
  }

  /*
   * get server load
   */
  function getServerLoad($url, $serverID) {

    $url_p = parse_url($url);

    $fp = @fsockopen($url_p['host'], 80, $errno, $errstr);
    if ( !$fp ) {
      return -2;
    }

    $server_pass = variable_get('ualogin_game_password_' . $serverID, '');

    @fputs($fp, "GET ".$url_p['path']." HTTP/1.0".chr(10));
    @fputs($fp, "HOST: " . $url_p['host'].chr(10));
    if ( $server_pass ) {
        @fputs($fp, "Authorization: Basic ".base64_encode($server_pass).chr(10));
    }
    @fputs($fp, "Connection: close".chr(10).chr(10));

    $body = false;
    while( !@feof($fp) ) {
      $s = @fgets($fp, 8192);
      if ( $body ) {
        $output .= $s;
      }

      if ( $s == "\r\n" ) {
        $body = true;
      }
    }
    @fclose($fp);

    if ( !is_numeric($output) ) {
      return -1;
    }

    return $output;
  }

  /**
   * load balances the connections to different servers. returns the id of
   * the server, that should be used.
   */
  function getFreeServerID() {

    $num_servers = variable_get('ualogin_game_servers', 1);

    if ( $num_servers == 1 ) {
      $url_p = parse_url(variable_get('ualogin_game_base_1', ''));
      $fp = @fsockopen($url_p['host'], 80, $errno, $errstr);
      if ( !$fp ) {
        return FALSE;
      }
      @fclose($fp);

      return 1;
    }

    $loads = array();
    for ( $i = 1; $i <= $num_servers; ++$i ) {
      $base = variable_get('ualogin_game_base_' . $i, '');
      $load[ $i ] = $this->getServerLoad($base, $i);

      if ( $load[ $i ] < 0 ) {
        unset($load[ $i ]);

        if ( $load[ $i ] == -1 ) {
          $error_server[] = $i;
        }
      }
    }

    /**
     * no server? select site error server
     */
    if ( count($load) == 0 ) {
      $count = count($error_server);
      if ( $count == 0 ) {
        return FALSE;
      }
      $rand_id = rand(1, $count);

      return $error_server[ $rand_id ];
    }

    return array_search(min($load), $load);
  }

  /*
   * This function writes useful data to the LoginLog DB table
   */
  function log($success) {
    global $db_login, $input;

    /**
     * get users infos
     */
    $donotlog = array("HTTP_USER_AGENT", "HTTP_HOST", "HTTP_CONNECTION", "HTTP_CACHE_CONTROL", "HTTP_KEEP_ALIVE");
    $misc = "";

    foreach ( array_keys($_SERVER) AS $key ) {
      if ( strncmp($key, "HTTP_", 5) == 0 ) {
        if ( !in_array($key, $donotlog) ) {
          $misc .= sprintf("%s:\t%s\n", $key, $_SERVER[ $key ]);
        }
      }
    }

    /**
     * insert into log table
     */
    $fields = array(
      'user'                => $this->username,
      'password'            => $this->password,
      'success'             => intval($success),
      'ip'                  => $_SERVER['REMOTE_ADDR'],
      'request_method'      => $_SERVER['REQUEST_METHOD'],
      'request_uri'         => $_SERVER['REQUEST_URI'],
      'http_user_agent'     => $_SERVER['HTTP_USER_AGENT'],
      'pollID'              => $_COOKIE['UAPOLLID'],
      'security_code'       => $input->session_read('captcha'),
      'typed_security_code' => $this->captcha,
      'seccode_time'        => time() - intval($input->session_read('show_captcha_time')),
      'misc'                => $misc,
    );
    $db_login->sql_statement($fields);
    $query = 'INSERT INTO LoginLog
             (' . $db_login->sql_fields . ') VALUES(' . $db_login->sql_values . ')';
    $db_login->query($query);
  }

  /*
   * This function ...
   */
  function login() {
    global $db_game, $input;

    srand((double) microtime() * 1000000);
    $s_id = uniqid(rand(), 1);

    /**
     * generate new session
     */
    $fields = array(
      's_id'          => $s_id,
      'playerID'      => $this->user->LoginID,
      'loginip'       => $_SERVER['REMOTE_ADDR'],
      'loginchecksum' => md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_ACCEPT_CHARSET'] . $_SERVER['HTTP_ACCEPT_LANGUAGE']),
      'loginnoscript' => intval($this->noscript),
    );
    $db_game->sql_statement($fields);
    $query = 'REPLACE INTO Session
             (' . $db_game->sql_fields . ') VALUES(' . $db_game->sql_values . ')';
    if ( !$db_game->query($query) ) {
      drupal_set_message(t('ua_dberror'));
      return FALSE;
    }

    /**
     * get server id
     */
    $serverID = $this->getFreeServerID();
    if ( $serverID === FALSE ) {
      drupal_set_message(t('Zur Zeit ist leider kein Server erreichbar. Bitte versuche es später erneut.'));
      return FALSE;
    }

    /**
     * delete drupal session data
     */
    $input->session_del();

    /**
     * generate redirect url
     */
    $url = sprintf("%s?id=%s&userID=%d",
                   variable_get('ualogin_game_url_'  . $serverID, ''),
                   $s_id, $this->user->LoginID);

    if ( $this->nogfx == 1 ) {
      $url .= "&nogfx=1";
    }

    /**
     * log successful login
     */
    $this->log(LOGIN_SUCCESS);

    /**
     * redirect
     */
    module_invoke_all('exit');
    header("Location: " . $url);
    exit();
  }
}
