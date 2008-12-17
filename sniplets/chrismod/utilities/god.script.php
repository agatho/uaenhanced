<?
/***************************************************************************/
/* GOD SCRIPT by chris--- VERSION 2, 2.8.2004                              */
/***************************************************************************/

// This script handles all the god stuff
// like reactions to attacks, resource etc

// 1st we need some config


  include "util.inc.php";

  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";
  include INC_DIR."game_rules.php";
  include INC_DIR."formula_parser.inc.php";


$config = new Config();
$db = new Db();

// set memory limit
ini_set("memory_limit", "32M");

global   $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $scienceTypeList,
         $unitTypeList,
	 $config, $params;


echo "GOD SCRIPT: (".date("d.m.Y H:i:s",time()).") Starting...\r\n";



/***************************************************************************/
/* SETUP STUFF                                                             */
/***************************************************************************/


$attack_delay = 12*60*60; // seconds
$attack_delay_max = 24*60*60; // seconds

// VERSION 2: --------------------------------------------------------------

$send_message = 1; // should we send a message to attacker?

// END VERSION 2: --------------------------------------------------------------




//playerIDs
$uga_id = 3;
//$agga_id = 4;


// general checking stuff
$takeoverable = 0;
$secureCave = 1;

// ********* UGA ***********


// min values, if lower they will be resetted to this with a little gauss

// checking buildings [god][id]

$buildings['uga'][3] = 60; // sleeping place
$buildings['uga'][4] = 30; // worker
$buildings['uga'][6] = 10; // pub
$buildings['uga'][9] = 30; // waldlaeufer
$buildings['uga'][11] = 30; // quarry
$buildings['uga'][12] = 15; // schmiede
$buildings['uga'][17] = 40; // forrestguardian
$buildings['uga'][20] = 50; // storehouse
$buildings['uga'][24] = 30; // metalcollector
$buildings['uga'][26] = 15; // sulfurcollector




// checking externs

$externs['uga'][1] = 10; // production
$externs['uga'][5] = 32; // woodenwall
$externs['uga'][6] = 15; // patrol
$externs['uga'][7] = 20; // moving patrol
$externs['uga'][8] = 25; // tower



// checking resources

$resources['uga'][0] = 1000; // population
$resources['uga'][1] = 200000; // food
$resources['uga'][2] = 100000; // wood
$resources['uga'][3] = 100000; // stone
$resources['uga'][4] = 20000; // metall
$resources['uga'][5] = 20000; // sulfur
$resources['uga'][6] = 1500; // religion

// checking units

$units['uga'][67] = 10000; // uga special unit


// VERSION 2: --------------------------------------------------------------

$units_max['uga'][67] = 15000; // Max cap for units

// END VERSION 2: --------------------------------------------------------------



// checking factors





// VERSION 2: --------------------------------------------------------------

// Messagestuff

$messageSubject['uga'][0] = "DU WURM!";
$messageSubject['uga'][1] = "DU MADE!";
$messageSubject['uga'][2] = "DU KLEINER WICHT!";


$messageText['uga'][0] = "Wie kannst du es wagen mich anzugreifen? Sp&uuml;re nun meinen Zorn!";
$messageText['uga'][1] = "Du wagst es mich anzugreifen? Du wirst schon sehen, was du davon hast!";
$messageText['uga'][2] = "Du wei&szlig;t wohl nicht wer ich bin? Ich werde dich zerquetschen!";

// END VERSION 2: --------------------------------------------------------------





// ********* AGGA ***********


// min values, if lower they will be resetted to this with a little gauss

// checking buildings [god][id]
/*
$buildings['agga'][0] = 50; // fireplace
$buildings['agga'][1] = 500; // wood shack
$buildings['agga'][2] = 500; // hunters shack
$buildings['agga'][5] = 150; // sleeping place
$buildings['agga'][7] = 500; // gatherer
$buildings['agga'][9] = 500; // storehouse
$buildings['agga'][14] = 50; // melting
$buildings['agga'][15] = 50; // sulfurcollector
*/

// checking externs
/*
$externs['agga'][0] = 50; // productionlocation
$externs['agga'][2] = 100; // torture
$externs['agga'][4] = 50; // tower
$externs['agga'][5] = 50; // earth wall
$externs['agga'][7] = 50; // rats
$externs['agga'][8] = 500; // mirrors
$externs['agga'][9] = 500; // holey curtain
$externs['agga'][10] = 50; // watchtower
$externs['agga'][13] = 500; // tree catapult
$externs['agga'][16] = 500; // moat
$externs['agga'][19] = 500; // metallic curtain
*/

// checking resources
/*
$resources['agga'][0] = 300000; // population
$resources['agga'][1] = 500000; // food
$resources['agga'][2] = 100000; // wood
$resources['agga'][3] = 100000; // stone
$resources['agga'][4] = 20000; // metall
$resources['agga'][5] = 20000; // sulfur
$resources['agga'][6] = 1500; // religion
*/
// checking units

//$units['agga'][58] = 1000000; // agga special unit


// VERSION 2: --------------------------------------------------------------

//$units_max['agga'][58] = 1300000; // Max cap for units

// END VERSION 2: --------------------------------------------------------------

// checking factors






// VERSION 2: --------------------------------------------------------------

// Messagestuff
/*
$messageSubject['agga'][0] = "DU WURM!";
$messageSubject['agga'][1] = "DU MADE!";
$messageSubject['agga'][2] = "DU KLEINER WICHT!";


$messageText['agga'][0] = "Wie kannst du es wagen mich anzugreifen?";
$messageText['agga'][1] = "Du wagst es mich anzugreifen?";
*/
// END VERSION 2: --------------------------------------------------------------






// gauss function

function create_gauss () {

srand((double)microtime()*1000000);

$gauss = rand(70000, 130000);

$gauss = $gauss/100000;

return $gauss;
}


/***************************************************************************/
/* BUILDING OUR QUERY                                                      */
/***************************************************************************/

// UGA


// VERSION 2: --------------------------------------------------------------

// checking max units

$query = "UPDATE Cave SET ";

// Get last key
$array1 = array_keys($units['uga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_UNIT;$i++) {
  if ($units['uga'][$i]) {
    $query .= $unitTypeList[$i]->dbFieldName . " = IF (" . $unitTypeList[$i]->dbFieldName .
" > " . $units_max['uga'][$i] . ", " . round($units['uga'][$i]*create_gauss()) .
", " . $unitTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}

$query .= " WHERE playerID = " . $uga_id;

echo "checking unit max cap for God Uga...\r\n";

    if (!$db->query($query)){
      echo "DB or query error!\r\n";
      echo "QUERY WAS: ".$query;
    }


// END VERSION 2: --------------------------------------------------------------



$query = "UPDATE Cave SET ";

$query .= "takeoverable = " . $takeoverable . ", secureCave = " . $secureCave . ", ";

// Buildings

// Get last key
$array1 = array_keys($buildings['uga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_BUILDING;$i++) {
  if ($buildings['uga'][$i]) {
    $query .= $buildingTypeList[$i]->dbFieldName . " = IF (" . $buildingTypeList[$i]->dbFieldName ." < " . $buildings['uga'][$i] . ", " . round($buildings['uga'][$i]*create_gauss()) . ", " . $buildingTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}



$query .= ", ";

// externs

// Get last key
$array1 = array_keys($externs['uga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_DEFENSESYSTEM;$i++) {
  if ($externs['uga'][$i]) {
    $query .= $defenseSystemTypeList[$i]->dbFieldName . " = IF (" . $defenseSystemTypeList[$i]->dbFieldName ." < " . $externs['uga'][$i] . ", " . round($externs['uga'][$i]*create_gauss()) . ", " . $defenseSystemTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}



$query .= ", ";

// resorces

// Get last key
$array1 = array_keys($resources['uga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_RESOURCE;$i++) {
  if ($resources['uga'][$i]) {
    $query .= $resourceTypeList[$i]->dbFieldName . " = IF (" . $resourceTypeList[$i]->dbFieldName ." < " . $resources['uga'][$i] . ", " . $resources['uga'][$i]*create_gauss() . ", " . $resourceTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}



$query .= ", ";

// units

// Get last key
$array1 = array_keys($units['uga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_UNIT;$i++) {
  if ($units['uga'][$i]) {
    $query .= $unitTypeList[$i]->dbFieldName . " = IF (" . $unitTypeList[$i]->dbFieldName ." < " . $units['uga'][$i] . ", " . round($units['uga'][$i]*create_gauss()) . ", " . $unitTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}


$query .= " WHERE playerID = " . $uga_id;

echo "checking and updating God Uga...\r\n";

    if (!$db->query($query)){
      echo "DB or query error!\r\n";
      echo "QUERY WAS: ".$query;
    }


// AGGA

// VERSION 2: --------------------------------------------------------------

// checking max units

//$query = "UPDATE Cave SET ";

// Get last key
/*
$array1 = array_keys($units['agga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_UNIT;$i++) {
  if ($units['agga'][$i]) {
    $query .= $unitTypeList[$i]->dbFieldName . " = IF (" . $unitTypeList[$i]->dbFieldName .
" > " . $units_max['agga'][$i] . ", " . round($units['agga'][$i]*create_gauss()) .
", " . $unitTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}

$query .= " WHERE playerID = " . $agga_id;

echo "checking unit max cap for God Agga...\r\n";

    if (!$db->query($query)){
      echo "DB or query error!\r\n";
      echo "QUERY WAS: ".$query;
    }

*/
// END VERSION 2: --------------------------------------------------------------



/*
$query = "UPDATE Cave SET ";

$query .= "takeoverable = " . $takeoverable . ", secureCave = " . $secureCave . ", ";
*/
// Buildings

// Get last key
/*
$array1 = array_keys($buildings['agga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_BUILDING;$i++) {
  if ($buildings['agga'][$i]) {
    $query .= $buildingTypeList[$i]->dbFieldName . " = IF (" . $buildingTypeList[$i]->dbFieldName ." < " . $buildings['agga'][$i] . ", " . round($buildings['agga'][$i]*create_gauss()) . ", " . $buildingTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}



$query .= ", ";
*/
// externs

// Get last key
/*
$array1 = array_keys($externs['agga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_DEFENSESYSTEM;$i++) {
  if ($externs['agga'][$i]) {
    $query .= $defenseSystemTypeList[$i]->dbFieldName . " = IF (" . $defenseSystemTypeList[$i]->dbFieldName ." < " . $externs['agga'][$i] . ", " . round($externs['agga'][$i]*create_gauss()) . ", " . $defenseSystemTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}



$query .= ", ";
*/
// resorces

// Get last key
/*
$array1 = array_keys($resources['agga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_RESOURCE;$i++) {
  if ($resources['agga'][$i]) {
    $query .= $resourceTypeList[$i]->dbFieldName . " = IF (" . $resourceTypeList[$i]->dbFieldName ." < " . $resources['agga'][$i] . ", " . $resources['agga'][$i]*create_gauss() . ", " . $resourceTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}



$query .= ", ";
*/
// units

// Get last key
/*
$array1 = array_keys($units['agga']);

$last_key = $array1[count($array1)-1];


for ($i=0;$i<$MAX_UNIT;$i++) {
  if ($units['agga'][$i]) {
    $query .= $unitTypeList[$i]->dbFieldName . " = IF (" . $unitTypeList[$i]->dbFieldName ." < " . $units['agga'][$i] . ", " . round($units['agga'][$i]*create_gauss()) . ", " . $unitTypeList[$i]->dbFieldName . ")";
    if ($i < $last_key) $query .= ", ";
  }
}


$query .= " WHERE playerID = " . $agga_id;

echo "checking and updating God Agga...\r\n";

    if (!$db->query($query)){
      echo "DB or query error!\r\n";
      echo "QUERY WAS: ".$query;
    }
*/




/***************************************************************************/
/* CHECKING IF GODS WERE ATTACKED                                          */
/***************************************************************************/

echo "checking if Gods were attacked...\r\n";


// clear the table
/*
//$query = "DELETE FROM event_gods WHERE target_caveID != 250 AND target_caveID != 1080";
$query = "DELETE FROM event_gods WHERE target_caveID != 1018";

if (!($result=$db->query($query))) {
  echo "DB or query error! Could not clean the table.\r\n";
  echo "QUERY WAS: ".$query;
  exit;
}
*/


$query = "SELECT * FROM event_gods WHERE blocked = 0";

if (!($result=$db->query($query))) {
  echo "DB or query error! Could not get events.\r\n";
  echo "QUERY WAS: ".$query;
  exit;
}

$count = 0;

while($row = $result->nextRow()) {
  echo "user $row[playerID] from $row[source_caveID] has attacked cave $row[target_caveID] at $row[impact] ";
  if ($row[event] == 1) echo "with a wonder with id $row[eventID]\r\n";
  if ($row[event] == 2) echo "with a movement with id $row[eventID]\r\n";
  $count++;


// Generating attacking time

$now = time();
$start = date("YmdHis", $now);
srand((double)microtime()*1000000);
$delay = rand($attack_delay, $attack_delay_max);
$attack_time = $now + $delay;
$impact = date("YmdHis", $attack_time);

if ($row[event] == 1) {

  echo "We re attacking him back at $impact... writing the wonder event...\r\n";

  $query = "INSERT INTO event_wonder SET ";

//if ($row[target_caveID] == 250) $query .= "casterID = 1, wonderID = 67, ";
if ($row[target_caveID] == 1018) $query .= "casterID = 3, wonderID = 68, ";
  $query .= "sourceID = $row[target_caveID], targetID = $row[source_caveID], ".
  "impactID = 0, event_start = $start, event_end = $impact, blocked = 0";

    if (!$db->query($query)){
      echo "DB or query error!\r\n";
      echo "QUERY WAS: ".$query;
    } else {
    $query = "UPDATE event_gods SET blocked = 1 WHERE id = $row[id]";
      if (!$db->query($query)){
        echo "DB or query error! Could not block event id $row[id]\r\n";
        echo "QUERY WAS: ".$query;
      }

// VERSION 2: --------------------------------------------------------------

// Sending a message to attacker

if ($send_message) {

echo "Sending a message to attacker!\r\n";

$query = "INSERT INTO message SET recipientID = $row[playerID], ";


//*****************************************************************
//*************** ACHTUNG: folgendes anpassen *********************
//
// caveID = die Götterhöhlen
//
//*****************************************************************

if ($row[target_caveID] == 1018) $query .= "senderID = $uga_id, ";
//if ($row[target_caveID] == xxx) $query .= "senderID = $agga_id, ";

// Generating message time

$message_time = time();
$thetime = date("YmdHis", $message_time);

$query .= "messageClass = 10, messageTime = $thetime, ";


//*****************************************************************
//*************** ACHTUNG: folgendes anpassen *********************
//
// caveID = die Götterhöhlen
//
//*****************************************************************

if ($row[target_caveID] == 1018) {
// Randomly select a message and subject from pool

  srand((double)microtime()*1000000);
  $message_number = rand(0, count($messageSubject['uga'])-1);
  $message_number2 = rand(0, count($messageText['uga'])-1);


  $query .= "messageSubject = '".$messageSubject['uga'][$message_number]."', ";
  $query .= "messageText = '".$messageText['uga'][$message_number2]."'";
}


//*****************************************************************
//*************** ACHTUNG: folgendes anpassen *********************
//
// caveID = die Götterhöhlen
//
//*****************************************************************

//if ($row[target_caveID] == xxx) {
// Randomly select a message and subject from pool
/*
  srand((double)microtime()*1000000);
  $message_number = rand(0, count($messageSubject['agga'])-1);
  $message_number2 = rand(0, count($messageText['agga'])-1);


  $query .= "messageSubject = '".$messageSubject['agga'][$message_number]."', ";
  $query .= "messageText = '".$messageText['agga'][$message_number2]."'";
}
*/

}
if (!$db->query($query)) {
  echo "DB or query error! Could not write message to attacker.\r\n";
  echo "QUERY WAS: ".$query;
}

// END VERSION 2: --------------------------------------------------------------



    }


}

if ($row[event] == 2) {

  echo "We re attacking him back at $impact... writing the movement event...\r\n";

  $query = "INSERT INTO event_movement SET caveID = $row[target_caveID], source_caveID = $row[target_caveID], target_caveID = $row[source_caveID], ".
	   "movementID = 3, speedFactor = 10, event_start = $start, event_end = $impact, blocked = 0, ";

if ($row[target_caveID] == 1018) $query .= "unit_diego_specialunit = ".round(5000*create_gauss());
//if ($row[target_caveID] == 250) $query .= "unit_ugaspecialunit = ".round(5000*create_gauss());

    if (!$db->query($query)){
      echo "DB or query error!\r\n";
      echo "QUERY WAS: ".$query;
    } else {
    $query = "UPDATE event_gods SET blocked = 1 WHERE id = $row[id]";
      if (!$db->query($query)){
        echo "DB or query error! Could not block event id $row[id]\r\n";
        echo "QUERY WAS: ".$query;
      }

// VERSION 2: --------------------------------------------------------------

// Sending a message to attacker

if ($send_message) {

echo "Sending a message to attacker!\r\n";

$query = "INSERT INTO message SET recipientID = $row[playerID], ";

//*****************************************************************
//*************** ACHTUNG: folgendes anpassen *********************
//
// caveID = die Götterhöhlen
//
//*****************************************************************

if ($row[target_caveID] == 1018) $query .= "senderID = $uga_id, ";
//if ($row[target_caveID] == xxx) $query .= "senderID = $agga_id, ";

// Generating message time

$message_time = time();
$thetime = date("YmdHis", $message_time);

$query .= "messageClass = 10, messageTime = $thetime, ";


//*****************************************************************
//*************** ACHTUNG: folgendes anpassen *********************
//
// caveID = die Götterhöhlen
//
//*****************************************************************

if ($row[target_caveID] == 1018) {
// Randomly select a message and subject from pool

  srand((double)microtime()*1000000);
  $message_number = rand(0, count($messageSubject['uga'])-1);
  $message_number2 = rand(0, count($messageText['uga'])-1);


  $query .= "messageSubject = '".$messageSubject['uga'][$message_number]."', ";
  $query .= "messageText = '".$messageText['uga'][$message_number2]."'";
}


//*****************************************************************
//*************** ACHTUNG: folgendes anpassen *********************
//
// caveID = die Götterhöhlen
//
//*****************************************************************

//if ($row[target_caveID] == xxx) {
// Randomly select a message and subject from pool
/*
  srand((double)microtime()*1000000);
  $message_number = rand(0, count($messageSubject['agga'])-1);
  $message_number2 = rand(0, count($messageText['agga'])-1);


  $query .= "messageSubject = '".$messageSubject['agga'][$message_number]."', ";
  $query .= "messageText = '".$messageText['agga'][$message_number2]."'";
}
*/

}
if (!$db->query($query)) {
  echo "DB or query error! Could not write message to attacker.\r\n";
  echo "QUERY WAS: ".$query;
}

// END VERSION 2: --------------------------------------------------------------



    }


}


}


// clear the table

$query = "DELETE FROM event_gods WHERE blocked = 1";

if (!($result=$db->query($query))) {
  echo "DB or query error! Could not clean the table.\r\n";
  echo "QUERY WAS: ".$query;
}

echo "finished...\r\n";



?> 
