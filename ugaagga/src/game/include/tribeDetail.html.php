<?
/*
 * tribeDetail.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function tribe_getContent($caveID, $tag) {
  global $db, $no_resource_flag, $config, $params;

  $no_resource_flag = 1;
  if (!($r = $db->query("SELECT t.*, p.playerID, p.name AS leaderName ".
      "FROM Tribe t ".
      "LEFT JOIN Player p ".
      "ON p.playerID = t.leaderID ".
      "WHERE t.tag LIKE '$tag'")))
    page_dberror();

    
  if (!($row = $r->nextRow(MYSQL_ASSOC))) page_dberror();

  
  $JuniorAdmin = $targetPlayer = new Player(getPlayerByID($row['juniorLeaderID'])); 
  
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'tribeDetail.ihtml');

  $row["urltag"] = urlencode(unhtmlentities($tag));
  $row["playerList_modus"]   = TRIBE_PLAYER_LIST;
  $row["playerDetail_modus"] = PLAYER_DETAIL;
  $row["tribeHistory_modus"] = TRIBE_HISTORY;
  $row["tribeRelationList_modus"] = TRIBE_RELATION_LIST;

  if (!empty($row['awards'])){
    $tmp = explode('|', $row['awards']);
    $awards = array();
    foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
    $row['award'] = $awards;
  }

  foreach($row as $k => $v)
    if (!$v) $row[$k] = "k.A.";

  $row['juniorLeaderName'] = $JuniorAdmin->name; 
  $row['juniorLeaderID'] = $JuniorAdmin->playerID; 

  tmpl_set($template, 'DETAILS', $row);

  return tmpl_parse($template);
}

?>