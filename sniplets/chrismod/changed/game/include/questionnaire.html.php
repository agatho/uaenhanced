<?
/*
 * questionnaire.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

define("SILVERPERGOLD",   26);
define("COPPERPERSILVER", 13);

function questionnaire_getQuestionnaire($caveID, &$meineHoehlen){

  global $config, $params, $no_resource_flag;

  $no_resource_flag = 1;
  $msg = "";

  if (isset($params->POST->question))
    $msg = questionnaire_giveAnswers();

  $template = tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/questionnaire.ihtml');

  // show message
  if ($msg != "")
    tmpl_set($template, 'MESSAGE/message', $msg);

  // show my credits
  if ($account = questionnaire_getCredits($params->SESSION->user['questionCredits']))
    tmpl_set($template, 'ACCOUNT', $account);

  // show the questions
  $questions = questionnaire_getQuestions();
  if (sizeof($questions)){
    tmpl_set($template, 'QUESTIONS/QUESTION', $questions);
    // set params
    tmpl_set($template, 'QUESTIONS/PARAMS', array(
      array('name' => "modus", 'value' => QUESTIONNAIRE)));
  } else {
    tmpl_iterate($template, 'MESSAGE');
    tmpl_set($template, 'MESSAGE/message', "Derzeit liegen keine weiteren Fragen vor.");
  }

  // show the link to the present page
  tmpl_set($template, 'QUESTIONNAIRE_PRESENTS', QUESTIONNAIRE_PRESENTS);

  return tmpl_parse($template);
}

function questionnaire_getCredits($credits){
  $copper = $credits % COPPERPERSILVER;
  $silver = intval($credits / COPPERPERSILVER) % SILVERPERGOLD;
  $gold   = intval($credits / SILVERPERGOLD / COPPERPERSILVER);

  $result = array('credits' => $credits);
  if (!$credits) $result['COPPER'] = array('copper' => 0);
  else {
    if ($copper) $result['COPPER'] = array('copper' => $copper);
    if ($silver) $result['SILVER'] = array('silver' => $silver);
    if ($gold)   $result['GOLD']   = array('gold'   => $gold);
  }
  return $result;
}

function questionnaire_getQuestions(){
  global $db, $params;

  // get possible questions
  $query = "SELECT * FROM Questionnaire_questions WHERE expiresOn > NOW() + 0 ORDER BY questionID ASC";
  if (!($result = $db->query($query))) return array();
  if ($result->isEmpty()) return array();

  $questions   = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)){
    $questions[$row['questionID']] = $row;
  }

  // get answers
  $query = "SELECT * FROM Questionnaire_answers ".
           "WHERE playerID = {$params->SESSION->user['playerID']} ".
           "AND questionID IN (".implode(",", array_keys($questions)).") ".
           "ORDER BY questionID ASC";
  if (!($result = $db->query($query))) return array();

  while ($row = $result->nextRow(MYSQL_ASSOC))
    unset($questions[$row['questionID']]);

  $query = "SELECT * FROM Questionnaire_choices ".
           "WHERE questionID IN (".implode(",", array_keys($questions)).")".
           "ORDER BY choiceID ASC";
  if (!($result = $db->query($query))) return array();
  if ($result->isEmpty()) return array();

  while ($row = $result->nextRow(MYSQL_ASSOC)){
    if (isset($question[$row['questionID']]['CHOICE']))
      $questions[$row['questionID']]['CHOICE'] = array();
    $questions[$row['questionID']]['CHOICE'][$row['choiceID']] = $row;
  }
  return $questions;
}

function questionnaire_giveAnswers(){
  global $params, $db;

  // filter given answers
  $answers = $params->POST->question;
  foreach ($answers AS $questionID => $choiceID){
    if ($choiceID < 0) unset($answers[$questionID]);
  }

  // get valid answers
  $query = "SELECT * FROM Questionnaire_choices ".
           "WHERE questionID IN (".implode(",", array_keys($answers)).")";
  if (!($result = $db->query($query))) return "Datenbankfehler 1:" . mysql_error();
  if ($result->isEmpty()) return "Keine derartigen Fragen!";

  $valid = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)){
    if (!isset($valid[$row['questionID']]))
      $valid[$row['questionID']] = array();
    $valid[$row['questionID']][$row['choiceID']] = $row;
  }

  // validate given answers
  foreach ($answers AS $questionID => $choiceID){
    if (!isset($valid[$questionID][$choiceID]))
      unset($answers[$questionID]);
  }

  $valid = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)){
    if (!isset($valid[$row['questionID']]))
      $valid[$row['questionID']] = array();
    $valid[$row['questionID']][$row['choiceID']] = $row;
  }

  // answers now contains valid answers

  // get questions
  $questions = array();
  $query = "SELECT * FROM Questionnaire_questions ".
           "WHERE questionID IN (".implode(",", array_keys($answers)).")";
  if (!($result = $db->query($query))) return "Datenbankfehler 2:" . mysql_error();
  if ($result->isEmpty()) return "Keine derartigen Fragen!";
  while ($row = $result->nextRow(MYSQL_ASSOC)){
    $questions[$row['questionID']] = $row;
  }

  // insert into db and reward afterwards
  $rewards = 0;
  foreach ($answers AS $questionID => $choiceID){
    $query = "INSERT INTO Questionnaire_answers ".
             "(playerID, questionID, choiceID) ".
             "VALUES ('{$params->SESSION->user['playerID']}', ".
             "'$questionID', '$choiceID')";
    if (!($result = $db->query($query))) return "Datenbankfehler 3:" . mysql_error();
    if ($db->affected_rows() != 1) continue;
    $rewards += $questions[$questionID]['credits'];
  }

  // now update playerstats
  if (!questionnaire_addCredits($rewards))
    return "Probleme beim Eintragen der Bonuspunkte.";

  return "";
}

function questionnaire_addCredits($credits){
  global $params, $db;

  $query = "UPDATE Player SET questionCredits = questionCredits + $credits WHERE playerID = " .
           $params->SESSION->user['playerID'] ." AND questionCredits + $credits >= 0";
  if (!$db->query($query)) die("questionnaire_addCredits: " . mysql_error());

  if ($db->affected_rows() != 1) return false;

  $params->SESSION->user['questionCredits'] += $credits;
  $_SESSION['user']['questionCredits'] = $params->SESSION->user['questionCredits'];
  return true;
}

function questionnaire_presents($caveID, &$meineHoehlen){
  global $params, $config, $db, $defenseSystemTypeList, $unitTypeList, $resourceTypeList, $no_resource_flag;

  $no_resource_flag = 1;

  $template = tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/questionnaire_presents.ihtml');

  if (isset($params->POST->presentID) && intval($params->POST->presentID) > 0)
    $msg = questionnaire_getPresent($caveID, $meineHoehlen, $params->POST->presentID);

  // show message
  if ($msg != "")
    tmpl_set($template, 'MESSAGE/message', $msg);

  // show my credits
  if ($account = questionnaire_getCredits($params->SESSION->user['questionCredits']))
    tmpl_set($template, 'ACCOUNT', $account);


  $query = "SELECT * FROM `Questionnaire_presents` ORDER BY presentID ASC";
  if (!($result = $db->query($query))) return "Datenbankfehler: " . mysql_error();

  $presents = array();
  while ($row = $result->nextRow(MYSQL_ASSOC)){

    if (!questionnaire_timeIsRight($row))
      continue;

    $row += questionnaire_getCredits($row['credits']);

    $externals = array();
    foreach ($defenseSystemTypeList AS $external)
      if ($row[$external->dbFieldName] > 0)
        $externals[] = array('amount' => $row[$external->dbFieldName],
                             'name'   => $external->name);
    if (sizeof($externals)) $row['EXTERNAL'] = $externals;

    $resources = array();
    foreach ($resourceTypeList AS $resource)
      if ($row[$resource->dbFieldName] > 0)
        $resources[] = array('amount' => $row[$resource->dbFieldName],
                             'name'   => $resource->name);
    if (sizeof($resources)) $row['RESOURCE'] = $resources;

    $units = array();
    foreach ($unitTypeList AS $unit)
      if ($row[$unit->dbFieldName] > 0)
        $units[] = array('amount' => $row[$unit->dbFieldName],
                         'name'   => $unit->name);
    if (sizeof($units)) $row['UNIT'] = $units;

    $presents[] = $row;
  }
  if (sizeof($presents)){
    tmpl_set($template, 'PRESENTS/PRESENT', $presents);
    tmpl_set($template, 'PRESENTS/PARAMS', array(
      array('name' => "modus", 'value' => QUESTIONNAIRE_PRESENTS)));
  }
  else
    tmpl_set($template, 'NO_PRESENT/dummy', "");

  // show the link to the questions page
  tmpl_set($template, 'QUESTIONNAIRE', QUESTIONNAIRE);

  return tmpl_parse($template);
}

function questionnaire_timeIsRight($row){
  global $db;

  static $now = null;

  // get current uga agga time
  if ($now === null)
    $now = getUgaAggaTime(time());

  $parsed_row = array();
  questionnaire_parseNumericElement($row['hour'], $parsed_row['hour'], HOURS_PER_DAY);
  questionnaire_parseNumericElement($row['day_of_month'], $parsed_row['day_of_month'], DAYS_PER_MONTH);
  questionnaire_parseNumericElement($row['month'], $parsed_row['month'], MONTHS_PER_YEAR);

  questionnaire_parseCharElement($row['phase_of_moon'], $parsed_row['phase_of_moon'], array("a", "n", "z", "v"));

  return $parsed_row['hour'][$now['hour']] &&
         $parsed_row['day_of_month'][$now['day']] &&
         $parsed_row['month'][$now['month']] &&
         $parsed_row['phase_of_moon'][$now['moon']];
}

function questionnaire_parseNumericElement($element, &$targetArray, $numberOfElements){
  $subelements = explode(",", $element);
  for ($i = 0; $i < $numberOfElements; $i++)
    $targetArray[$i] = ($subelements[0] == "*");

  for ($i = 0; $i < count($subelements); $i++)
    if (preg_match("~^(\\*|([0-9]{1,2})(-([0-9]{1,2}))?)(/([0-9]{1,2}))?$~",
        $subelements[$i],  $matches)){

      if ($matches[1] == "*"){
        $matches[2] = 0; // from
        $matches[4] = $numberOfElements; //to
      } else if ($matches[4] == ""){
        $matches[4] = $matches[2];
      }
      if ($matches[5][0] != "/")
        $matches[6] = 1; // step
      for ($j = questionnaire_lTrimZeros($matches[2]);
           $j <= questionnaire_lTrimZeros($matches[4]);
           $j += questionnaire_lTrimZeros($matches[6]))
        $targetArray[$j] = TRUE;
    }
}

function questionnaire_parseCharElement($element, &$targetArray, $allowedElements){

  $subelements = explode(",", $element);
  foreach ($allowedElements AS $character)
    $targetArray[$character] = ($subelements[0] == "*");

  // list
  foreach ($subelements AS $character)
    if (in_array($character, $allowedElements))
      $targetArray[$character] = true;
}

function questionnaire_lTrimZeros($number){
  while ($number[0]=='0') $number = substr($number,1);
  return $number;
}

function questionnaire_getPresent($caveID, &$meineHoehlen, $presentID){
  global $config, $db, $params, $defenseSystemTypeList, $resourceTypeList, $unitTypeList;

  $query = "SELECT * FROM `Questionnaire_presents` WHERE presentID = " . intval($presentID);
  if (!($result = $db->query($query))) return "Datenbankfehler: " . mysql_error();

  $row = $result->nextRow(MYSQL_ASSOC);

  if (!questionnaire_timeIsRight($row))
    return "&quot;Dieses Geschenk kann ich euch nicht anbieten, H&auml;uptling!&quot;";

  // genügend Schnecken?
  $myaccount = questionnaire_getCredits($params->SESSION->user['questionCredits']);
  $price     = questionnaire_getCredits($row['credits']);

/*
  if ($myaccount['credits']          < $price['credits'] ||
      $myaccount['COPPER']['copper'] < $price['COPPER']['copper'] ||
      $myaccount['SILVER']['silver'] < $price['SILVER']['silver'] ||
      $myaccount['GOLD']['gold']     < $price['GOLD']['gold'])
    return "&quot;Ihr habt nicht die passenden Schnecken, H&auml;uptling!&quot;";
*/

  if ($myaccount['credits'] < $price['credits'])
    return "&quot;Ihr habt nicht die passenden Schnecken, H&auml;uptling!&quot;";

  // Preis abziehen
  if (!questionnaire_addCredits(-$row['credits']))
    return "&quot;Ich bin mit dem Schnecken abz&auml;hlen durcheinander ".
           "gekommen, H&auml;uptling! Versucht es noch einmal!&quot;";

  // Geschenk überreichen

  $presents = array();
  $caveData = $meineHoehlen[$caveID];
  foreach ($defenseSystemTypeList AS $external)
    if ($row[$external->dbFieldName] > 0){
      $dbField    = $external->dbFieldName;
      $maxLevel   = round(eval('return '.formula_parseToPHP("{$external->maxLevel};", '$caveData')));
      $presents[] = "$dbField = LEAST(GREATEST($maxLevel, $dbField), $dbField + ".$row[$external->dbFieldName].")";
    }

  foreach ($resourceTypeList AS $resource)
    if ($row[$resource->dbFieldName] > 0){
      $dbField    = $resource->dbFieldName;
      $maxLevel   = round(eval('return '.formula_parseToPHP("{$resource->maxLevel};", '$caveData')));
      $presents[] = "$dbField = LEAST($maxLevel, $dbField + ".$row[$resource->dbFieldName].")";
    }

  foreach ($unitTypeList AS $unit)
    if ($row[$unit->dbFieldName] > 0){
      $dbField    = $unit->dbFieldName;
      $presents[] = "$dbField = $dbField + " . $row[$unit->dbFieldName];
    }

  if (sizeof($presents)){
    // UPDATE Cave
    $query = "UPDATE Cave SET " . implode(", ", $presents) .
             " WHERE caveID = $caveID AND playerID = ".
             $params->SESSION->user['playerID'];
    $update_result = $db->query($query);
    if (!$update_result)
      return "Datenbankfehler: " . mysql_error();

    // UPDATE Questionnaire_presents
    $query = "UPDATE Questionnaire_presents SET use_count = use_count + 1 ".
             "WHERE presentID = " . $presentID;
    $update_result = $db->query($query);
    if (!$update_result)
      return "Datenbankfehler: " . mysql_error();
    if ($db->affected_rows() != 1)
      return "Probleme beim UPDATE des Geschenks";

    // Höhle auffrischen
    $r = getCaveSecure($caveID, $params->SESSION->user['playerID']);
    if ($r->isEmpty()) page_dberror();
    $meineHoehlen[$caveID] = $r->nextRow();

    return "Eure Geschenke sind nun in eurer Siedlung!";
  }

  return "Danke f&uuml;r die Schnecken!";
}
?>
