<?
/*
 * tribePlayerList.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribePlayerList_getContent($caveID, $tribe) {
  global $db, $no_resource_flag, $config, $params;

  $no_resource_flag = 1;

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'tribePlayerList.ihtml');

  tmpl_set($template, 'tribe', $tribe);


  $query = 'SELECT r.rank, r.playerID AS link, r.name, r.average AS points, r.caves, r.religion, r.fame, p.awards ' .
           ' FROM Ranking r' .
           ' LEFT JOIN Player p' .
           ' ON p.playerID = r.playerID' .
           ' WHERE p.tribe LIKE "' . $tribe . '"' .
           ' ORDER BY r.rank ASC';

  if (!($dbresult = $db->query($query))) page_dberror();

  while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $i++;
    tmpl_iterate($template, 'ROWS');
    $row['link'] = "main.php?modus=" . PLAYER_DETAIL . "&detailID=" . $row['link'] . "&caveID=" . $caveID;

    if (!empty($row['awards'])){
      $tmp = explode('|', $row['awards']);
      $awards = array();
      foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      $row['award'] = $awards;
    }

    if ($i % 2)
      tmpl_set($template, 'ROWS/ROW_ALTERNATE', $row);
    else
      tmpl_set($template, 'ROWS/ROW',           $row);
  }

  return tmpl_parse($template);
}

?>
