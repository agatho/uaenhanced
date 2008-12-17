<?
/*
 * ranking.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define("RANKING_ROWS", 20);

function ranking_getContent($caveID, $offset){
  global $no_resource_flag, $config, $params;

  $no_resource_flag = 1;

  $religions = ranking_getReligiousDistribution();

  if($religions['uga']+$religions['agga']!=0){
    $ugapercent = round($religions['uga']/($religions['uga'] + $religions['agga'])*100);
    $aggapercent = round($religions['agga']/($religions['uga'] + $religions['agga'])*100);
  }else{
    $ugapercent = 0;
    $aggapercent = 0;
  }
  $row = ranking_getRowsByOffset($caveID, $offset);

  $up   = array('link' => "?modus=" . RANKING . "&offset=" . ($offset - RANKING_ROWS));
  $down = array('link' => "?modus=" . RANKING . "&offset=" . ($offset + RANKING_ROWS));

  $hidden = array(array('name' => "modus", 'value' => RANKING));


  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'ranking.ihtml');

  tmpl_set($template, array('modus'      => RANKING_TRIBE,
                            'UP'         => $up,
                            'DOWN'       => $down,
                            'HIDDEN'     => $hidden,
                            'RELIGIOUS_DISTRIBUTION' => array('ugapercent' => $ugapercent,
                                                              'aggapercent' => $aggapercent)
                            ));

  for ($i = 0; $i < sizeof($row); ++$i){
    tmpl_iterate($template, 'ROWS');
    if ($i % 2) tmpl_set($template, 'ROWS/ROW_ALTERNATE', $row[$i]);
    else tmpl_set($template, 'ROWS/ROW', $row[$i]);
  }

  return tmpl_parse($template);
}

function rankingTribe_getContent($caveID, $offset){
  global $no_resource_flag, $config, $params;

  $no_resource_flag = 1;

  $row = rankingTribe_getRowsByOffset($caveID, $offset);

  $up   = array('link' => "?modus=" . RANKING_TRIBE . "&offset=" . ($offset - RANKING_ROWS));
  $down = array('link' => "?modus=" . RANKING_TRIBE . "&offset=" . ($offset + RANKING_ROWS));

  $hidden = array(array('name' => "modus", 'value' => RANKING_TRIBE));


  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'rankingTribe.ihtml');

  for ($i = 0; $i < sizeof($row); ++$i){
    tmpl_iterate($template, 'ROWS');
    if ($i % 2) tmpl_set($template, 'ROWS/ROW_ALTERNATE', $row[$i]);
    else tmpl_set($template, 'ROWS/ROW', $row[$i]);
  }

  tmpl_set($template, array('modus'  => RANKING,
                            'UP'     => $up,
                            'DOWN'   => $down,
                            'HIDDEN' => $hidden));

  return tmpl_parse($template);
}
?>
