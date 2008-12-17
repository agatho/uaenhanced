<?
/*
 * Suggestions.php -
 * Copyright (c) 2004  Marcus Lunzenauer/Johannes Roessel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

DEFINE('SUGGESTIONS_NOERROR',             0x00);

DEFINE('SUGGESTIONS_ERROR_INSERTFAILED',  0x01);

class Suggestions_Model extends Model {

  function Suggestions_Model(){
  }

  function addSuggestion($suggestion) {
    global $db, $params;

    // insert suggestion
    $sql = sprintf("INSERT INTO `Suggestions` (`playerID`, `Suggestion`) ".
                   "VALUES ('%d', '%s')",
                   $params->SESSION->player->playerID,
                   addslashes($suggestion));
    if (!$db->query($sql))
      return SUGGESTIONS_ERROR_INSERTFAILED;
    // refresh number of used suggestion credits
    $sql = sprintf("UPDATE `Player` SET `suggestion_credits` = `suggestion_credits`+1 ".
                   "WHERE `playerID` = %d",
                   $params->SESSION->player->playerID);
    if (!$db->query($sql))
      return SUGGESTIONS_ERROR_INSERTFAILED;

    return SUGGESTIONS_NOERROR;
  }

  function getCount() {
    global $params, $db;

    $retval = NULL;

    $sql = sprintf("SELECT `suggestion_credits` FROM `Player` ".
                   "WHERE `playerID` = %d",
                   $params->SESSION->player->playerID);

    $result = $db->query($sql);
    if ($result)
      $retval = $result->nextRow(MYSQL_ASSOC);

    return $retval['suggestion_credits'];
  }
}
