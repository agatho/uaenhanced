<?php
/*
 * class_BlockTime.inc - The Time
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

define("STARTING_YEAR",   1);
define("MONTHS_PER_YEAR", 10);
define("DAYS_PER_MONTH",  24);
define("HOURS_PER_DAY",   24);

define("SPEED_RATIO",     24);

/* Mon Sep  2 00:00:00 CEST 2002 */
define("START_TIME",      1030917600);

class ualogin_BlockTime {

  /**
   * create class
   */
  function ualogin_BlockTime() {

    $this->display();
  }

  /**
   * Display Page
   */
  function display() {

    $hours  = (int)((time() - START_TIME) * SPEED_RATIO / (60 * 60));
    $days   = (int)($hours / HOURS_PER_DAY);
    $months = (int)($days / DAYS_PER_MONTH);

    $data = array();
    $data['hour']       = $hours  % HOURS_PER_DAY;
    $data['day']        = $days   % DAYS_PER_MONTH  + 1;
    $data['month']      = $months % MONTHS_PER_YEAR + 1;
    $data['month_name'] = $this->getMonthName($retval['month']);
    $data['year']       = (int)($months / MONTHS_PER_YEAR) + STARTING_YEAR;
    $data['realtime']   = date("H:i:s");

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('BlockTime');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }

  function getMonthName($month) {

    $monthNames = array('Agga',
                        'Eisigkeit',
                        'Schnehbrandh',
                        'Binenschtich',
                        'Brrunfhd',
                        'Uga',
                        'Ernte',
                        'Duesternis',
                        'Verderb',
                        'Frrost'
    );

    return $monthNames[ ($month - 1) % sizeof($monthNames) ];
  }
}
