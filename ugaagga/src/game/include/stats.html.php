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


function stats_stats($playerID){
  global $params, $config, $no_resource_flag;
  $no_resource_flag = 1;

$stats = stats_getStats();

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'stats.ihtml');

  tmpl_set($template, array('runden_start'     => $stats['runden_start'],
			    'uga_time'		=> $stats['uga_time'],
			    'kampfberichte'	=> $stats['kampfberichte'],
			    'kbs_durchschnitt'	=> round($stats['kampfberichte']/$stats['spieler'],2),
			    'spioberichte'	=> $stats['spioberichte'],
			    'spio_durchschnitt'	=> round($stats['spioberichte']/$stats['spieler'],2),
			    'takeover'		=> $stats['takeover'],
			    'spieler'		=> $stats['spieler'],
			    'clans'		=> $stats['clans'],
			    'player_clans'	=> $stats['player_clans'],
			    'player_noclan'	=> $stats['player_noclan'],
			    'player_noreligion' => $stats['player_noreligion'],
			    'units'		=> $stats['units'],
			    'units_durchschnitt' => round($stats['units']/$stats['spieler'],2),
			    'units_moving'	=> $stats['units_moving'],
			    'units_move_durchschnitt' => round($stats['units_moving']/$stats['spieler'],2),
			    'messages'		=> $stats['messages'],
			    'messages_durchschnitt' => round($stats['messages']/$stats['spieler'],2),
			    'ticker_status'	=> $stats['ticker_status'],
			    'caves'		=> $stats['caves'],
			    'caves_durchschnitt' => round($stats['caves']/$stats['spieler'],2),
			    'caves_free'	=> $stats['caves_free'],
			    'caves_all'		=> $stats['caves_all'],
			    'caves_prozent'	=> round($stats['caves']/$stats['caves_all']*100,2),
			    'caves_free_prozent' => round($stats['caves_free']/$stats['caves_all']*100,2),
			    'player_religion_agga' => $stats['player_religion_agga'],
			    'player_religion_uga' => $stats['player_religion_uga'],
			    'agga_prozent'	=> round($stats['player_religion_agga']/$stats['spieler']*100,2),
			    'uga_prozent'	=> round($stats['player_religion_uga']/$stats['spieler']*100,2),
			    'noreligion_prozent' => round($stats['player_noreligion']/$stats['spieler']*100,2),
			    'player_clans_prozent' => round($stats['player_clans']/$stats['spieler']*100,2),
			    'player_noclan_prozent' => round($stats['player_noclan']/$stats['spieler']*100,2),
			    'questions'		=> $stats['questions'],
			    'user_active'	=> $stats['user_active'],
			    'user_active_prozent' => round($stats['user_active']/$stats['spieler']*100,2),
			    'one_cave'		=> $stats['one_cave'],
			    'one_cave_prozent'	=> round($stats['one_cave']/$stats['spieler']*100,2),
			    '4_cave'		=> $stats['4_cave'],
			    '4_cave_prozent'	=> round($stats['4_cave']/$stats['spieler']*100,2),
			    'artefact'		=> $stats['artefact'],
			    'artefact_durchschnitt' => round($stats['artefact']/$stats['spieler'],2),
			    'max_active'	=> $stats['max_active'],
			    'max_date'		=> $stats['max_date'],
			    'player_religion_hex' => $stats['player_religion_hex'],
                            'hex_prozent'	=> round($stats['player_religion_hex']/$stats['spieler']*100,2),
			    'wunder'		=> $stats['wunder'],
			    'wunder_durchschnitt'	=> round($stats['wunder']/$stats['spieler'],2),
			    'urlauber'		=> $stats['urlauber'],
			    'urlauber_prozent'	=> round($stats['urlauber']/$stats['spieler']*100,2)));

  return tmpl_parse($template);


}


?>