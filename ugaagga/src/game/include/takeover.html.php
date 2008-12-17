<?
/*
 * takeover.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('formula_parser.inc.php');

################################################################################


/**
 * This function delegates the task at issue to the respective function.
 */

function takeover_main($caveID, $meineHoehlen){
  global $params;

  // initialize return value
  $result = '';

  // get current task
  $task = $params->POST->task;

  switch ($task){

    // show main page
    default:
      $result = takeover_show($caveID, $meineHoehlen);
      break;

    // show change confirmation page
    case 'confirm_change':
      $result = takeover_confirmChange($caveID, $meineHoehlen);
      break;

    // change cave page
    case 'change':
      $result = takeover_change($caveID, $meineHoehlen);
      break;

    // show withdrawal confirmation page
    case 'confirm_withdrawal':
      $result = takeover_confirmWithdrawal($caveID, $meineHoehlen);
      break;

    // withdrawal page
    case 'withdrawal':
      $result = takeover_withdrawal($caveID, $meineHoehlen);
      break;
  }

  return $result;
}


################################################################################


/**
 * This function shows the general information page
 */

function takeover_show($caveID, $meineHoehlen, $feedback = NULL){
  global $config, $params,
         $resourceTypeList,
         $TAKEOVERMAXPOPULARITYPOINTS, $TAKEOVERMINRESOURCEVALUE;

  // get params
  $playerID = $params->SESSION->player->playerID;
  $maxcaves = $params->SESSION->player->takeover_max_caves;

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'takeover.ihtml');

  // show feedback
  if ($feedback !== NULL)
    tmpl_set($template, '/MESSAGE/message', $feedback);

  // don't show page, if maxcaves reached
  if (sizeof($meineHoehlen) >= $maxcaves){
    tmpl_set($template, '/MESSAGE/message', sprintf(_('Sie haben bereits die maximale Anzahl von %d Höhlen erreicht.'), $maxcaves));

  // prepare page
  } else {

    // collect resource ratings
    $ratings = array();
    foreach ($resourceTypeList AS $resource)
      if ($resource->takeoverValue)
        $ratings[] = array('dbFieldName' => $resource->dbFieldName,
                           'name'        => $resource->name,
                           'value'       => $resource->takeoverValue);

    // fill page
    tmpl_set($template, '/TAKEOVER',
             array('beliebtheit'   => $TAKEOVERMAXPOPULARITYPOINTS,
                   'mindestgebot'  => $TAKEOVERMINRESOURCEVALUE,
                   'maxcaves'      => $maxcaves,
                   'targetXCoord'  => $params->POST->targetXCoord,
                   'targetYCoord'  => $params->POST->targetYCoord,
                   'RESOURCEVALUE' => $ratings));

    // get bidding
    $bidding = takeover_getBidding();
    if ($bidding){
      tmpl_set($template, '/TAKEOVER/CHOSEN', $bidding);
      tmpl_set($template, '/TAKEOVER', array('currentXCoord' => $bidding['xCoord'],
                                             'currentYCoord' => $bidding['yCoord']));
    }
  }
  return tmpl_parse($template);
}


################################################################################


/**
 * This function shows a page where one can confirm the change of one's cave
 */

function takeover_confirmChange($caveID, $meineHoehlen){
  global $config, $params;

  // get params
  $xCoord        = $params->POST->xCoord;
  $yCoord        = $params->POST->yCoord;
  $currentXCoord = $params->POST->currentXCoord;
  $currentYCoord = $params->POST->currentYCoord;

  // only one ordinate
  if ($xCoord == "" || $yCoord == "" )
    return takeover_show($caveID, $meineHoehlen, _('Zum Wechseln mußt du sowohl die x- als auch die y-Koordinate angeben.'));

  // already bidding on this cave
  else if ($currentXCoord == $xCoord && $currentYCoord == $yCoord)
    return takeover_show($caveID, $meineHoehlen, _('Du bietest bereits für diese Höhle.'));

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'takeover_change.ihtml');

  // generate check value
  $_SESSION['check'] = uniqid('change');

  // now bidding on another one
  tmpl_set($template, array('xCoord' => $xCoord, 'yCoord' => $yCoord,
                            'check' => $_SESSION['check']));

  return tmpl_parse($template);
}


################################################################################


/**
 * This function changes the cave.
 */

function takeover_change($caveID, $meineHoehlen){
  global $params;

  // get check
  $check = $params->POST->check;

  // get coordinates
  $xCoord = $params->POST->xCoord;
  $yCoord = $params->POST->yCoord;

  // verify $check
  if ($check != $_SESSION['check'])
    return takeover_show($caveID, $meineHoehlen, _('Sie können nicht für diese Höhle bieten. Wählen sie eine freie Höhle.'));

  // not enough informations
  if ($xCoord == "" || $yCoord == "")
    return takeover_show($caveID, $meineHoehlen, _('Sie können nicht für diese Höhle bieten. Wählen sie eine freie Höhle.'));

  // cave change successfull
  if (changeCaveIfReasonable($xCoord, $yCoord))
    return takeover_show($caveID, $meineHoehlen,
                         sprintf(_('Sie bieten nun für die Höhle in (%d|%d).'), $xCoord, $yCoord));

  return takeover_show($caveID, $meineHoehlen, _('Sie können nicht für diese Höhle bieten. Wählen sie eine freie Höhle.'));
}


################################################################################


/**
 * This function shows a page where one can confirm one's withdrawal
 */

function takeover_confirmWithdrawal($caveID, $meineHoehlen){
  global $config, $params;

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'takeover_withdrawal.ihtml');

  // generate check value
  $_SESSION['check'] = uniqid('withdrawal');
  tmpl_set($template, array('check' => $_SESSION['check']));

  return tmpl_parse($template);
}


################################################################################


/**
 * This function let the player withdraw his bidding.
 */

function takeover_withdrawal($caveID, $meineHoehlen){
  global $params, $db;

  // get check
  $check = $params->POST->check;

  // verify $check
  if ($check != $_SESSION['check'])
    return takeover_show($caveID, $meineHoehlen, _('Sie konnten ihr Angebot nicht zurückziehen.'));

  // withdraw

  // prepare query
  $query = sprintf("DELETE FROM Cave_takeover WHERE playerID = %d",
                   $params->SESSION->player->playerID);
  // execute query
  $result = $db->query($query);
  // error
  if (!$result)
    return takeover_show($caveID, $meineHoehlen, _('Sie konnten ihr Angebot nicht zurückziehen.'));

  return takeover_show($caveID, $meineHoehlen, _('Sie haben ihr Angebot zurückgezogen.'));
}


################################################################################
# HELP FUNCTIONS                                                               #
################################################################################


/**
 *
 */

function takeover_getBidding(){
  global $db, $params, $resourceTypeList;

  // prepare query
  $query = sprintf('SELECT * FROM Cave_takeover WHERE playerID = %d',
                   $params->SESSION->player->playerID);

  // execute query
  $result = $db->query($query);

  // return NULL on error or if recordSet is empty, as there is no bidding
  if (!$result || $result->isEmpty())
    return NULL;

  // fetch row
  $row = $result->nextrow();

  // fill return value
  $bidding = array('caveID'      => $row['caveID'],
                   'xCoord'      => $row['xCoord'],
                   'yCoord'      => $row['yCoord'],
                   'status'      => $row['status'],
                   'caveName'    => $row['name'],
                   'uh_caveName' => unhtmlentities($row['name']));

  // get own status
  $bidding += takeover_getStatusPic($row['status']);

  // get sent resources
  $sum = 0;
  $resources = array();
  foreach ($resourceTypeList as $resource){
    $amount = $row[$resource->dbFieldName];
    if ($amount > 0){
      $resources[] = array('name'  => $resource->name, 'value' => $amount);
      $sum += $amount * $resource->takeoverValue;
    }
  }

  // merge $resources with bidding
  if (sizeof($resources)){
    $bidding['RESOURCE'] = $resources;
    $bidding['SUM'] = array('sum' => $sum);
  } else {
    $bidding['NONE'] = array('iterate' => '');
  }

  // get other bidders
  $bidders = array();
  $query = sprintf('SELECT p.name, p.playerID, ct.status '.
                   'FROM Cave_takeover ct, Player p '.
                   'WHERE caveID = %d AND ct.playerID = p.playerID '.
                   'AND ct.playerID != %d',
                   $row['caveID'], $params->SESSION->player->playerID);
  $result = $db->query($query);
  if ($result)
    while($row = $result->nextrow()){
      $temp  = array('playername' => $row['name'],
                     'playerID'   => $row['playerID']);
      $temp += takeover_getStatusPic($row['status']);
      $bidders[] = $temp;
    }

  // merge $bidders with bidding
  if (sizeof($bidders)){
    $bidding['BIDDER'] = $bidders;
  } else {
    $bidding['NOONE'] = array('iterate' => '');
  }

  return $bidding;
}


################################################################################


/**
 *
 */

function takeover_getStatusPic($status){
  return array('status-img' => 'star' . substr($status + 1000, 1), 'status-txt' => $status);
}


################################################################################


/**
 * check:
 * 1. this cave is a takeoverable cave
 * 2. neuen Eintrag in Cave_takeover (alten ueberschreiben)
 */

function changeCaveIfReasonable($xCoord, $yCoord){
  global $db, $resourceTypeList, $params;

  // prepare return value
  $result = FALSE;

  // ist diese Hoehle ueberhaupt frei? Welche caveID hat diese?
  $sql = sprintf('SELECT * FROM Cave WHERE playerID = 0 AND takeoverable = 1 '.
                 'AND xCoord = %d AND yCoord = %d', $xCoord, $yCoord);
  $dbresult = $db->query($sql);

  if ($dbresult)
    // this cave has no owner and may be taken over
    if (!$dbresult->isEmpty()){

        $row = $dbresult->nextrow();

        // prepare statement
        $colNames = array();
        $values = array();
        foreach($resourceTypeList AS $resource){
            $colNames[] = $resource->dbFieldName;
            $values[]   = "0";
        }
        $colNames = implode(",", $colNames);
        $values   = implode(",", $values);

        $query = sprintf("REPLACE INTO Cave_takeover ".
                         "(playerID, caveID, xCoord, yCoord, name, %s, status) ".
                         "VALUES (%d, %d, %d, %d, '%s', %s, 0)",
                         $colNames,
                         $params->SESSION->player->playerID, $row['caveID'],
                         $row['xCoord'], $row['yCoord'], $row['name'], $values);
        if ($dbresult = $db->query($query))
          $result = TRUE;
    }

  return $result;
}


################################################################################
?>
