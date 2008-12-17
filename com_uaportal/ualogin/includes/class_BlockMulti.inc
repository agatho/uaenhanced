<?php
/*
 * class_BlockMulti.inc - The Time
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

class ualogin_BlockMulti {

  /**
   * create class
   */
  function ualogin_BlockMulti() {

    $this->display();
  }

  /**
   * Display Page
   */
  function display() {
    global $db_login;

    $data = array();

    /**
     * modul or login disable?
     */
    $disabled = ( variable_get("ualogin_disable_game", 1) == 1 ) ? 1 : variable_get("ualogin_disable_login", 1);
    if ( $disabled ) {
      $data['/DISABLED/iterate'] = '';
      $data['/DISABLED/message'] = 'Die Runde wurde beendet.';
    }
    else {
      /**
       * select the multis
       */
      $query = 'SELECT user FROM Login WHERE multi = 66 ORDER BY user ASC';
      $result = $db_login->query($query);

      if ( $result === FALSE ) {
        $data['/DISABLED/iterate'] = '';
        $data['/DISABLED/message'] = 'Keine Datenbankverbindung.';

        return FALSE;
      }

      while ( $row = $db_login->fetchrow($result) ) {
        $item[] = $row['user'];
      }

      /**
       * send to the template
       */
      if ( empty($item) ) {
        $data['/DISABLED/iterate'] = '';
        $data['/DISABLED/message'] = 'Es gibt zur Zeit keine Multis.';
      }
      else {
        $data['/MULTI/iterate'] = '';
        $data['/MULTI/multi'] = implode(", ", $item);
      }
    }

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('BlockMulti');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }
}
