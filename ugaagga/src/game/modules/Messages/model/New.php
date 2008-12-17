<?php
/*
 * New.php - TODO
 * Copyright (c) 2005  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Model.php');

class Messages_New_Model extends Model {

  function getCount(){
    global $params, $db;

    // prepare retval
    $retval = 0;

    // get user messages
    $query = sprintf('SELECT COUNT(*) as num FROM Message '.
                     'WHERE recipientID = %d AND `read` = 0 '.
                     'AND recipientDeleted = 0',
                     $params->SESSION->player->playerID);
    $r = $db->query($query);

    if ($r && !$r->isEmpty()) {
      $row = $r->nextRow(MYSQL_ASSOC);
      $retval = $row['num'];
    }

    return $retval;
  }
}
?>