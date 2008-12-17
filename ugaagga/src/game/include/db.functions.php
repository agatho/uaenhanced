<?
/*
 * db.functions.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function check_timestamp($value)
{
  global
    $config,
    $params;

  if ($value > 0) return;
  if (! $db_login = new DB($config->DB_LOGIN_HOST,
         $config->DB_LOGIN_USER,
         $config->DB_LOGIN_PWD,
         $config->DB_LOGIN_NAME)) {
    Header("Location: ".$config->DBERROR_URL);
    exit;
  }
  $query =
    "UPDATE Login ".
    "SET multi = 8 ".
    "WHERE loginID = '".$params->SESSION->player->playerID."'";
  $db_login->query($query);
}

function beginner_isCaveProtectedByID($caveID, $db) {
  $query =
    "SELECT (protection_end > NOW()+0) AS protected ".
    "FROM Cave ".
    "WHERE caveID = '$caveID'";
  if (!($result = $db->query($query)) || ! ($row = $result->nextRow())) {
//  echo $query;
    return 0;
  }
  return $row['protected'];
}

function beginner_endProtection($caveID, $playerID, $db) {
  $query =
    "UPDATE Cave ".
    "SET protection_end = NOW()+0 ".
    "WHERE caveID = '$caveID' ".
    "AND playerID = '$playerID' ";

  if (! $db->query($query) )
    return 0;
  else
    return 1;
}

function beginner_isCaveProtectedByCoord($x, $y, $db) {
  $query =
    "SELECT (protection_end > NOW()+0) AS protected ".
    "FROM Cave ".
    "WHERE xCoord = '$x' AND yCoord = '$y'";

  if (!($result = $db->query($query)) || ! ($row = $result->nextRow())) {
//  echo $query;
    return 0;
  }
  return $row['protected'];
}

function cave_isCaveSecureByCoord($x, $y, $db) {
  $query =
    "SELECT (secureCave OR playerID = 0) as secureCave ".
    "FROM Cave ".
    "WHERE xCoord = '$x' AND yCoord = '$y'";

  if (!($result = $db->query($query)) || ! ($row = $result->nextRow())) {
//  echo $query;
    return 0;
  }
  return $row['secureCave'];
}

function cave_isCaveSecureByID($caveID, $db) {
  $query =
    "SELECT (secureCave OR playerID = 0) as secureCave ".
    "FROM Cave ".
    "WHERE caveID = '$caveID' ";

  if (!($result = $db->query($query)) || ! ($row = $result->nextRow())) {
//  echo $query;
    return 0;
  }
  return $row['secureCave'];
}

/** unused */
function copyScienceFromPlayerToAllCaves($playerID) {
  global $db, $scienceTypeList;

  if (!($r = $db->query("SELECT * ".
          "FROM Player ".
          "WHERE playerID = '$playerID'"))) {
    return 0;
  }
  if ($r->isEmpty()) {
    return 0;
  }

  $row = $r->nextRow(MYSQL_ASSOC);

  for($i = 0; $i < MAX_SCIENCE; $i++) {
    $set.=
      $scienceTypeList[$i]->dbFieldName." = '".
      $row[$scienceTypeList[$i]->dbFieldName]."', ";
  }
  if (MAX_SCIENCE)
    $set = substr($set, 0, strlen($set) - 2) . " "; // remove last ','

  return $db->query("UPDATE Cave ".
        "SET ".$set." ".
        "WHERE playerID = '$playerID'");
}
?>
