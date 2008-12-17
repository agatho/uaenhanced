<?
function digest_getDigest($meineHoehlen){

  global $params,
         $config;


  $template = @tmpl_open('templates/' . $config->template_paths[$params->SESSION->user['template']] . '/digest.ihtml');


  $movements = digest_getMovements($meineHoehlen, array(5), false);
  foreach($movements AS $move)
    if ($move['isOwnMovement']){
$java_ownmovements = TRUE;
$ownmovement[ownmovement]++;
      tmpl_iterate($template, 'MOVEMENTS/MOVEMENT');
      tmpl_set($template, 'MOVEMENTS/MOVEMENT', $move);
    } else {
$java_opponentmovements = TRUE;
$oppmovement[oppmovement]++;
      tmpl_iterate($template, 'OPPONENT_MOVEMENTS/MOVEMENT');
      tmpl_set($template, 'OPPONENT_MOVEMENTS/MOVEMENT', $move);
    }

tmpl_set($template, 'MOVEMENTS', $ownmovement);
tmpl_set($template, 'OPPONENT_MOVEMENTS', $oppmovement);

  $initiations = digest_getInitiationDates($meineHoehlen);
  if (sizeof($initiations))
    tmpl_set($template, 'INITIATIONS/INITIATION', $initiations);

  $appointments = digest_getAppointments($meineHoehlen);
  if (sizeof($appointments)){
$java_appointments = TRUE;
    tmpl_set($template, 'APPOINTMENTS/APPOINTMENT', $appointments);
  }

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
      'modus'  => DEFENSESYSTEM);
    $sciences[$value['caveID']] = array(
      'caveID' => $value['caveID'],
      'cave_name' => $value['name'],
      'modus' => SCIENCE);
  }

  foreach ($appointments as $value){
$appointmentcount[appointments]++;
    switch ($value['modus']){
      case UNIT_BUILDER:        unset($units[$value['caveID']]);
                                break;
      case IMPROVEMENT_DETAIL:  unset($buildings[$value['caveID']]);
                                break;
      case DEFENSESYSTEM:       unset($defenses[$value['caveID']]);
                                break;
      case SCIENCE:             unset($sciences[$value['caveID']]);
                                break;
    }
tmpl_set($template, 'APPOINTMENTS', $appointmentcount);
  }
  if (sizeof($units))
    tmpl_set($template, 'UNITS/UNIT',         $units);
  if (sizeof($buildings))
    tmpl_set($template, 'BUILDINGS/BUILDING', $buildings);
  if (sizeof($defenses))
    tmpl_set($template, 'DEFENSES/DEFENSE',   $defenses);
  if (sizeof($sciences))
    tmpl_set($template, 'SCIENCES/SCIENCE',   $sciences);


if ($java_ownmovements) $java[] = "'min_ownmovement', 'max_ownmovement'";
if ($java_opponentmovements) $java[] = "'min_oppmovement', 'max_oppmovement'";
if ($java_appointments) $java[] = "'min_appointments', 'max_appointments'";
//echo implode(", ",$java);

if (sizeof($java)){
  $java_str = "var sw_settings = new Array (";
  $java_str .= implode(", ",$java);
  $java_str .= ");\r\n";
  $java_str .= "showhide_settings();";
}

tmpl_set($template, array('java' => $java_str));

  return tmpl_parse($template);
}
?>
