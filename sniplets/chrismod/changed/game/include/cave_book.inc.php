<?php

// ADDED by chris--- for cavebook

// ** SQL: **
/*
CREATE TABLE `cavebook` (
  `playerID` int(11) NOT NULL default '0',
  `entry_caveID` int(11) NOT NULL default '0'
) TYPE=MyISAM;
*/
// **********


function cavebook_getEntries($playerID) {
  global $db, $params, $config;

  $query = "SELECT a.entry_caveID, c.xCoord, c.yCoord, c.name, c.caveID, c.playerID, p.playerID, p.name AS playerName, p.tribe FROM cavebook a LEFT  JOIN cave c ON a.entry_caveID = c.caveID LEFT  JOIN player p ON c.playerID = p.playerID WHERE a.playerID = ".$playerID." ORDER  BY c.name";

  if (!($result = $db->query($query))) return;
  if ($result->isEmpty()) return;

  $entry_cave = array();
  $i = 0;
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    $entry_cave[id][$i] = $row['entry_caveID'];
    $entry_cave[tribe][$i] = $row['tribe'];
    $entry_cave[name][$i] = $row['name'];
    $entry_cave[playerName][$i] = $row['playerName'];
    $entry_cave[x][$i] = $row['xCoord'];
    $entry_cave[y][$i] = $row['yCoord'];
    $entry_cave[playerID][$i] = $row['playerID'];
    $i++;
  }

return $entry_cave;
}

function cavebook_deleteEntry ($playerID, $entry_caveID) {
  global $db, $params, $config;

//  $query = "SELECT name FROM player WHERE playerID = ".$entry_playerID;
//  if (!($result = $db->query($query))) return 4;

  $query = "DELETE FROM cavebook WHERE playerID = ".$playerID." AND entry_caveID = ".$entry_caveID;
  if (!($result = $db->query($query))) return 4;
    else return 3;

}

function cavebook_newEntry($playerID, $newCaveName) {
  global $db, $params, $config;

  if (!$newCaveName) return 5;

  $query = "SELECT caveID FROM cave WHERE name = '".$newCaveName."'";
  if (!($result = $db->query($query))) return 6;
  if ($result->isEmpty()) return 1;
    else {
      $row = $result->nextRow(MYSQL_ASSOC);
      $entry_caveID = $row['caveID'];

      $query = "SELECT * FROM cavebook WHERE playerID = ".$playerID." AND entry_caveID = ".$entry_caveID;
      if (!($result = $db->query($query))) return 6;
      if (!$result->isEmpty()) return 2;
        else {
          $query = "INSERT INTO cavebook SET playerID = ".$playerID.", entry_caveID = ".$entry_caveID;
          if (!($result = $db->query($query))) return 6;
            else return 0;
      }
  }
}


function cavebook_newEntry_coords($playerID, $x, $y) {
  global $db, $params, $config;

  if (($x && !$y) || (!$x && $y)) return 5;

  $query = "SELECT caveID FROM cave WHERE xCoord = ".$x." AND yCoord = ".$y;
  if (!($result = $db->query($query))) return 6;
  if ($result->isEmpty()) return 1;
    else {
      $row = $result->nextRow(MYSQL_ASSOC);
      $entry_caveID = $row['caveID'];

      $query = "SELECT * FROM cavebook WHERE playerID = ".$playerID." AND entry_caveID = ".$entry_caveID;
      if (!($result = $db->query($query))) return 6;
      if (!$result->isEmpty()) return 2;
        else {
          $query = "INSERT INTO cavebook SET playerID = ".$playerID.", entry_caveID = ".$entry_caveID;
          if (!($result = $db->query($query))) return 6;
            else return 0;
      }
  }
}


function cavebook_newEntry_id($playerID, $id) {
  global $db, $params, $config;

  if (!$id) return 5;

  $query = "SELECT caveID FROM cave WHERE caveID = '".$id."'";
  if (!($result = $db->query($query))) return 6;
  if ($result->isEmpty()) return 1;
    else {
      $row = $result->nextRow(MYSQL_ASSOC);
      $entry_caveID = $row['caveID'];

      $query = "SELECT * FROM cavebook WHERE playerID = ".$playerID." AND entry_caveID = ".$entry_caveID;
      if (!($result = $db->query($query))) return 6;
      if (!$result->isEmpty()) return 2;
        else {
          $query = "INSERT INTO cavebook SET playerID = ".$playerID.", entry_caveID = ".$entry_caveID;
          if (!($result = $db->query($query))) return 6;
            else return 0;
      }
  }
}
?>
