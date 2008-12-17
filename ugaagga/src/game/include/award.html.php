<?
/*
 * award.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/** This function returns basic award details
 *
 *  @param tag       the current award's tag
 */
function award_getAwardDetail($tag){

  // get configuration settings
  global $config;
  // get parameters from the page request
  global $params;
  // get db link
  global $db;

  $msgs = array();

  $sql = "SELECT * FROM Awards WHERE tag = '{$tag}'";
  $result = $db->query($sql);


  if (!$result || $result->isEmpty()){
    $msgs[] = sprintf(_('Dieser Orden existiert nicht: "%s".'), $tag);
    $row    = array();
  } else {
    $row = $result->nextRow(MYSQL_ASSOC);
  }

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'award_detail.ihtml');

  if (sizeof($msgs)){
    foreach ($msgs AS $msg){
      tmpl_iterate($template, "MESSAGE");
      tmpl_set($template, "MESSAGE/message", $msg);
    }
  }

  if (sizeof($row)){
    tmpl_set($template, 'AWARD', $row);
  }

  return tmpl_parse($template);
}

?>
