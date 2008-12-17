<?
/*
 * View.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class View {

  var $template;

  function openTemplate($language, $skin, $template){
    global $config;

    $this->template = tmpl_open(sprintf('%s/templates/%s/%s/%s', UA_GAME_DIR, $language, $config->template_paths[$skin], $template));
  }
  
  function getTitle(){
    return $this->template ? tmpl_parse($this->template, '/TITLE') : __CLASS__;
  }
}
