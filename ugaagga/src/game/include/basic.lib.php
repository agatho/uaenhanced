<?
/*
 * basic.lib.php - basic routines
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/* ***************************************************************************/
/* **** GET CAVE FUNCTIONS ***** *********************************************/
/* ***************************************************************************/

/** This function returns the cave data for a given caveID
 */
function getCaveByID($caveID){
  global $db;

  $query = "SELECT *, (protection_end > NOW()+0) AS protected ".
           "FROM Cave WHERE caveID = " . intval($caveID);
  $result = $db->query($query);
  if ($result) return $result->nextRow(MYSQL_ASSOC);
  return null;
}

/** This function returns the cave data for a given caveID and playerID
 */
function getCaveSecure($caveID, $playerID){
  global $db;

  $query = "SELECT *, (protection_end > NOW()+0) AS protected FROM Cave ".
           "WHERE caveID = ". intval($caveID).
           " AND playerID = ". intval($playerID);
  $r = $db->query($query);
  if (!$r || $r->isEmpty()) return null;
  return $r;
}

/** This function returns the cave data for a given cave name
 */
function getCaveByName($caveName){
  global $db;

  $query = "SELECT * FROM Cave WHERE name = '$caveName'";
  $res =$db->query($query);
  if($res && !$res->isEmpty())
    return $res->nextRow(MYSQL_ASSOC);
  return array();
}

/** This function returns the cave data for given cave coordinates
 */
function getCaveByCoords($xCoord, $yCoord){
  global $db;

  $query = "SELECT * FROM Cave ".
           "WHERE xCoord = ". intval($xCoord) .
           " AND yCoord = ". intval($yCoord);
  $res =$db->query($query);
  if($res && !$res->isEmpty())
    return $res->nextRow(MYSQL_ASSOC);
  return array();
}

/** This function returns the cave data for all caves of a given playerID
 */
function getCaves($playerID){
  global $db;

  $query = "SELECT *, (protection_end > NOW()+0) AS protected FROM Cave ".
           "WHERE playerID = ". intval($playerID) . " ORDER BY name ASC";

  $result = $db->query($query);
  $caves = array();
  if ($result){
    while($row = $result->nextRow(MYSQL_ASSOC))
      $caves[$row['caveID']] = $row;
    return $caves;
  }
  return 0;
}

/** This function returns the cave data for all caves of a given regionID
 */
function getCavesByRegion($regionID){
  global $db;

  $query = "SELECT *, (protection_end > NOW()+0) AS protected FROM Cave ".
           "WHERE regionID = ". intval($regionID) . " ORDER BY name ASC";

  $result = $db->query($query);
  $caves = array();
  if ($result){
    while($row = $result->nextRow(MYSQL_ASSOC))
      $caves[$row['caveID']] = $row;
    return $caves;
  }
  return 0;
}

/* ***************************************************************************/
/* **** GET REGION FUNCTIONS ***** *******************************************/
/* ***************************************************************************/

/** This function returns an array with all Regions
 */
function getRegions() {
  global $db;

  $query = "SELECT * FROM Regions ORDER BY name";
  $res = $db->query($query);
  $ret = array();
  if ($res && !$res->isEmpty()) {
    while ($temp = $res->nextRow()) {
      $ret[$temp['regionID']] = $temp;
    }
  }
  return $ret;
}

/** This function returns the region data for the given region ID
 */
function getRegionByID($regionID) {
  global $db;

  $query = "SELECT * FROM Regions ".
           "WHERE regionID = ". intval($regionID);
  $res = $db->query($query);
  if ($res && !$res->isEmpty())
    return $res->nextRow();
  return array();
}

/** This function returns the region data for the given region name
 */
function getRegionByName($name) {
  global $db;

  $query = "SELECT * FROM Regions WHERE name = '$name'";
  $res = $db->query($query);
  if ($res && !$res->isEmpty())
    return $res->nextRow();
  return array();
}

/* ***************************************************************************/
/* **** GET PLAYER FUNCTIONS ***** *******************************************/
/* ***************************************************************************/

/** This function returns a players data
 */
function getPlayerByID($playerID) {
  global $db;

  $query = "SELECT * FROM Player WHERE playerID = ".intval($playerID);
  $result = $db->query($query);
  if ($result && !$result->isEmpty())
    return $result->nextRow(MYSQL_ASSOC);
  return array();
}

/** This function returns a players data
 */
function getPlayerByName($name){
  global $db;

  $query = "SELECT * FROM Player WHERE name = '".$name."'";
  $result = $db->query($query);
  if ($result && !$result->isEmpty())
    return $result->nextRow(MYSQL_ASSOC);
  return array();
}

/* ***************************************************************************/
/* **** MAP FUNCTIONS ***** **************************************************/
/* ***************************************************************************/

function getMapSize(){
  global $db;

  static $size = null;

  if ($size === null){
    $query = "SELECT min(xCoord) as minX, max(xCoord) as maxX, ".
             "min(yCoord) as minY, max(yCoord) as maxY FROM Cave";
    $res = $db->query($query);
    if ($res) $size = $res->nextRow(MYSQL_ASSOC);
  }
  return $size;
}

/* ***************************************************************************/
/* **** SQL QUERY FUNCTIONS ***** ********************************************/
/* ***************************************************************************/

/**
 * use an additional list of allowed field names to prevent
 * users from cheating the formulas
 */
function db_makeSetStatementSecure($data, $fields) {
  if (!$data) {
    return 0;
  }
  $count = 0;

  foreach($fields as $field) {
    if (array_key_exists($field, $data)) {
      $count++;
      $statement .= $field ." = '". $data[$field] ."', ";
    }
  }
  if (!$count) return 0;
  return substr($statement, 0, strlen($statement) - 2);  // remove ", "
}

/**
 * connect to login database
 */
function db_connectToLoginDB(){
  global $config;
  
  $db_login = new DB($config->DB_LOGIN_HOST, $config->DB_LOGIN_USER, $config->DB_LOGIN_PWD, $config->DB_LOGIN_NAME);
  return $db_login;
}

/* ***************************************************************************/
/* **** PHP HELP FUNCTIONS ***** *********************************************/
/* ***************************************************************************/

function unhtmlentities($string){
  static $trans_tbl;

  if (empty($trans_tbl)){
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
  }
  return strtr($string, $trans_tbl);
}

/** This function shortens a html string to a certain number of characters
 *  paying attention to character entities like &amp;
 */
function lib_shorten_html($string, $length){
  $temp = unhtmlentities($string);
  if (strlen($temp) > $length)
    return htmlentities(substr($temp, 0, $length)) . "...";
  return $string;
}
