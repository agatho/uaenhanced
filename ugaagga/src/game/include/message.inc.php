<?
/*
 * message.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class MessageClass {

  function getMessageClasses(){
    static $result = NULL;

    if ($result === NULL){
      $result = array(0 => _('Information'),
                      1 => _('Quest'),
                      2 => _('Sieg!'),
                      4 => _('Einheit ausgebildet'),
                      6 => _('Handelsbericht'),
                      7 => _('Rückkehr'),
                      8 => _('Stammesnachricht'),
                      9 => _('Wunder'),
                      10 => _('Benutzernachricht'),
                      11 => _('Spionage'),
                      12 => _('Artefakt'),
                      20 => _('Niederlage!'),
                      //25 => _('Wetter'),
                      //26 => _('Stammeswunder'),
                      //27 => _('Überraschendes Ereignis'),
                      99 => _('Uga-Agga Team'),
                      // special message class: can't be deleted, everybody can see
                      1001 => _('<b>ANKÜNDIGUNG</b>'));
    }

    return $result;
  }
}

/**
 *
 */
function messages_getIncomingMessagesCount(){
  global $db, $params;
  $query = 'SELECT COUNT(*) as num FROM Message '.
           'WHERE recipientID = ' .
           $params->SESSION->player->playerID .
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
           $params->SESSION->player->playerID .
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

  // get message classes
  $uaMessageClass = MessageClass::getMessageClasses();

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
    $row['absender_empfaenger'] = empty($row['name']) ? _('System') : $row['name'];
    $t = $row['messageTime'];
    $row['datum'] = $t{6}.$t{7}  .".".
                    $t{4}.$t{5}  .".".
                    $t{2}.$t{3}  ." ".
                    $t{8}.$t{9}  .":".
                    $t{10}.$t{11}.":".
                    $t{12}.$t{13};
    $row['nachrichtenart'] = $uaMessageClass[$row['messageClass']];
    $row['linkparams'] = '?modus=messagesdetail&amp;messageID=' . $row['messageID'] . '&amp;box=' . BOX_INCOMING;
    $nachrichten[] = $row;
  }

  // get user messages
  $query = 'SELECT m.messageID, m.flag, p.name, m.messageClass, m.messageSubject AS betreff, m.messageTime, SIGN(m.read) as `read` ' .
           'FROM Message m ' .
           'LEFT JOIN Player p ' .
           'ON p.playerID = m.senderID ' .
           'WHERE ' .
           'recipientID = ' . $params->SESSION->player->playerID . ' ' .
           'AND recipientDeleted != 1 ' .
           'ORDER BY m.messageTime DESC, m.messageID DESC '.
           'LIMIT ' . intval($offset) . ',' . intval($row_count);

  if (!($dbresult = $db->query($query)))
    return array();

  while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $row['absender_empfaenger'] = empty($row['name']) ? _('System') : $row['name'];
    $t = $row['messageTime'];
    $row['datum'] = $t{6}.$t{7}  .".".
                    $t{4}.$t{5}  .".".
                    $t{2}.$t{3}  ." ".
                    $t{8}.$t{9}  .":".
                    $t{10}.$t{11}.":".
                    $t{12}.$t{13};
    $row['nachrichtenart'] = $uaMessageClass[$row['messageClass']];
    $row['linkparams'] = '?modus=messagesdetail&amp;messageID=' . $row['messageID'] . '&amp;box=' . BOX_INCOMING;
    $row[($row['flag'] ? 'FLAGGED' : 'UNFLAGGED') . '/id'] = $row['messageID'];
    $nachrichten[] = $row;
  }
  return $nachrichten;
}

/**
 *
 */

function messages_getOutgoingMessages($offset, $row_count){
  global $db, $params, $config;

  // get message classes
  $uaMessageClass = MessageClass::getMessageClasses();

  $nachrichtenart = "";
  foreach($uaMessageClass AS $key => $value)
    $nachrichtenart .= 'WHEN ' . $key . ' THEN "' . $value . '" ';

  $query = 'SELECT ' .
           'm.messageID, ' .
           'IFNULL(p.name, "' . _('System') . '") AS absender_empfaenger, ' .

           'CASE m.messageClass ' . $nachrichtenart .
           'ELSE "'._('unbekannte Nachrichtenart').'" ' .
           'END AS nachrichtenart, ' .

           'm.messageSubject AS betreff, ' .
           'DATE_FORMAT(m.messageTime, "%d.%m.%y %H:%i:%s") AS datum, ' .
           'SIGN(m.read) as `read` ' .

           'FROM Message m ' .
           'LEFT JOIN Player p ' .
           'ON p.playerID = m.recipientID ' .

           'WHERE ' .
           'senderID = ' . $params->SESSION->player->playerID . ' ' .
           'AND senderDeleted != 1 ' .
           'ORDER BY m.messageTime DESC '.
           'LIMIT ' . intval($offset) . ',' . intval($row_count);

 if (!($dbresult = $db->query($query))){
   return array();
 }

 $nachrichten = array();
 while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $row['linkparams'] = '?modus=messagesdetail&amp;messageID=' . $row['messageID'] . '&amp;box=' . BOX_OUTGOING;

    // FIXME
    unset($row['read']);

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
         "senderDeleted    = senderDeleted    OR (senderID    = '{$params->SESSION->player->playerID}'), " .
         "recipientDeleted = recipientDeleted OR (recipientID = '{$params->SESSION->player->playerID}') " .
         "WHERE messageID IN ($IDs) AND messageClass != 1001";

  $dbresult = $db->query($sql);
  return $db->affected_rows();
}

/**
 * Delete all messages of a given box.
 */

function messages_deleteAllMessages($boxID, $messageClass = FALSE){
  global $db, $params;

  if ($messageClass == -2) // no class selected
    return 0;
  
  switch ($boxID) {

    case BOX_INCOMING:
      $deletor = 'recipient';
      break;

    case BOX_OUTGOING:
      $deletor = 'sender';
      break;

    default:
      return 0;
  }

  $playerID = $params->SESSION->player->playerID;
  $uaMessageClass = MessageClass::getMessageClasses();

  // messageClass set
  if (isset($uaMessageClass[$messageClass])) {
    $sql = sprintf('UPDATE Message SET '.
                   'senderDeleted    = senderDeleted    OR (senderID    = %d), '.
                   'recipientDeleted = recipientDeleted OR (recipientID = %d) '.
                   'WHERE messageClass != 1001 AND messageClass = %d '.
                   'AND %sID = %d',
                   $playerID, $playerID, $messageClass, $deletor, $playerID);

  } else {
    $sql = sprintf('UPDATE Message SET '.
                   'senderDeleted    = senderDeleted    OR (senderID    = %d), '.
                   'recipientDeleted = recipientDeleted OR (recipientID = %d) '.
                   'WHERE messageClass != 1001 '.
                   'AND %sID = %d',
                   $playerID, $playerID, $deletor, $playerID);
  }

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
          'IF (m.recipientID = '.$params->SESSION->player->playerID.', m.senderID = p.playerID, m.recipientID = p.playerID) '.
          'WHERE messageID IN (' . $IDs . ') AND '.
          'IF (recipientDeleted = '.$params->SESSION->player->playerID.', recipientDeleted = 0, senderDeleted = 0)';

  $dbresult = $db->query($sql);
  if (!$dbresult || $dbresult->isEmpty()) return 0;



  $exporter = new MessageExporter();
  while($record = $dbresult->nextRow(MYSQL_ASSOC))
    $exporter->add(new Message($record));
  $exporter->send($params->SESSION->player->email2);

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
           'ON IF(' . $params->SESSION->player->playerID . ' = m.senderID, p.playerID = m.recipientID, p.playerID = m.senderID) ' .

           'WHERE messageID = ' . $messageID . ' ' .
           'AND (recipientID  = ' . $params->SESSION->player->playerID .
                ' OR  senderID =  ' . $params->SESSION->player->playerID  .
                ' OR  messageClass = 1001)';

  if (!($result = $db->query($query)))
    return array();
  if ($result->isEmpty())
    return array();

  $row = $result->nextRow(MYSQL_ASSOC);

  if ($row['senderID'] == $params->SESSION->player->playerID){
    $row['sender']     = $params->SESSION->player->name;
    $row['empfaenger'] = $row['dummy'];
  } else {
    $row['empfaenger'] = $params->SESSION->player->name;
    $row['sender']     = $row['dummy'];
  }
  unset($row['dummy']);

  // mark as read
  $db->query(sprintf('UPDATE Message SET `read` = `read` + 1 '.
                     'WHERE messageID = %d AND recipientID = %d',
                     $messageID, $params->SESSION->player->playerID));

  return $row;
}

function messages_insertMessageIntoDB($empfaenger, $betreff, $nachricht){
  global $db, $params, $config;

  // get Empfaenger ID
  $query = "SELECT playerID FROM Player WHERE name = '" . mysql_real_escape_string($empfaenger) . "'";
  if (!($result = $db->query($query)))
    return 0;
  if ($result->isEmpty())
    return 0;
  $row = $result->nextrow(MYSQL_ASSOC);
  $empfanger = $row["playerID"];

  $query = "INSERT INTO Message (recipientID, senderID, messageClass, messageSubject, messageText, messageTime) " .
           "VALUES (" .
           "'" . $empfanger . "', " .
           "'" . $params->SESSION->player->playerID . "', " .
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

function messages_createSubject($subject) {

  $result = preg_match('/^Re(\((\d*)\))?:(.*)$/i', $subject, $sub);

  // no 'Re:'
  if ($result == 0)
    return 'Re: ' . $subject;

  // 'Re(x):'
  else if (strlen($sub[1]))
    return sprintf('Re(%d): %s', 1 + (int)$sub[2], $sub[3]);

  // 'Re:'
  else
    return sprintf('Re(2): %s', $sub[3]);
}

/**
 * TODO
 */
function messages_flag($mID) {

  global $db, $params;

  // prepare query

  // just a single message?
  if (!is_array($mID))
    $sql = sprintf('UPDATE Message SET flag = 1 WHERE flag = 0 AND '.
                   'recipientID = %d AND messageID = %d',
                   $params->SESSION->player->playerID, $mID);

  else
    $sql = sprintf('UPDATE Message SET flag = 1 WHERE flag = 0 AND '.
                   'recipientID = %d AND messageID IN (%s)',
                   $params->SESSION->player->playerID, implode($mID, ','));

  $db->query($sql);
}

/**
 * TODO
 */
function messages_unflag($mID) {

  global $db, $params;

  // prepare query

  // just a single message?
  if (!is_array($mID))
    $sql = sprintf('UPDATE Message SET flag = 0 WHERE flag = 1 AND '.
                   'recipientID = %d AND messageID = %d',
                   $params->SESSION->player->playerID, $mID);

  else
    $sql = sprintf('UPDATE Message SET flag = 0 WHERE flag = 1 AND '.
                   'recipientID = %d AND messageID IN (%s)',
                   $params->SESSION->player->playerID, implode($mID, ','));

  $db->query($sql);
}

/**
 * This class stores all properties of an ingame message.
 *
 */

class Message {

  var $sender;
  var $recipient;
  var $time;
  var $subject;
  var $text;

  function Message($record){
    global $params;

    $playerID = $params->SESSION->player->playerID;

    $this->sender    = ($record['senderID'] == $playerID ?
                        $params->SESSION->player->name :
                        $record['name']);
    $this->recipient = ($record['recipientID'] == $playerID ?
                        $params->SESSION->player->name :
                        $record['name']);
    $this->time      = $record['messageTime'];
    $this->subject   = $record['messageSubject'];
    $this->text      = $record['messageText'];
  }
}

/**
 * This class exports all added messages
 * TODO: has to be refactored
 *
 */

class MessageExporter {

  var $messages = array();

  function MessageExporter(){
  }

  function add($message){
    $this->messages[] = $message;
  }

  function send($recipient){

    // if there are no messages, dont do anything
    if (!sizeof($this->messages))
      return;

    // concatenate those messages
    $mail = "";
    foreach ($this->messages as $message)
      $mail .= sprintf('%s: %s<br />'.
                       '%s: %s<br />'.
                       '%s: %s<br />'.
                       '%s: %s<br />'.
                       '%s<br /><hr />',
                       _('Absender'), $message->sender,
                       _('Empfänger'), $message->recipient,
                       _('Datum'), $message->time,
                       _('Betreff'), $message->subject,
                       $message->text);

    // add headers
    $mail = "<html><body>" . $mail . "</body></html>";

    // zip it
    require_once("zip.lib.php");
    $time_now = date("YmdHis", time());
    $zipfile = new zipfile();
    $zipfile->addFile($mail, "mail.".$time_now.".html");
    $mail = $zipfile->file();

    // put mail together
    $mail_from    = "noreply@uga-agga.de";

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
                     _("Hallo,\n\ndu hast im Nachrichten Fenster auf den Knopf 'Mailen&löschen' gedrückt. Die dabei markierten Nachrichten werden dir nun mit dieser Email zugesandt. Um den Datenverkehr gering zu halten, wurden dabei deine Nachrichten komprimiert. Mit einschlägigen Programmen wie WinZip lässt sich diese Datei entpacken.\n\nGruß, dein UA-Team") . "\n";

    // hier fängt der datei-anhang an
    $mail_headers .= "--$mail_boundary\n".
                     "Content-type: $mime_type; name=\"$filename\";\n".
                     "Content-Transfer-Encoding: base64\n".
                     "Content-disposition: attachment; filename=\"$filename\"\n\n".
                     $filedata;

    // gibt das ende der email aus
    $mail_headers .= "\n--$mail_boundary--\n";

    // und abschicken
    mail($recipient, _('Deine Uga-Agga InGame Nachrichten'), "", $mail_headers);
  }
}
?>
