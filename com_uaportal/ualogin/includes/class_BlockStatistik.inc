<?php
/*
 * class_BlockStatistik.inc - Statistik
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

class ualogin_BlockStatistik {

  var $getAcc = false;
  var $countAcc = 0;

  /**
   * create class
   */
  function ualogin_BlockStatistik() {

    $this->display();
  }

  /**
   * Display Page
   */
  function display() {

    $data = array();

    /**
     * modul or login disable?
     */
    $disabled = ( variable_get("ualogin_disable_game", 1) == 1 ) ? 1 : variable_get("ualogin_disable_login", 1);
    if ( $disabled ) {
      $data['/DISABLED/iterate'] = '';
    }
    else {
      $data['/STATISTIK/iterate'] = '';
      $data['/STATISTIK/ITEM'] = $this->get_Statistik();

      if ( !variable_get("ualogin_disable_statistik", 1) ) {
        $data['/STATISTIK/EXTRA_STATISTIK/iterate'] = '';
      }
    }

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('BlockStatistik');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }

  function get_Statistik() {

    $data[]  = array('name'   => 'Spieler',
                     'count'  => $this->getAccounts());
    $data[]  = array('name'   => 'freie Accounts',
                     'count'  => $this->getfreeAccounts());
    $data[]  = array('name'   => 'Spieler online',
                     'count'  => $this->getOnline());

    return $data;
  }

  function getAccounts() {
    global $db_login;

    $this->getAcc = TRUE;

    /**
     * count the users
     */
    $query = 'SELECT COUNT(*) AS count FROM agatho_login.Login';
    $result = $db_login->query($query);

    if ( $result == FALSE ) {
      drupal_set_message(t('ua_dberror_blockstatistik_1'));
      return FALSE;
    }

    $this->countAcc = $db_login->fetchrow($result);

    return $this->countAcc['count'];
  }

  function getfreeAccounts() {

    if ( $this->getAcc == FALSE ) {
        $this->getAccounts();
    }

    $freeAcc = variable_get("ualogin_max_users", 0) - $this->countAcc['count'];

    return $freeAcc;
  }

  function getOnline() {
    global $db_game;

    $time = time() - 1800;
    $lastActionTime = gmdate("Y-m-d H:i:s", $time);

    /**
     * count online users
     */
    $query = 'SELECT COUNT(*) AS count
               FROM Session
               WHERE lastAction > \'' . $db_game->sql_escape_string($lastActionTime) . '\'
               ORDER BY lastAction';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror_statistikinc_2'));
      return FALSE;
    }

    $online = $db_game->fetchrow($result);

    return $online['count'];
  }
}
