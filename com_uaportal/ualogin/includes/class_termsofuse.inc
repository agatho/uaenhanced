<?php
/*
 * class_login.inc - Show TermsOfUse
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

class ualogin_termofuse {

  /**
   * create class
   */
  function ualogin_termofuse() {

    $this->display();
  }

  /**
   * Display Page
   */
  function display() {

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('TermsOfUse');
    $this->output = $template->toString();
  }
}
