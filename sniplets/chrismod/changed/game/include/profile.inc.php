<?

/*
 * profile.inc.php - routines for setting player data as password...
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/** This function deletes the account. The account isn't deleted directly,
 *  but marked with a specialtag. It'll be deleted by a special script,
 *  that runs on a given time...
 */
function profile_processDeleteAccount($playerID, $db_login){
  $query = "UPDATE Login SET deleted = 1, ".
           "email = CONCAT(email, '_del') ".
           "WHERE LoginID = '$playerID'";
  return $db_login->query($query);
}

/** This function sets the changed data specified by the user.
 */
function profile_processUpdate($playerID, $data, $password, $cave_prio,  // ADDED by chris--- for cave sorting: cave_prio
                               $db_game, $db_login){

  // list of fields, that should be inserted into the player record
  $fields = array("sex", "origin", "age", "icq", "avatar", "description",
                  "template", "show_unqualified", "show_ticker", "show_returns", "urlaub", "gfxpath", "email2");
// ADDED by chris--- for ticker: show_ticker
// ADDED by chris--- for returns: show_returns
// ADDED by chris--- for urlaub: urlaub

// ADDED by chris--- for urlaubmodus -----------------------------------

if ($data['urlaub'] == 1) {

// check ob Clan im Krieg
  $sql = "SELECT tribe FROM Player WHERE playerID = ".$playerID;
  if (!($result=$db_game->query($sql))) return 8;
  if (!$result->isEmpty()) {
    // Spieler hat nen clan
    $game = $result->nextRow();
    $tribe = $game['tribe'];

    $sql = "SELECT relationType FROM relation WHERE tribe = '".$tribe."'";
    if (!($result=$db_game->query($sql))) return 8;
    if (!$result->isEmpty()) {
      // Clan hat Beziehungen
      $war = FALSE;
      while($game = $result->nextRow()) {
        if ($game['relationType'] == 2) $war = TRUE;
      } // end while
      if ($war) return 6;
    } // end if beziehung
  }// end if clan



// check ob Spieler kürzlich im Urlaub
// Username holen
  $sql = "SELECT Name FROM Player WHERE playerID = ".$playerID." LIMIT 0,1";
  if (!($result=$db_game->query($sql))) return 8;
  if ($result->isEmpty()) return 8;
  $game = $result->nextRow();

  $sql = "SELECT urlaub_begin, urlaub_end FROM Login WHERE user = '".$game['Name']."'";
  if (!($result=$db_login->query($sql))) return 8;
  if ($result->isEmpty()) return 8;
  $login = $result->nextRow();
  $urlaub_begin = $login['urlaub_begin'];
  $urlaub_end = $login['urlaub_end'];
  $jetzt = time();
  $diff = $urlaub_end-$urlaub_begin;
  $sperre = $jetzt+$diff;
  if ($jetzt < $sperre) return 7;

    else {

// Alles ok, aktiviere Urlaubsmodus

// Alle Siedlungen des Spieler auf urlaub = 1 setzen, protection_end auf jetzt+1monat, evt. secure_cave ändern? Falls ja muß der vorige Zustand gespeichert werden
  $sql = "UPDATE Cave SET secureCave_was = secureCave WHERE playerID = ".$playerID;
  if (!$db_game->query($sql)) return 8;

  $endtime = date("YmdHis", time()+31*24*60*60);
  $sql = "UPDATE Cave SET urlaub = 1, protection_end = ".$endtime.", secureCave = 1 WHERE playerID = ".$playerID;
  if (!$db_game->query($sql)) return 8;

// Player Tabelle updaten
  $sql = "UPDATE Player SET urlaub = 1 WHERE playerID = ".$playerID;
  if (!$db_game->query($sql)) return 8;

// Username holen
  $sql = "SELECT Name FROM Player WHERE playerID = ".$playerID." LIMIT 0,1";
  if (!($result=$db_game->query($sql))) return 8;
  if ($result->isEmpty()) return 8;
  $game = $result->nextRow();

// Login Tabelle updaten
  $sql = "UPDATE Login SET urlaub = 1, urlaub_begin = ".time().", urlaub_end = 0 WHERE user = '".$game['Name']."'";
  if (!$db_login->query($sql)) return 8;


  }

}

// END urlaubsmodus ----------------------------------------------

  // first update data
  $data['description'] = nl2br($data['description']);

  if ($set = db_makeSetStatementSecure($data, $fields)) {
    $query = "UPDATE Player SET $set WHERE playerID = '$playerID'";
    if (!$db_game->query($query)){
      return 2;
    }
  }

  // now update the password, if it is set
  if (strlen($password['password1'])){

    // typo?
    if (strcmp($password['password1'], $password['password2']) != 0)
      return 1;
    // password too short?
    if (strlen($password['password1']) <= 4)
      return 3;

    // set password
    $query = "UPDATE Login SET password = '$password[password1]' ".
             "WHERE LoginID = '$playerID'";
    if (!$db_login->query($query))
      return 4;
  }

// ADDED by chris--- for cave sorting
// Processing the cave priorities

$meineHoehlen = getCaves($playerID);
if (sizeof($meineHoehlen) > 1) {

  // We should check the values here
  foreach($cave_prio as $key => $value){
    if ($value > 10 || $value < 0) return 5; // Wrong value
  // if we have 2 or more of the same values here
  // it doesnt matter cause it doesnt affect the game
  // the priority is undefined then
  }

  // Updating the table

  foreach($cave_prio as $key => $value){
    $query = "UPDATE cave SET priority = ".$value." WHERE caveID = ".$key." AND playerID = ".$playerID;
    if (!$db_game->query($query))
      return 2; // Database error
  }
} // end if

  return 0;
}

/** This function gets the players data out of the game and login
 *  database.
 */
function profile_getPlayerData($playerID, $db_game, $db_login){
  $query = "SELECT * FROM Player WHERE playerID = '$playerID'";
  if (!($r_game=$db_game->query($query)))
    return 0;

  $query = "SELECT * FROM Login WHERE LoginID = '$playerID'";
  if (!($r_login=$db_login->query($query)))
    return 0;

  if ($r_login->isEmpty() || $r_game->isEmpty())
    return 0;

  $game = $r_game->nextRow();
  $game['description'] = str_replace("<br />", "", $game['description']);

  return array("game" => $game, "login" => $r_login->nextRow());
}
?>
