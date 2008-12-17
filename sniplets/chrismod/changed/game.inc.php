<?
/**
 *
 *  @return 1 for success 0 for failure
 */
function game_createAccount($db_login, $username, $password, $email, $easyStart, $sex){

  global $cfg; 

  // produce activation code
  srand((double)microtime() * 1000000);
  $actCode = rand(100000, 999999);

  // insert new account
  $query = "INSERT INTO Login (user, password, email, activationID, activated, creation) " .
           "VALUES ('{$username}', '{$password}', '{$email}', '{$actCode}', '0', NOW()+0)";
  $result = $db_login->query($query);
  
  if (!result){
    printf("%s:%s<br>\n", __file__, __line__);
    return 0;
  }
 
  // get playerID
  $playerID = $db_login->insertID();

  // get link to DB 'game'
  $db_game = new DB($cfg['DB_GAME']['HOST'],
                    $cfg['DB_GAME']['USER'],
                    $cfg['DB_GAME']['PWD'],
                    $cfg['DB_GAME']['NAME']); 
  if (!$db_game){  
    $db_login->query("DELETE FROM Login WHERE user = '{$username}'");
    printf("%s:%s<br>\n", __file__, __line__);
    return 0;
  }

  // insert into game DB  
  $query = "INSERT INTO Player (playerID, email, email2, name, sex, template, takeover_max_caves, SecureCaveCredits) " .
           "VALUES ('$playerID', '$email', '$email', '$username', '$sex', 1, 8, '{$cfg['SecureCaveCredits']}')";

  if (!$db_game->query($query)){ echo $query;
    $db_login->query("DELETE FROM Login WHERE user = '{$username}'");
    printf("%s:%s<br>\n", __file__, __line__);
    return 0;
 }

  // get playerID (FIXME: this is really needed?)
 // $playerID = $db_login->insertID();
  
  //helden erstellen
/*
  if(!game_heroCreate($db_game, $playerID)){ 
    echo "fail heroCreate";
    $db_login->query("DELETE FROM Login WHERE user = '{$username}'");
    $db_game->query("DELETE FROM Player WHERE playerID = '{$playerID}'");
    return 0;
  }
*/
  
  if (!game_adviceCave($db_game, $playerID, $easyStart)){ echo "fail adc";
    $db_login->query("DELETE FROM Login WHERE user = '{$username}'");
    $db_game->query("DELETE FROM Player WHERE playerID = '{$playerID}'");
    $db_game->query("DELETE FROM Hero WHERE playerID = '($playerID)'");
    printf("%s:%s<br>\n", __file__, __line__);
    return 0;
  }
  
  $query = "INSERT INTO Message (recipientID, senderID, messageClass, messageSubject, " .
           "messageText) VALUES ('{$playerID}', '0', '{$cfg['WELCOME_CLASS']}', " .
           "'{$cfg['WELCOME_SUBJECT']}', '{$cfg['WELCOME_TEXT']}')";
  $db_game->query($query);


// ADDED by chris--- for QUests
process_abortedQuests($playerID, $db_game);



  if(!mail($email, game_newAccountSubject(), game_newAccountBody($username, $tribe, $password, $actCode)))
	   echo "<font color=\"#ffffff\">Email konnte nicht gesendet werden. Bitte wenden Sie sich an den Support</font><p>";


  return 1;
}


function game_heroCreate($db_game, $playerID){
  require_once("held_names.php");
  
  $name = createNames();
  $k = rand(7,9);
  $a = rand(7,9);
  $v = rand(7,9);
  $m = rand(7,9);
  $f = 5;
  $querry = "INSERT into Hero (playerID, name, angriffsWert, verteidigungsWert, mentalKraft, koerperKraft, fluchtGrenze, erfahrungsWert, level, bonusPunkte, leichteSiege, schatzHals, schatzKopf, schatzRing, schatzRuestung, schatzWaffe, schatzSchild) VALUES (".$playerID.", '".$name."', ".$a.", ".$v.", ".$m.", ".$k.", 5, 0,0,0,0,0,0,0,0,0,0) ";
  $result = $db_game->query($querry);
  echo $querry;
  if(!$result){
    echo "fehlgeschlagen";
    return 0;
    
  }else{
    return 1;
  }
}

// ADDED by chris--- for Quests --------------------------------------------------------------------

function process_abortedQuests($playerID, $db_game) {

global $cfg;

  $query = "SELECT questID FROM quests WHERE quest_finished = 1 AND isQuestBegin = 1";

  $result = $db_game->query($query);
    if (!$result) return 0;

  $stuff = array();

  while ($row = $result->nextrow()){
    $stuff[] = array("questID"  => $row['questID']);
  }

  foreach ($stuff AS $key => $data) {
    $query = "INSERT INTO quests_aborted " .
             "SET playerID = " . $playerID .
             ", questID = " . $data['questID'];
    $result = $db_game->query($query);
  }

return 1;

}
//----------------------------------------------------------------------------------------------

 
function game_adviceCave($db_game, $playerID, $easyStart){

  global $cfg;


  list($usec, $sec) = explode(' ', microtime());
  srand((float) $sec + ((float) $usec * 100000));

  // count the empty caves that aren't the destination of an ongoing 
  // movement and calculate a random number
  $query = "SELECT COUNT(caveID) AS n FROM Cave WHERE playerID = 0 AND ".
           "starting_position > 0";
  $result = $db_game->query($query);
  if (!$result) return 0;
  
  $row = $result->nextRow();
  if ($row['n'] == 0) return 0;
  
  $caveNumber = rand(0, $row['n'] - 1);
  $result->free($result);

  // get the caveID of the $caveNumber'th entry  
  $query = "SELECT caveID FROM Cave WHERE playerID = 0 ".
           "AND starting_position > 0 LIMIT {$caveNumber}, 1";
  $result = $db_game->query($query);
  if (!$result || $result->isEmpty()) return 0;
  
  $row = $result->nextRow();

  // update cave $caveID to belong to the player with id $playerID
  // And set the start conditions

  require_once($cfg['GAME_RULES']);
  global $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList;
  init_Buildings();
  init_Units();
  init_Resources();
  init_Sciences();
  init_DefenseSystems();
 
  $set = array();
  foreach ($resourceTypeList      AS $value) $set[$value->dbFieldName] = "{$value->dbFieldName} = 0";
  foreach ($buildingTypeList      AS $value) $set[$value->dbFieldName] = "{$value->dbFieldName} = 0";
  foreach ($unitTypeList          AS $value) $set[$value->dbFieldName] = "{$value->dbFieldName} = 0";
  foreach ($scienceTypeList       AS $value) $set[$value->dbFieldName] = "{$value->dbFieldName} = 0";
  foreach ($defenseSystemTypeList AS $value) $set[$value->dbFieldName] = "{$value->dbFieldName} = 0";
 
  $set['protection_end'] = 'protection_end = (NOW() + INTERVAL '. BEGINNER_PROTECTION_HOURS .' HOUR)+0';
  $set['secureCave'] = 'secureCave = 1';


  if (! $easyStart ) {
  foreach($cfg['START_SETTINGS'] as $key => $value)
    $set[$key] = "{$key} = {$value}";
  }
  else {      // read start values, if fail, simply don't change set array
    $query = "SELECT * FROM StartValue";
    if ( $result = $db_game->query($query) ) {
      while($entry = $result->nextRow()) {
	$set[$entry[dbFieldName]] = "{$entry[dbFieldName]} = '{$entry[value]}'";
      }
    }
  }

  $setArray = $set;
  $set = implode(",", $set);

   
  $query = "UPDATE Cave SET playerID = {$playerID}, {$set} WHERE caveID = {$row['caveID']}";
  $result = $db_game->query($query);
  if (!$result) { echo $query; return 0; }

  // write science start values to the player table

  $set = array();
  foreach ($scienceTypeList AS $value)  {
    $set[$value->dbFieldName] = $setArray[$value->dbFieldName];   // get all science sets
  }
  $query = "UPDATE Player SET ".implode($set, ", ")." WHERE playerID = {$playerID}";
  if (! $db_game->query($query)) { 
    echo $query;  // not that worse, so don't delete account 
  }
   
  
  return $row['caveID'];
}

function game_newAccountSubject() {
  return "Ihr neuer Account bei uga-agga@chris";
}

function game_newAccountBody($username, $tribe, $password, $actcode) {
  global $cfg;
  return
    "Herzlich willkommen $user!\n\n".
    "Hier Ihre Account-Informationen:\n".
    "Spieler: {$username}\n".
    "Stamm: {$tribe}\n".
    "Passwort: {$password}\n".
    "Aktivierungscode: {$actcode}\n\n".
    
    "Sollte Ihr Email-Client dies unterstützen, können Sie zur Aktivierung ".
    "auch direkt auf diesen Link klicken: ".
    
    "{$cfg['LOGIN_ACTIVATION_URL']}?modus=".ACTIVATE_ACCOUNT.
    "&password=".urlencode($password)."&username=".urlencode($username)."&actcode=".urlencode($actcode)."\n". 
    
    "Falls Ihr Browser den Link nicht unterstützt, melden Sie sich ".
    "einfach mit Ihren Logindaten an. Sie werden dann beim ersten ".
    "Versuch nach dem Aktivierungscode gefragt.\n".
    "Sollten Sie Ihren Account nicht innerhalb den nächsten 48 Stunden aktivieren, ".
    "wird er beim nächsten Aufräumen des Datenbestandes gelöscht.\n\n".
    "Bitte beachten Sie das der Server leider nicht \"rund um die Uhr\" online sein kann\n".
    "Viel Spaß beim Spielen!\n\n".
    "Sollten Sie sich nicht bei dem Spiel angemeldet haben, ignorieren Sie diese Mail einfach.";
}
?>
