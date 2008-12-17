<?
/*
 * vote.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');


################################################################################


/**
 * Diese Konstante gibt an, wieviele Sekunden vergehen müssen, damit der User
 * erneut den Vote-Knopf eingeblendet bekommt.
 */
DEFINE('VOTE_INTERVAL', 60 * 60 * 24); // every day

$VOTE_CLASSES = array('gn' => 'GalaxyNewsVoteButton');


################################################################################

/**
 * This class describes a vote button.
 */

class VoteButton {
  var $id;
  var $imgSrc;
  var $link;
  var $arguments;

  function VoteButton(){
  }

  function getButtonParams(){

    return array('imgSrc' => $this->imgSrc,
                 'id'     => $this->id);
  }

  function getURL(){

    // concatenate arguments to url
    $args = array();
    foreach ($this->arguments AS $k => $v)
      $args[] = sprintf('%s=%s', $k, $v);
    $args = implode('&', $args);

    return $this->link . (strlen($args) ? ('?' . $args) : '');
  }
}

class GalaxyNewsVoteButton extends VoteButton{

  function GalaxyNewsVoteButton(){
    $this->id        = 'gn';
    $this->imgSrc    = 'http://www.galaxy-news.de/images/vote.gif';
    $this->link      = 'http://www.galaxy-news.de/';
    $this->arguments = array('page'    => 'charts',
                             'op'      => 'vote',
                             'game_id' => '39');
  }
}

################################################################################


function vote_main(){
  global $params;

  // initialize return value
  $result = '';

  // get current task
  $task = $params->POST->task;

  switch ($task){

    // show vote button
    case 'show':
    default:
      $result = vote_show();
      break;

    // vote button was activated
    case 'vote':
      $result = vote_vote();
  }

  return $result;
}


################################################################################


function vote_show(){
  global $config, $params, $VOTE_CLASSES;

  // should the buttons be shown?
  if ($params->SESSION->player->lastVote + VOTE_INTERVAL > time())
    return '';

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'vote.ihtml');

  // show each button
  foreach ($VOTE_CLASSES as $class){
    $button = new $class();
    tmpl_iterate($template, '/VOTE');
    tmpl_set($template, '/VOTE', $button->getButtonParams());
  }

  // return parsed template
  return tmpl_parse($template);
}


################################################################################


function vote_vote(){
  global $config, $params, $VOTE_CLASSES, $db;

  // already voted
  if ($params->SESSION->player->lastVote + VOTE_INTERVAL > time())
    exit();

  // get button id
  $id = strval($params->POST->id);

  // get class
  if (!isset($VOTE_CLASSES[$id]))
    exit();
  $class = $VOTE_CLASSES[$id];

  // create new object
  $button = new $class();

  // update database
  $now = time();
  $query = sprintf("UPDATE Player SET lastVote = %d WHERE playerID = %d",
                   $now, $params->SESSION->player->playerID);
  $db->query($query);
  $_SESSION['player']->lastVote = $now;

  // locate to voting site
  Header("Location: " . $button->getURL());
  exit;
}
?>