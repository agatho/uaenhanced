<?

function ticker_text()
{
  global
    $config,
    $params,
    $db;

// Getting the messages
  $query =
    "SELECT * ".
    "FROM ticker ".
    "ORDER BY time DESC ".
    "LIMIT 0,".TICKER_MESSAGE_AMOUNT;

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return "++++ System: Noch keine Nachrichten!                                   ";
  }

  $messages = array();
  $i = 0;
  while($row = $result->nextRow(MYSQL_ASSOC)) {
    $i++;
    $messages[$i]['senderID'] = $row[senderID];
    $messages[$i]['text'] = $row[message];
  }

  $ticker_text = "";

  for ($a=$i;$a>=1;$a--) {
    // getting the player names
    $query = "SELECT name FROM player WHERE playerID = " . $messages[$a]['senderID'];

    if (!($result = $db->query($query))) {
      echo "Database error!";
      return;
    }
    if ($result->isEmpty()) {
      $sender = "Unbekannt";
    } else {
      $sendername = $result->nextRow(MYSQL_ASSOC);
      $sender = $sendername[name];
    }

$sender = unhtmlentities($sender);
$sender = strip_tags($sender);
$sender = str_replace("\n", " ", $sender);
$sender = str_replace("\r", "", $sender);
$sender = str_replace("<br>", " ", $sender);
$sender = str_replace("<br />", " ", $sender);
$sender = str_replace("&quot;", "", $sender);
$sender = str_replace("&#039;", "", $sender);
$sender = str_replace('"', "", $sender);

$message = unhtmlentities($messages[$a]['text']);
$message = strip_tags($message);
$message = str_replace("\n", " ", $message);
$message = str_replace("\r", "", $message);
$message = str_replace("<br>", " ", $message);
$message = str_replace("<br />", " ", $message);
$message = str_replace("&quot;", "", $message);
$message = str_replace("&#039;", "", $message);
$message = str_replace('"', "", $message);


  $ticker_text .= "++++ ". $sender . ": " . $message . "             ";

  }

  $ticker_text .= "                      ";

  return $ticker_text;

}


function getTickerMessages ($db) {

// Getting the messages
  $query =
    "SELECT * ".
    "FROM ticker ".
    "ORDER BY time ASC";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return ;
  }

  $messages = array();
  $i = 0;
  while($row = $result->nextRow(MYSQL_ASSOC)) {
    $i++;
    $messages[$i]['senderID'] = $row[senderID];
    $messages[$i]['text'] = $row[message];
    $messages[$i]['time'] = $row[time];
  }

  $ticker = array();

  for ($a=$i;$a>=1;$a--) {
    // getting the player names
    $query = "SELECT name FROM player WHERE playerID = " . $messages[$a]['senderID'];

    if (!($result = $db->query($query))) {
      echo "Database error!";
      return;
    }
    if ($result->isEmpty()) {
      $sender = "Unbekannt";
    } else {
      $sendername = $result->nextRow(MYSQL_ASSOC);
      $sender = $sendername[name];
    }

    $t = $messages[$a]['time'];    
    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};

    $ticker[] = array('sender'		=> $sender,
		      'message'		=> $messages[$a]['text'],
		      'time'		=> $time,
		      'alternate'	=> ($a % 2 ? "" : "alternate"),
		      'senderID'	=> $messages[$a]['senderID'],
		      'playerDetail_modus' => PLAYER_DETAIL);
  }

  return $ticker;

}



function ticker_insertMessageIntoDB($nachricht, $playerID){
  global $db, $params, $config;

  $query = "INSERT INTO ticker (senderID, message, time) " .
           "VALUES (" .
           "'" . $playerID . "', " .
           "'" . $nachricht . "', " .
           "NOW()+0)";
  return $db->query($query);
}


function playerEntryAllowed ($playerID) {

global $db, $params, $config;

$now = time();

$time = $now - 60*TICKER_BLOCK_TIME;

$date = getdate($time);

if ($date[mon] < 10) $date[mon] = "0".$date[mon];
if ($date[mday] < 10) $date[mday] = "0".$date[mday];
if ($date[hours] < 10) $date[hours] = "0".$date[hours];
if ($date[minutes] < 10) $date[minutes] = "0".$date[minutes];
if ($date[seconds] < 10) $date[seconds] = "0".$date[seconds];

$time = $date[year].$date[mon].$date[mday].$date[hours].$date[minutes].$date[seconds];

$query = "SELECT * FROM ticker WHERE senderID = " . $playerID .
         " AND time > " . $time;

    if (!($result = $db->query($query))) {
      echo "Database error!";
      return 1;
    }
    if ($result->isEmpty()) {
      return 0;
    } else {
	$row = $result->nextRow(MYSQL_ASSOC);
	$time = $row[time];
	return $time;
    }


}
?>
