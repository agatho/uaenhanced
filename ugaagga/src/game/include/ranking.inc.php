<?
/*
 * ranking.inc.php -
 * Copyright (c) 2004  OGP-Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function ranking_checkOffset($playerID, $offset){

  global $db;

  // get numRows of Ranking
  $query = "SELECT COUNT(*) AS num_rows FROM Ranking";

  $db_result = $db->query($query);
  if (!$db_result){
    return -1;
  }
  if (($row = $db_result->nextrow(MYSQL_ASSOC)) != FALSE){
    $num_rows = $row['num_rows'];
  } else {
    // something is real wrong, just return '-1' as error message
    return -1;
  }

  if (strval(intval($offset)) != $offset){
    // $offset is NOT a line number

    if (!isset($offset)){
      // $offset is not set yet, show the actual player in the middle of the list
      $query = "SELECT rank FROM Ranking WHERE playerID = " . $playerID;

    } else {
      // $offset is a player name
      $query = 'SELECT rank FROM Ranking WHERE name LIKE "' . $offset . '"';
    }

    $db_result = $db->query($query);
    if (!$db_result){
      return -1;
    }

    // if at least one record exists, get 'rank'
    if ($row = $db_result->nextrow()){
      $offset = $row['rank'] - (!isset($offset) ? floor(RANKING_ROWS/2) : 0);
    } else {
      $offset = 1;
    }
  }

  // the $offset is possibly out of bounds so make it right
  if ($offset < 1)
    $offset = 1;
  if ($offset > $num_rows)
    $offset = $num_rows;

  return $offset;
}

function rankingTribe_checkOffset($offset){

  global $db, $params;

  // get numRows of Ranking
  $query = "SELECT COUNT(*) AS num_rows FROM RankingTribe";

  $db_result = $db->query($query);
  if (!$db_result){
    return -1;
  }
  if (($row = $db_result->nextrow(MYSQL_ASSOC)) != FALSE){
    $num_rows = $row['num_rows'];
  } else {
    // something is real wrong, just return '-1' as error message
    return -1;
  }

  //if (!isset($offset))
  //    $offset = 1;

  if (strval(intval($offset)) != $offset){

    if (!isset($offset) && $params->SESSION->player->tribe != ''){
      // $offset is not set yet, show the actual player's tribe in the middle of the list
      $query = 'SELECT rank FROM RankingTribe WHERE tribe = "' . $params->SESSION->player->tribe . '"';
    } else {
      // $offset is a tribe name
      $query = 'SELECT rank FROM RankingTribe WHERE tribe LIKE "' . $offset . '"';
    }

    $db_result = $db->query($query);
    if (!$db_result){
      return -1;
    }

    // if at least one record exists, get 'rank'
    if ($row = $db_result->nextrow()) {
      $offset = $row['rank'] - (!isset($offset) ? floor(RANKING_ROWS/2) : 0);
    } else {
      $offset = 1;
    }
  }

  // the $offset is possibly out of bounds so make it right
  if ($offset < 1)
    $offset = 1;
  if ($offset > $num_rows)
    $offset = $num_rows;

  return $offset;
}


function ranking_getRowsByOffset($caveID, $offset){

  global $db;

  $query = "SELECT r.rank, r.playerID AS link, r.name, r.average AS points, ".
           "r.religion, p.tribe, r.caves, p.awards, ".
           "(IF(ISNULL(t.leaderID),0,r.playerID = t.leaderID)) AS is_leader ".
           "FROM Ranking r LEFT JOIN Player p ON r.playerID = p.playerID ".
           "LEFT JOIN Tribe t ON p.tribe = t.tag ".
           "ORDER BY rank ASC LIMIT " . ($offset - 1) . ", " . RANKING_ROWS;

  $db_result = $db->query($query);
  if (!$db_result){
    return array();
  }

  $result = array();
  while ($row = $db_result->nextrow(MYSQL_ASSOC)){
    if (!empty($row['awards'])){
      $tmp = explode('|', $row['awards']);
      $awards = array();
      foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      $row['award'] = $awards;
    }
    $row['link']      = "?modus=" . PLAYER_DETAIL . "&detailID=" . $row['link'];
    $row['tribelink'] = "?modus=" . TRIBE_DETAIL  . "&tribe="    . urlencode(unhtmlentities($row['tribe']));
    $result[] = $row;
  }

  return $result;
}

function rankingTribe_getRowsByOffset($caveID, $offset){

  global $db;

  $query = "SELECT r.*, r.playerAverage AS average, t.awards ".
           "FROM RankingTribe r LEFT JOIN Tribe t ON r.tribe = t.tag ".
           "ORDER BY r.rank ASC LIMIT " . ($offset - 1) . ", " . RANKING_ROWS;

  $db_result = $db->query($query);
  if (!$db_result){
    return array();
  }

  $result = array();
  while ($row = $db_result->nextrow(MYSQL_ASSOC)){
    if (!empty($row['awards'])){
      $tmp = explode('|', $row['awards']);
      $awards = array();
      foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      $row['award'] = $awards;
    }
    $row['link'] = "?modus=" . TRIBE_DETAIL . "&tribe=" . urlencode(unhtmlentities($row['tribe']));
    $result[] = $row;
  }

  return $result;
}

function ranking_getReligiousDistribution(){

  global $db;

  $query = "SELECT religion, count(religion) as sum FROM Ranking WHERE religion NOT LIKE 'none' GROUP BY religion";

  $db_result = $db->query($query);
  if (!$db_result){
    return array();
  }

  $result = array();
  while ($row = $db_result->nextrow(MYSQL_ASSOC)){
    $result[$row['religion']] = $row['sum'];
  }
  return $result;
}
?>
