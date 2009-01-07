<?
/*
 * stats.html.php - stats
 * Copyright (c) 2004  chris---
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function stats_stats($playerID){
  global 
      $params, 
	  $config, 
	  $no_resource_flag;
	  
  $no_resource_flag = 1;

$stats = stats_getStats();

$statistik = array('runden_start'     => $stats[0]['runden_start'],
			    'uga_time'		=> $stats[0]['uga_time'],
			    'kampfberichte'	=> $stats[0]['kampfberichte'],
			    'kbs_durchschnitt'	=> round($stats[0]['kampfberichte']/$stats[0]['spieler'],2),
			    'spioberichte'	=> $stats[0]['spioberichte'],
			    'spio_durchschnitt'	=> round($stats[0]['spioberichte']/$stats[0]['spieler'],2),
			    'takeover'		=> $stats[0]['takeover'],
			    'spieler'		=> $stats[0]['spieler'],
			    'clans'		=> $stats[0]['clans'],
			    'player_clans'	=> $stats[0]['player_clans'],
			    'player_noclan'	=> $stats[0]['player_noclan'],
			    'player_noreligion' => $stats[0]['player_noreligion'],
			    'units'		=> $stats[0]['units'],
			    'units_durchschnitt' => round($stats[0]['units']/$stats[0]['spieler'],2),
			    'units_moving'	=> $stats[0]['units_moving'],
			    'units_move_durchschnitt' => round($stats[0]['units_moving']/$stats[0]['spieler'],2),
			    'messages'		=> $stats[0]['messages'],
			    'messages_durchschnitt' => round($stats[0]['messages']/$stats[0]['spieler'],2),
			    'ticker_status'	=> $stats[0]['ticker_status'],
			    'caves'		=> $stats[0]['caves'],
			    'caves_durchschnitt' => round($stats[0]['caves']/$stats[0]['spieler'],2),
			    'caves_free'	=> $stats[0]['caves_free'],
			    'caves_all'		=> $stats[0]['caves_all'],
			    'caves_prozent'	=> round($stats[0]['caves']/$stats[0]['caves_all']*100,2),
			    'caves_free_prozent' => round($stats[0]['caves_free']/$stats[0]['caves_all']*100,2),
			    'player_religion_agga' => $stats[0]['player_religion_agga'],
			    'player_religion_uga' => $stats[0]['player_religion_uga'],
			    'agga_prozent'	=> round($stats[0]['player_religion_agga']/$stats[0]['spieler']*100,2),
			    'uga_prozent'	=> round($stats[0]['player_religion_uga']/$stats[0]['spieler']*100,2),
			    'noreligion_prozent' => round($stats[0]['player_noreligion']/$stats[0]['spieler']*100,2),
			    'player_clans_prozent' => round($stats[0]['player_clans']/$stats[0]['spieler']*100,2),
			    'player_noclan_prozent' => round($stats[0]['player_noclan']/$stats[0]['spieler']*100,2),
			    'questions'		=> $stats[0]['questions'],
			    'user_active'	=> $stats[0]['user_active'],
			    'user_active_prozent' => round($stats[0]['user_active']/$stats[0]['spieler']*100,2),
			    'one_cave'		=> $stats[0]['one_cave'],
			    'one_cave_prozent'	=> round($stats[0]['one_cave']/$stats[0]['spieler']*100,2),
			    '4_cave'		=> $stats[0]['4_cave'],
			    '4_cave_prozent'	=> round($stats[0]['4_cave']/$stats[0]['spieler']*100,2),
			    'artefact'		=> $stats[0]['artefact'],
			    'artefact_durchschnitt' => round($stats[0]['artefact']/$stats[0]['spieler'],2),
			    'max_active'	=> $stats[0]['max_active'],
			    'max_date'		=> $stats[0]['max_date'],
			    'player_religion_hex' => $stats[0]['player_religion_hex'],
                            'hex_prozent'	=> round($stats[0]['player_religion_hex']/$stats[0]['spieler']*100,2),
			    'wunder'		=> $stats[0]['wunder'],
			    'wunder_durchschnitt'	=> round($stats[0]['wunder']/$stats[0]['spieler'],2),
			    'urlauber'		=> $stats[0]['urlauber'],
			    'urlauber_prozent'	=> round($stats[0]['urlauber']/$stats[0]['spieler']*100,2)) ;

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'stats.ihtml');

  tmpl_set($template, '/', $statistik);
				

  return tmpl_parse($template);


}


?>