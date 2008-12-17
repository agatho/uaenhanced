<?
/*
 * CaveBookmarks.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

DEFINE('CAVEBOOKMARKS_NOERROR',             0x00);

DEFINE('CAVEBOOKMARKS_ERROR_NOSUCHCAVE',    0x01);
DEFINE('CAVEBOOKMARKS_ERROR_MAXREACHED',    0x02);
DEFINE('CAVEBOOKMARKS_ERROR_INSERTFAILED',  0x03);

DEFINE('CAVEBOOKMARKS_ERROR_DELETEFAILED',  0x04);

class CaveBookmarks_Model extends Model {

  function CaveBookmarks_Model(){
  }

  function getCaveBookmarks($extended = false){
    global $db, $params;

    // init return value
    $result = array();
    $names = array();
    // prepare query
    $sql = sprintf("SELECT cb.*, c.name, c.xCoord, c.yCoord, ".
                   "p.playerID, p.name as playerName, p.tribe, ".
                   "r.name as region ".
                   "FROM `CaveBookmarks` cb ".
                   "LEFT JOIN `Cave` c ON cb.caveID = c.caveID ".
                   "LEFT JOIN `Player` p ON c.playerID = p.playerID ".
                   "LEFT JOIN `Regions` r ON c.regionID = r.regionID ".
                   "WHERE cb.playerID = '%d' ".
                   "ORDER BY c.name", $params->SESSION->player->playerID);

    // send query
    $dbresult = $db->query($sql);

    // collect rows
    if ($dbresult)
      while($row = $dbresult->nextRow(MYSQL_ASSOC)){
        $row['raw_name'] = unhtmlentities($row['name']);
        $result[] = $row;
        array_push($names,$row['name']);
      }

    if ($extended) {
      $sql = sprintf("SELECT  c.name, c.xCoord, c.yCoord, ".
               "p.playerID, p.name as playerName, p.tribe, ".
               "r.name as region ".
               "FROM `Cave` c ".
               "LEFT JOIN `Player` p ON c.playerID = p.playerID ".
               "LEFT JOIN `Regions` r ON c.regionID = r.regionID ".
               "WHERE c.playerID = '%d'  ".
               "ORDER BY c.name",$params->SESSION->player->playerID);
     
      echo "<!-- $sql -->";
          // send query
      $dbresult = $db->query($sql);

      // collect rows
      if ($dbresult)
       while($row = $dbresult->nextRow(MYSQL_ASSOC)){
         if (!in_array($row['name'],$names)) {
           $row['raw_name'] = unhtmlentities($row['name']);
           $result[] = $row;
	 }  
       }
    }

    return $result;
  }

  function getCaveBookmark($bookmarkID){
    global $db, $params;

    $retval = NULL;
    $sql = sprintf("SELECT cb.*, c.name, c.xCoord, c.yCoord ".
                   "FROM `CaveBookmarks` cb ".
                   "LEFT JOIN `Cave` c ON cb.caveID = c.playerID ".
                   "WHERE cb.playerID = '%d' AND cb.bookmarkID = '%d' LIMIT 1",
                   $params->SESSION->player->playerID, $bookmarkID);
    $result = $db->query($sql);
    if ($result)
      $retval = $result->nextRow(MYSQL_ASSOC);

    return $retval;
  }

  function addCaveBookmark($caveID){
    global $db, $params;

    // no more than CAVESBOOKMARKS_MAX should be inserted
    if (sizeof($this->getCaveBookmarks()) >= CAVESBOOKMARKS_MAX)
      return CAVEBOOKMARKS_ERROR_MAXREACHED;

    // insert cave
    $sql = sprintf("INSERT INTO `CaveBookmarks` (`playerID`, `caveID`) ".
                   "VALUES ('%d', '%d')",
                  $params->SESSION->player->playerID, $caveID);
    if (!$db->query($sql))
      return CAVEBOOKMARKS_ERROR_INSERTFAILED;

    return CAVEBOOKMARKS_NOERROR;
  }

  function addCaveBookmarkByName($name){

    // check cave name
    $cave = getCaveByName($name);

    // no such cave
    if (!sizeof($cave))
      return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    return $this->addCaveBookmark($cave['caveID']);
  }

  function addCaveBookmarkByCoord($xCoord, $yCoord){

    // check coords
    $cave = getCaveByCoords($xCoord, $yCoord);

    // no such cave
    if (!sizeof($cave))
      return CAVEBOOKMARKS_ERROR_NOSUCHCAVE;

    return $this->addCaveBookmark($cave['caveID']);
  }

  function deleteCaveBookmark($bookmarkID){
    global $db, $params;

    // prepare query
    $sql = sprintf("DELETE FROM `CaveBookmarks` WHERE playerID = '%d' ".
                   "AND bookmarkID = '%d'",
                   $params->SESSION->player->playerID, $bookmarkID);

    // send query
    $db->query($sql);

    return $db->affected_rows() == 1
           ? CAVEBOOKMARKS_NOERROR
           : CAVEBOOKMARKS_ERROR_DELETEFAILED;
  }
}
