<?php
/*
 * class_password.inc - Show View
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

class ualogin_password {

  /**
   * create class
   */
  function ualogin_password() {

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
      $data['/PASSWORD/iterate'] = '';
    }

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('LostPassword');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }

  function validate() {
    global $db_login, $input;

    if ( !isset($_POST['submit']) ) {
      return TRUE;
    }

    $this->username = $input->read('username', TYPE_NO_HTML, true);

    if ( !isset($this->username) || strlen($this->username) == 0 ) {
      drupal_set_message(t('Du hast keinen Usernamen eingegeben.'));
      return FALSE;
    }

    /**
     * select last password resend
     */
    $query = 'SELECT user, password, email, lastResend
               FROM Login
               WHERE user = \'' . $db_login->sql_escape_string($this->username) . '\'';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror_password_1'));
      return FALSE;
    }

    $this->user = $db_login->fetchrow($result);

    if ( $this->user['lastResend'] > date("YmdHis") - intval(variable_get("LoginPwdResendTimeout", 350)) ) {
      drupal_set_message(t('Du hast dir zu oft das Passwort schicken lassen.'));
      return FALSE;
    }

    /*
     * Send Password
     */
    $mail = new ualogin_mail;
    $mail->replyto = $this->user['email'];
    $mail->subject = "Ihr Passwort bei " . $_SERVER['HTTP_HOST'];
    $mail->message = "Hier Ihre Account-Informationen:\n" .
                     "Spieler: " . $this->user['user'] . "\nPasswort: " . $this->user['password'];
    if ( !$mail->send() ) {
      drupal_set_message(t('Beim Passwort verschicken ist ein Fehler aufgetreten'));
      return FALSE;
    }
    unset($mail);

    $query = 'UPDATE Login
               SET lastResend = NOW() + 0, countResend = countResend + 1
               WHERE LoginID = ' . intval($this->user['LoginID']);
    $db_login->query($query);

    drupal_set_message(t('Das Passwort wurde erfolgreich abgeschickt.'));
    return TRUE;
  }
}
