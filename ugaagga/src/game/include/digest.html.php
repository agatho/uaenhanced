<?
/*
 * digest.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/**
 * Diese Funktion stellt alle Daten für den Terminkalender zusammen und parsed
 * sie danach in das Template.
 *
 * @param  meineHoehlen  Enthält die Records aller eigenen Höhlen.
 */

function digest_getDigest($meineHoehlen){
  global $config, $params;

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'digest.ihtml');

  // get movements
  // don't show returning movements
  // don't show details for each movement
  $movements = digest_getMovements($meineHoehlen, array(), false);

  // set each movement into the template
  foreach($movements AS $move)

    // own movements
    if ($move['isOwnMovement']){
      tmpl_iterate($template, 'MOVEMENTS/MOVEMENT');
      tmpl_set($template, 'MOVEMENTS/MOVEMENT', $move);

    // adverse movements
    } else {
      tmpl_iterate($template, 'OPPONENT_MOVEMENTS/MOVEMENT');
      tmpl_set($template, 'OPPONENT_MOVEMENTS/MOVEMENT', $move);
    }

  // get artefact initiations and parse them into the template
  $initiations = digest_getInitiationDates($meineHoehlen);
  if (sizeof($initiations))
    tmpl_set($template, 'INITIATIONS/INITIATION', $initiations);

  // get building appointments and parse them
  $appointments = digest_getAppointments($meineHoehlen);
  if (sizeof($appointments))
    tmpl_set($template, 'APPOINTMENTS/APPOINTMENT', $appointments);

  // fill arrays with potential shortcuts
  $units = $buildings = $defenses = $sciences = array();
  foreach ($meineHoehlen as $value){
    $units[$value['caveID']] = array(
      'caveID'    => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'     => UNIT_BUILDER);
    $buildings[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'  => IMPROVEMENT_DETAIL);
    $defenses[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus'  => EXTERNAL_BUILDER);
    $sciences[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus' => SCIENCE);
  }

  // remove elements in these arrays, if there is such an appointment
  foreach ($appointments as $value){
    switch ($value['modus']){
      case UNIT_BUILDER:
        unset($units[$value['caveID']]);
        break;

      case IMPROVEMENT_DETAIL:
        unset($buildings[$value['caveID']]);
        break;

      case EXTERNAL_BUILDER:
        unset($defenses[$value['caveID']]);
        break;

      case SCIENCE:
        unset($sciences[$value['caveID']]);
        break;
    }
  }

  // show the remaining elements as shortcuts
  if (sizeof($units))
    tmpl_set($template, 'UNITS/UNIT',         $units);
  if (sizeof($buildings))
    tmpl_set($template, 'BUILDINGS/BUILDING', $buildings);
  if (sizeof($defenses))
    tmpl_set($template, 'DEFENSES/DEFENSE',   $defenses);
  if (sizeof($sciences))
    tmpl_set($template, 'SCIENCES/SCIENCE',   $sciences);

  return tmpl_parse($template);
}
?>