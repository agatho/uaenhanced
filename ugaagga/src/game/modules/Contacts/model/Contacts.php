<?
/*
 * Contacts.php -
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

DEFINE('CONTACTS_NOERROR',             0x00);

DEFINE('CONTACTS_ERROR_NOSUCHPLAYER',  0x01);
DEFINE('CONTACTS_ERROR_MAXREACHED',    0x02);
DEFINE('CONTACTS_ERROR_INSERTFAILED',  0x03);

DEFINE('CONTACTS_ERROR_DELETEFAILED',  0x04);

class Contacts_Model extends Model {

  function Contacts_Model(){
  }

  function getContacts(){
    global $db, $params;

    // init return value
    $result = array();

    // prepare query
    $sql = sprintf("SELECT c.*, p.name AS contactname, p.tribe AS contacttribe ".
                   "FROM `Contacts` c ".
                   "LEFT JOIN `Player` p ON c.contactplayerID = p.playerID ".
                   "WHERE c.playerID = '%d' ".
                   "ORDER BY contactname", $params->SESSION->player->playerID);

    // send query
    $dbresult = $db->query($sql);

    // collect rows
    if ($dbresult)
      while($row = $dbresult->nextRow(MYSQL_ASSOC))
        $result[] = $row;

    return $result;
  }

  function getContact($contactID){
    global $db, $params;

    $retval = NULL;
    $sql = sprintf("SELECT c.*, p.name AS contactname FROM `Contacts` c ".
                   "LEFT JOIN `Player` p ON c.contactplayerID = p.playerID ".
                   "WHERE c.playerID = '%d' AND c.contactID = '%d' LIMIT 1",
                   $params->SESSION->player->playerID, $contactID);
    $result = $db->query($sql);
    if ($result)
      $retval = $result->nextRow(MYSQL_ASSOC);

    return $retval;
  }

  function addContact($name){
    global $db, $params;

    // check username
    $player = getPlayerByName($name);

    // no such player
    if (!sizeof($player))
      return CONTACTS_ERROR_NOSUCHPLAYER;

    // no more than CONTACTS_MAX should be inserted
    if (sizeof($this->getContacts()) >= CONTACTS_MAX)
      return CONTACTS_ERROR_MAXREACHED;

    // insert player
    $sql = sprintf("INSERT INTO `Contacts` (`playerID`, `contactplayerID`) ".
                   "VALUES ('%d', '%d')",
                  $params->SESSION->player->playerID,
                   $player['playerID']);
    if (!$db->query($sql))
      return CONTACTS_ERROR_INSERTFAILED;

    return CONTACTS_NOERROR;
  }

  function deleteContact($contactID){
    global $db, $params;

    // prepare query
    $sql = sprintf("DELETE FROM `Contacts` WHERE playerID = '%d' ".
                   "AND contactID = '%d'", $params->SESSION->player->playerID, $contactID);

    // send query
    $db->query($sql);

    return $db->affected_rows() == 1
           ? CONTACTS_NOERROR
           : CONTACTS_ERROR_DELETEFAILED;
  }
}