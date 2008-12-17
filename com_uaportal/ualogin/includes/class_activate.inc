<?php
/*
 * class_activate.inc - Activate Account
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

class ualogin_activate {

  var $username, $password, $actCode;

  /**
   * create class
   */
  function ualogin_activate() {

    $this->validate();
    $this->display();
  }

  /**
   * Display Page
   */
  function display() {
    global $input;

    $data = array();

    /**
     * modul or login disable?
     */
    $disabled = ( variable_get("ualogin_disable_game", 1) == 1 ) ? 1 : variable_get("ualogin_disable_register", 1);
    if ( $disabled ) {
      $data['/DISABLED/iterate'] = '';
    }
    else {
      $data['/ACTIVATE/iterate'] = '';
      $data['/ACTIVATE/username'] = $input->session_read('username');
    }

    $input->session_del('username');

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('Activate');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }

  function validate() {
    global $db_login, $input;

    if ( !isset($_POST['submit']) && !(isset($_GET['username']) && isset($_GET['actcode'])) ) {
      return TRUE;
    }

    if ( isset($_POST['submit']) ) {
      $this->username = $input->read('username', TYPE_NO_HTML, true);
      $this->actCode  = $input->read('actcode', TYPE_INT, true);
    }
    else {
      $this->username = $input->read('username', TYPE_NO_HTML);
      $this->actCode  = $input->read('actcode', TYPE_INT);
    }

    /**
     * validate username
     */
    if ( !isset($this->username) || strlen($this->username) == 0 ) {
      drupal_set_message(t('Bitte einen Usernamen eingeben'));
      return FALSE;
    }

    /**
     * validate activation code
     */
    if ( !isset($this->actCode) || strlen($this->actCode) == 0 ) {
      drupal_set_message(t('Bitte einen Aktivierungscode eingeben'));
      return FALSE;
    }

    /**
     * user exists?
     */
    $query = 'SELECT count(*)
               FROM Login
               WHERE user = \'' . $db_login->sql_escape_string($this->username) . '\'
                 AND activationID = ' . intval($this->actCode) . '
                 AND activated <> 1';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror'));
      return FALSE;
    }

    /**
     * return false if no user exists
     */
    if ( $db_login->numrows($result) == 0 ) {
      drupal_set_message(t('Username und Aktivierungscode stimmen nicht überein.'));
      return FALSE;
    }

    /**
     * activate user
     */
    $query = 'UPDATE Login
               SET activated = 1
               WHERE user = \'' . $db_login->sql_escape_string($this->username) . '\'
                 AND activationID = ' . intval($this->actCode);
    if ( $db_login->query($query) ) {
      drupal_set_message(t('Das Konto '.$this->username.' wurde erfolgreich aktiviert.<br>Sie k&ouml;nnen sich nun eingeloggen.'));
      drupal_goto("ualogin/Login");
      return TRUE;
    }
    else {
      drupal_set_message(t('Beim Aktivieren des Kontos '.$this->username.' ist ein Fehler aufgetreten. Bitte versuche es später nochmals.'));
      return FALSE;
    }
  }
}
