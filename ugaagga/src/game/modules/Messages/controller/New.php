<?php
/*
 * New.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Controller.php');
require_once('modules/Messages/model/New.php');
require_once('modules/Messages/view/New.php');

class Messages_New_Controller extends Controller {

  function execute($caveID, $caves){
    global $params;

    // create View
    $view = new  Messages_New_View($params->SESSION->player->language,
                                   $params->SESSION->player->template);

    // create Model
    $model = new Messages_New_Model();
    
    // set count
    $view->setCount($model->getCount());

    return array($view->getTitle(), $view->getContent());
  }
}
?>