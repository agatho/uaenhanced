<?
/*
 * tribeAdmin.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function doYouKnow_getContent() {
  global $config;
  global $params;
  global $db;
  
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'doYouKnow.ihtml');


  if ($params->POST->show=="all") {
    $query = "SELECT * FROM `doYouKnow`";
  } else {
    $query = "SELECT * FROM `doYouKnow` ORDER BY RAND( ) LIMIT 0 , 1";
  }

  $result = $db->query($query);
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    tmpl_iterate($template, "ELEM");
    tmpl_set($template,array("ELEM/header" => $row['titel'], 
                           "ELEM/text" => $row['content']));
  }			   

  if ($params->POST->show!="all")
    tmpl_iterate($template, "LINKLIST");


  return tmpl_parse($template);
}
