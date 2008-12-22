<?
/*
 * playerDetail.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function player_getContent($caveID, $playerID) {
  global $db, $no_resource_flag, $config, $params;

  $no_resource_flag = 1;

  if (!($r = $db->query("SELECT * FROM Player WHERE playerID = '$playerID'")))
    page_dberror();

  if (!($row = $r->nextRow(MYSQL_ASSOC)))
    page_dberror();

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'playerDetail.ihtml');

  if ($row['avatar']) {
    // FIXME: should be configurable
    tmpl_set($template, 'DETAILS/AVATAR_IMG/avatar', $row['avatar']);
    tmpl_set($template, 'DETAILS/AVATAR_IMG/width',  120);
    tmpl_set($template, 'DETAILS/AVATAR_IMG/height', 120);
  }

  if (!empty($row['awards'])){
    $tmp = explode('|', $row['awards']);
    $awards = array();
    foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
    $row['award'] = $awards;
  }
  unset($row['awards']);

  foreach($row as $k => $v)
    if (! $v )
      $row[$k] = _('k.A.');


  $row['mail_modus']    = NEW_MESSAGE;
  $row['mail_receiver'] = urlencode($row['name']);
  $row['caveID']        = $caveID;

  $timediff = getUgaAggaTimeDiff(time_fromDatetime($row['created']), time());
  $row['age'] = 18 + $timediff['year'];

  tmpl_set($template, 'DETAILS', $row);
  
  // ADDED by chris--- for rank_history
  $row['playerID'] = $playerID;

  // show player's caves
  $caves = getCaves($playerID);
  if ($caves)
    tmpl_set($template, '/DETAILS/CAVES', $caves);

  //show bodycount
  // Keinen Bodycount fuers erste.... Nebrot
  //$body_count = $row['body_count'];
  //tmpl_set($template, '/DETAILS/BODYCOUNT/body_count', $body_count);


  // show player's history
  $history = Player::getHistory($db, $playerID);
  if (sizeof($history))
    tmpl_set($template, '/DETAILS/HISTORY/ENTRY', $history);
  else
    tmpl_set($template, '/DETAILS/HISTORY/NOENTRIES/iterate', '');

  return tmpl_parse($template);
}

?>
