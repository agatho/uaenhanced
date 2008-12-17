<?php
/*
 * class_config.inc - Config
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

define('TYPE_INT', 1);
define('TYPE_FLOAT', 2);
define('TYPE_NO_HTML', 3);

define('SESSION_NAME', 'ualogin');

class ualogin_read {

  function ualogin_read() {

  }

  function read($var, $type, $post_only=false) {

    if ( $post_only ) {
      $var = ( isset($_POST[ $var ]) ) ? $_POST[ $var ] : '';
    }
    else {
      $var = ( isset($_GET[ $var ]) ) ? urldecode($_GET[ $var ]) : $_POST[ $var ];
    }

    switch ( $type ) {
      case TYPE_INT:
        $var = intval($var);
        break;

      case TYPE_FLOAT:
        $var = doubleval($var);
        break;

      default:
      case TYPE_NO_HTML:
        $var = $this->clean_html($var);
        break;
    }

    return $var;
  }

  function clean_html($var) {

    return trim(htmlentities(strip_tags($var), ENT_QUOTES));
  }

  function session_read($name) {

    return ( $this->session_check($name) ) ? $_SESSION[ SESSION_NAME ][ $name ] : '';
  }

  function session_add($name, $value) {

    if ( !empty($name) ) {
      $_SESSION[ SESSION_NAME ][ $name ] = $value;
    }
  }

  function session_del($name='') {

    if ( !empty($name) ) {
      unset($_SESSION[ SESSION_NAME ][ $name ]);
    }
    else {
      unset($_SESSION[ SESSION_NAME ]);
    }
  }

  function session_check($name) {

    return ( isset($_SESSION[ SESSION_NAME ][ $name ]) ) ? true : false;
  }
}

class ualogin_mail {

  var $replyto, $message, $subject;

  function header() {

    srand((double) microtime() * 1000000);

    $header   = array();
    $header[] = 'From: ' . variable_get('ualogin_email', 'noreply@localhost');
    $header[] = 'Errors-To: ' . variable_get('ualogin_email', 'noreply@localhost');
    $header[] = 'Reply-To: ' . $this->replyto;
    $header[] = 'Return-Path: <' . variable_get('ualogin_email', 'noreply@localhost') . '>';
    $header[] = 'Sender: <' . variable_get('ualogin_email', 'noreply@localhost') . '>';
    $header[] = 'MIME-Version: 1.0';
    $header[] = 'Message-ID: <' . md5(uniqid (rand())) . '@' . $_SERVER['HTTP_HOST'] . '>';
    $header[] = 'Date: ' . gmdate('D, d M Y H:i:s T', time());
    $header[] = 'Content-Type: text/plain; charset=UTF-8; format=flowed; delsp=yes';
    $header[] = 'Content-Transfer-Encoding: 8bit';
    $header[] = 'X-Mailer: PHP/' . phpversion();

    return implode("\n", $header);
  }

  function send() {

    if ( empty($this->replyto) || empty($this->subject) || empty($this->message) ) {
      return FALSE;
    }

    if ( !@mail($this->replyto, $this->subject, $this->message, $this->header()) ) {
      return FALSE;
    }

    return TRUE;
  }
}
