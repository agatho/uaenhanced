<?php

/**
 *
 */
function messages_getIncomingMessagesCount(){
  global $db, $params;
  $query = 'SELECT COUNT(*) as num FROM Message '.
           'WHERE recipientID = ' .
           $params->SESSION->user['playerID'] .
           ' AND recipientDeleted != 1';
  if (!($dbresult = $db->query($query))) return 0;
  $row = $dbresult->nextRow(MYSQL_ASSOC);
  return $row['num'];
}

/**
 *
 */
function messages_getOutgoingMessagesCount(){
  global $db, $params;
  
  $query = 'SELECT COUNT(*) as num FROM Message '.
           'WHERE senderID = ' .
           $params->SESSION->user['playerID'] .
           ' AND senderDeleted != 1';
  if (!($dbresult = $db->query($query))) return 0;
  $row = $dbresult->nextRow(MYSQL_ASSOC);
  return $row['num'];
}

/**
 *
 */
function messages_getIncomingMessages($offset, $row_count){
  global $db, $params, $config;

  $nachrichten = array();

  // get announcements
  $query =  'SELECT ' .
            'm.messageID, ' .
            'p.name, ' .
            'm.messageClass, ' .
            'm.messageSubject AS betreff, ' .
            'm.messageTime ' .

            'FROM Message m ' .
            'LEFT JOIN Player p ' .
            'ON p.playerID = m.senderID ' .

            'WHERE ' .
            'messageClass = 1001 ' .
            'ORDER BY m.messageTime DESC, m.messageID DESC';

  if (!($dbresult = $db->query($query)))
    return array();

  while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $row['absender_empfaenger'] = empty($row['name']) ? "System" : $row['name'];
    $t = $row['messageTime'];    
    $row['datum'] = $t{6}.$t{7}  .".".
                    $t{4}.$t{5}  .".".
                    $t{0}.$t{1}  .
                    $t{2}.$t{3}  ." ".
                    $t{8}.$t{9}  .":".
                    $t{10}.$t{11}.":".
                    $t{12}.$t{13};
    $row['nachrichtenart'] = $config->messageClass[$row['messageClass']];
    $row['linkparams'] = '?modus=' . MESSAGESDETAIL . '&messageID=' . $row['messageID'] . '&box=' . BOX_INCOMING;
    $nachrichten[] = $row;
  }

  // get user messages
  $query = 'SELECT m.messageID, p.name, m.messageClass, m.messageSubject AS betreff, m.messageTime ' .
           'FROM Message m ' .
           'LEFT JOIN Player p ' .
           'ON p.playerID = m.senderID ' .
           'WHERE ' .
           'recipientID = ' . $params->SESSION->user['playerID'] . ' ' .
           'AND recipientDeleted != 1 ' .
           'ORDER BY m.messageTime DESC, m.messageID DESC '.
           'LIMIT ' . intval($offset) . ',' . intval($row_count);

  if (!($dbresult = $db->query($query)))
    return array();

  while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $row['absender_empfaenger'] = empty($row['name']) ? "System" : $row['name'];
    $t = $row['messageTime'];    
    $row['datum'] = $t{6}.$t{7}  .".".
                    $t{4}.$t{5}  .".".
                    $t{0}.$t{1}  .
                    $t{2}.$t{3}  ." ".
                    $t{8}.$t{9}  .":".
                    $t{10}.$t{11}.":".
                    $t{12}.$t{13};
    $row['nachrichtenart'] = $config->messageClass[$row['messageClass']];
    $row['linkparams'] = '?modus=' . MESSAGESDETAIL . '&messageID=' . $row['messageID'] . '&box=' . BOX_INCOMING;
    $nachrichten[] = $row;
  }
  return $nachrichten;
}

/**
 *
 */

function messages_getOutgoingMessages($offset, $row_count){
  global $db, $params, $config;

  $nachrichtenart = "";
  foreach($config->messageClass AS $key => $value)
    $nachrichtenart .= 'WHEN ' . $key . ' THEN "' . $value . '" ';

  $query = 'SELECT ' .
           'm.messageID, ' .
           'IFNULL(p.name, "System") AS absender_empfaenger, ' .

           'CASE m.messageClass ' . $nachrichtenart .
           'ELSE "unbekannte Nachrichtenart" ' .
           'END AS nachrichtenart, ' .

           'm.messageSubject AS betreff, ' .
           'DATE_FORMAT(m.messageTime, "%d.%m.%Y %H:%i:%s") AS datum ' .

           'FROM Message m ' .
           'LEFT JOIN Player p ' .
           'ON p.playerID = m.recipientID ' .

           'WHERE ' .
           'senderID = ' . $params->SESSION->user['playerID'] . ' ' .
           'AND senderDeleted != 1 ' .
           'ORDER BY m.messageTime DESC '.
           'LIMIT ' . intval($offset) . ',' . intval($row_count);

 if (!($dbresult = $db->query($query))){
   return array();
 }

 $nachrichten = array();
 while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $row['linkparams'] = '?modus=' . MESSAGESDETAIL . '&messageID=' . $row['messageID'] . '&box=' . BOX_OUTGOING;
    array_push($nachrichten, $row);
 }
 return $nachrichten;
}

/**
 *
 */

function messages_deleteMessages($messageIDs){
  global $db, $params;

  // get valid messageIDs
  $IDs = implode($messageIDs, ", ");

  // delete all those IDs
  $sql = "UPDATE Message SET " .
         "senderDeleted    = senderDeleted    OR (senderID    = '{$params->SESSION->user['playerID']}'), " .
         "recipientDeleted = recipientDeleted OR (recipientID = '{$params->SESSION->user['playerID']}') " .
         "WHERE messageID IN ($IDs) AND messageClass != 1001";

  $dbresult = $db->query($sql);
  return $db->affected_rows();
}

/**
 *
 */

function messages_mailAndDeleteMessages($messageIDs){
  global $db, $params;

  // get valid messages
  $IDs = implode($messageIDs, ", ");

  $sql  = 'SELECT m.recipientID, m.senderID, p.name, m.messageSubject, m.messageText, '.
          'DATE_FORMAT(m.messageTime, "%d.%m.%Y %H:%i:%s") AS messageTime ' .
          'FROM `Message` m '.
          'LEFT JOIN Player p ON '.
          'IF (m.recipientID = '.$params->SESSION->user['playerID'].', m.senderID = p.playerID, m.recipientID = p.playerID) '.
          'WHERE messageID IN (' . $IDs . ') AND '.
          'IF (recipientDeleted = '.$params->SESSION->user['playerID'].', recipientDeleted = 0, senderDeleted = 0)';

  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()) return 0;

  // nun zusammen schreiben
  $mail = "";
  while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $mail .= sprintf("Von:     %s<br>An:      %s<br>Betreff: %s<br>Datum: %s<p>%s<p>".
                     "----------------------------------------------------------------------------------------<p>",
                     ($row['senderID']    == $params->SESSION->user['playerID'] ? $params->SESSION->user['name'] : $row['name']),
                     ($row['recipientID'] == $params->SESSION->user['playerID'] ? $params->SESSION->user['name'] : $row['name']),
                     $row['messageSubject'],
                     $row['messageTime'],
                     $row['messageText']);
  }

  if ($mail != ""){

    $mail = "<html><body>" . $mail . "</body></html>";

    // zip it
    require_once("zip.lib.php");
    $time_now = date("YmdHis", time());
    $zipfile = new zipfile();
    $zipfile->addFile($mail, "mail.".$time_now.".html");
    $mail = $zipfile->file();

    // put mail together
    $mail_from    = "noreply@uga-agga.de";
    $mail_subject = "Deine Uga-Agga InGame Nachrichten";
    $mail_text    = "Hallo,\n\n".
                    "du hast im Nachrichten Fenster auf den Knopf 'Mailen&löschen' ".
                    "gedrückt. Die dabei markierten Nachrichten werden dir nun mit dieser ".
                    "Email zugesandt. Um den Datenverkehr gering zu halten, ".
                    "wurden dabei deine Nachrichten komprimiert. Mit einschlägigen ".
                    "Programmen wie WinZip lässt sich diese Datei entpacken.".
                    "\n\nGruß, dein UA-Team";

    $filename = "mail.".$time_now.".zip";
    $filedata = chunk_split(base64_encode($mail));

    $mail_boundary = '=_' . md5(uniqid(rand()) . microtime());

    // create header
    $mime_type    = "application/zip-compressed";
    $mail_headers = "From: $mail_from\n".
                    "MIME-version: 1.0\n".
                    "Content-type: multipart/mixed; ".
                    "boundary=\"$mail_boundary\"\n".
                    "Content-transfer-encoding: 7BIT\n".
                    "X-attachments: $filename;\n\n";

    // hier fängt der normale mail-text an
    $mail_headers .= "--$mail_boundary\n".
                     "Content-Type: text/plain; charset=\"iso-8859-1\"\n\n".
                     $mail_text . "\n";

    // hier fängt der datei-anhang an
    $mail_headers .= "--$mail_boundary\n".
                     "Content-type: $mime_type; name=\"$filename\";\n".
                     "Content-Transfer-Encoding: base64\n".
                     "Content-disposition: attachment; filename=\"$filename\"\n\n".
                     $filedata;

    // gibt das ende der email aus
    $mail_headers .= "\n--$mail_boundary--\n";

    // und abschicken
    mail($params->SESSION->user['email2'], $mail_subject, "", $mail_headers);
  }
  return messages_deleteMessages($messageIDs);
}

///////////////////////////////////////////////////////////////////////////////

function getMessageDetail($messageID){
  global $db, $params;

  $query = 'SELECT ' .

           'm.messageSubject AS betreff, ' .

           'm.senderID AS senderID, ' .
           'm.recipientID AS empfaengerID, ' .

           'IFNULL(p.name, "System") AS dummy, ' .

           'DATE_FORMAT(m.messageTime, "%d.%m.%Y %H:%i:%s") AS datum, ' .
           'm.messageText AS nachricht, ' .

           'm.messageClass AS nachrichtenart ' .

           'FROM  Message m ' .
           'LEFT JOIN Player p ' .
           'ON IF(' . $params->SESSION->user['playerID'] . ' = m.senderID, p.playerID = m.recipientID, p.playerID = m.senderID) ' .

           'WHERE messageID = ' . $messageID . ' ' .
           'AND (recipientID  = ' . $params->SESSION->user['playerID'] .
                ' OR  senderID =  ' . $params->SESSION->user['playerID']  .
                ' OR  messageClass = 1001)';

  if (!($result = $db->query($query)))
    return array();
  if ($result->isEmpty())
    return array();

  $row = $result->nextRow(MYSQL_ASSOC);

  if ($row['senderID'] == $params->SESSION->user['playerID']){
    $row['sender']     = $params->SESSION->user['name'];
    $row['empfaenger'] = $row['dummy'];
  } else {
    $row['empfaenger'] = $params->SESSION->user['name'];
    $row['sender']     = $row['dummy'];
  }
  unset($row['dummy']);
  return $row;
}

function messages_insertMessageIntoDB($empfaenger, $betreff, $nachricht){
  global $db, $params, $config;

  // get Empfaenger ID
  $query = "SELECT playerID FROM Player WHERE name LIKE '" . $empfaenger . "'";
  if (!($result = $db->query($query)))
    return 0;
  if ($result->isEmpty())
    return 0;
  $row = $result->nextrow(MYSQL_ASSOC);
  $empfanger = $row["playerID"];

  $query = "INSERT INTO Message (recipientID, senderID, messageClass, messageSubject, messageText, messageTime) " .
           "VALUES (" .
           "'" . $empfanger . "', " .
           "'" . $params->SESSION->user['playerID'] . "', " .
           "'10', " .
           "'" . $betreff . "', " .
           "'" . $nachricht . "', " .
           "NOW()+0)";
  return $db->query($query);
}

function messages_sendSystemMessage($receiverID, $type, $betreff, $nachricht, $db){

  $query = "INSERT INTO Message (recipientID, messageClass, senderID, messageSubject, messageText, messageTime) " .
           "VALUES (" .
           "'$receiverID', " .
           "'$type', ".
           " 0, " .
           "'" . $betreff . "', " .
           "'" . $nachricht . "', " .
           "NOW()+0)";
  return $db->query($query);
}


// ADDED by chris--- for adressbook

function book_getEntries($playerID) {
  global $db, $params, $config;

  $query = "SELECT a.entry_playerID, p.name, p.tribe FROM adressbook a LEFT JOIN player p ON a.entry_playerID = p.playerID WHERE a.playerID = ".$playerID." ORDER BY p.tribe, p.name ";

  if (!($result = $db->query($query))) return;
  if ($result->isEmpty()) return;

  $entry_player = array();
  $i = 0;
  while ($row = $result->nextRow(MYSQL_ASSOC)) {
    $entry_player[id][$i] = $row['entry_playerID'];
    $entry_player[tribe][$i] = $row['tribe'];
    if ($row['name'] == "") $entry_player[name][$i] = "Spieler nicht auffindbar";
      else $entry_player[name][$i] = $row['name'];
    $i++;
  }

return $entry_player;
}

function book_deleteEntry ($playerID, $entry_playerID) {
  global $db, $params, $config;

  $query = "SELECT name FROM player WHERE playerID = ".$entry_playerID;
  if (!($result = $db->query($query))) return 4;

  $query = "DELETE FROM adressbook WHERE playerID = ".$playerID." AND entry_playerID = ".$entry_playerID;
  if (!($result = $db->query($query))) return 4;
    else return 3;

}

function book_newEntry($playerID, $newPlayerName) {
  global $db, $params, $config;

  if (!$newPlayerName) return 5;

  $query = "SELECT playerID FROM player WHERE name = '".$newPlayerName."'";
  if (!($result = $db->query($query))) return 6;
  if ($result->isEmpty()) return 1;
    else {
      $row = $result->nextRow(MYSQL_ASSOC);
      $entry_playerID = $row['playerID'];

      if ($params->SESSION->user['playerID'] == $entry_playerID) return 5;

      $query = "SELECT * FROM adressbook WHERE playerID = ".$playerID." AND entry_playerID = ".$entry_playerID;
      if (!($result = $db->query($query))) return 6;
      if (!$result->isEmpty()) return 2;
        else {
          $query = "INSERT INTO adressbook SET playerID = ".$playerID.", entry_playerID = ".$entry_playerID;
          if (!($result = $db->query($query))) return 6;
            else return 0;
      }
  }
}
?>
