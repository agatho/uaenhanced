<?php


///////////////////////////////////////////////////////////////////////////////
// MESSAGES                                                                  //
///////////////////////////////////////////////////////////////////////////////

function messages_getMessages($caveID, $deletebox, $box){

  global $params, $config;

  $template = @tmpl_open('templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/messagebox.ihtml');

  // Nachrichten löschen
  $deleted = 0;

  if (is_array($deletebox)){
    if (!empty($params->POST->mail_and_delete))
      $deleted = messages_mailAndDeleteMessages($deletebox);
    else
      $deleted = messages_deleteMessages($deletebox);
  }
  if ($deleted > 0)
    tmpl_set($template, '/STATUS_MESSAGE/status_message', $deleted . " Nachricht(en) erfolgreich gelöscht.");

  // verschiedene Boxes werden hier behandelt... //
  $boxes = array(BOX_INCOMING => array('boxname' => "Posteingang", 'von_an' => "Absender"),
                 BOX_OUTGOING => array('boxname' => "Postausgang", 'von_an' => "Empf&auml;nger"));

  if(!isset($boxes[$box])) $box = BOX_INCOMING;

  tmpl_set($template, 'CHANGEBOX/BOX', array(
           array('name' => "Posteingang",
                 'value' => BOX_INCOMING,
                 'selected' => ($box == BOX_INCOMING ? "selected" : "")),
           array('name' => "Postausgang",
                 'value' => BOX_OUTGOING,
                 'selected' => ($box == BOX_OUTGOING ? "selected" : ""))));
  
  tmpl_set($template, 'CHANGEBOX/HIDDEN',
           array(array('arg' => "modus",  'value' => MESSAGES)));

  tmpl_set($template, 'boxname',               $boxes[$box]['boxname']);
  tmpl_set($template, '/von_an', $boxes[$box]['von_an']);
  /////////////////////////////////////////////////

  tmpl_set($template, 'newmessagelinkparams', "?modus=" . NEW_MESSAGE . "&box=" . $box);

  // get row_count
  $row_count = 50;
  
  // errechne offset
  $offset = isset($params->POST->offset) ? intval($params->POST->offset) : 0;
  switch ($box){
    default:
    case BOX_INCOMING:
      $message_count = messages_getIncomingMessagesCount();
      break;
    case BOX_OUTGOING:
      $message_count = messages_getOutgoingMessagesCount();
      break;
  }

  // offset "normalisieren"
  if ($offset < 0)
    $offset = 0;
  if ($offset > $message_count - 1)
    $offset = $message_count;

  // Nachrichten einlesen und ausgeben
  $nachrichten = array();
  switch ($box){
    default:
    case BOX_INCOMING:
      $nachrichten = messages_getIncomingMessages($offset, $row_count);
      break;
    case BOX_OUTGOING:
      $nachrichten = messages_getOutgoingMessages($offset, $row_count);
      break;
  }
  tmpl_set($template, '/MESSAGE', $nachrichten);

  // vor-zurück Knopf
  if ($offset - $row_count >= 0)
    tmpl_set($template, '/PREVIOUS', array('offset' => $offset - $row_count,
                                           'box'    => $box,
                                           'modus'  => MESSAGES));
  else tmpl_set($template, '/PREVIOUS_DEACTIVATED/dummy', ' ');
  
  if ($offset + $row_count <= $message_count - 1)
    tmpl_set($template, '/NEXT', array('offset' => $offset + $row_count,
                                       'box'    => $box,
                                       'modus'  => MESSAGES));
  else tmpl_set($template, '/NEXT_DEACTIVATED/dummy', ' ');

  // Anzeige welche Nachrichten man sieht
  tmpl_set($template, array('message_min'   => $offset + 1,
                            'message_max'   => min($offset + $row_count, $message_count),
                            'message_count' => $message_count));  
 
  /////////////////////////////////////////////////


  tmpl_set($template, '/HIDDEN', array(array('arg' => "modus",  'value' => MESSAGES),
                                       array('arg' => "box",    'value' => $box)));

  return tmpl_parse($template);
}

///////////////////////////////////////////////////////////////////////////////
// MESSAGESDETAIL                                                            //
///////////////////////////////////////////////////////////////////////////////

function messages_showMessage($caveID, $messageID, $box){

  global $params, $no_resource_flag, $config;

  $no_resource_flag = 1;

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/messagesdetail.ihtml');

  if (!empty($messageID)){
    $message = getMessageDetail($messageID);

    if ($bild = $config->messageImage[$message['nachrichtenart']]){
      tmpl_set($template, 'BILD/bild', $bild);
      unset($message['nachrichtenart']);
    }
    tmpl_set($template, $message);

    if ($message['sender'] != "System"){
      $antworten = array('HIDDEN' => array(array('arg' => "modus",      'value' => NEW_MESSAGE),
                                           array('arg' => "caveID",     'value' => $caveID),
                                           array('arg' => "box",        'value' => $box),
                                           array('arg' => "betreff",    'value' => "Re: " . $message['betreff']),
                                           array('arg' => "empfaenger", 'value' => $message['sender'])));
// ADDED by chris--- for adressbook
      $adressbook_add = array('HIDDEN' => array(array('arg' => "modus",      'value' => MESSAGE_BOOK_ADD),
                                                array('arg' => "empfaenger", 'value' => $message['sender'])));
    }
    $loeschen  = array('HIDDEN' => array(array('arg' => "modus",          'value' => MESSAGES),
                                         array('arg' => "caveID",         'value' => $caveID),
                                         array('arg' => "box",            'value' => $box),
                                         array('arg' => "deletebox[" .$messageID . "]", 'value' => $messageID)));

    tmpl_set($template, 'OBEN',  array('ANTWORTEN' => $antworten, 'LOESCHEN' => $loeschen,
// ADDED by chris--- for adressbook
				       'BOOKADD' => $adressbook_add));
  }
  tmpl_set($template, 'linkbackparams', '?modus=' . MESSAGES . '&box=' . $box);

  return tmpl_parse($template);
}

///////////////////////////////////////////////////////////////////////////////
// NEW_MESSAGE                                                               //
///////////////////////////////////////////////////////////////////////////////

function messages_newMessage($caveID, $recipientID){
  global $params, $config, $no_resource_flag;
  $no_resource_flag = 1;

// ADDED by chris--- something for adressbook (recipient)
if (!$recipientID) $recipient = unhtmlentities($params->POST->empfaenger);
  else $recipient = $recipientID;

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/messageDialogue.ihtml');

  tmpl_set($template, array('sender'     => $params->SESSION->user['name'],
                            'empfaenger' => $recipient,
                            'betreff'    => $params->POST->betreff));

  tmpl_set($template, 'HIDDEN', array(array('arg' => "box",    'value' => $params->POST->box),
                                      array('arg' => "caveID", 'value' => $caveID),
                                      array('arg' => "modus",  'value' => NEW_MESSAGE_RESPONSE)));

  tmpl_set($template, 'linkbackparams', '?modus=' . MESSAGES . '&box=' . $params->POST->box);

// ADDED by chris--- for adressbook -------------------------------------------

  tmpl_set($template, 'show_book_modus', MESSAGE_BOOK);

  // Getting entries
 $playerlist = book_getEntries($params->SESSION->user['playerID']);

  // Show the player table
  for($i = 0; $i < sizeof($playerlist[id]); $i++) {

    $playername = $playerlist[name][$i]; // the current playername

    if ($playername != "Spieler nicht auffindbar") {
      tmpl_iterate($template, '/BOOKENTRY');
      tmpl_set($template, 'BOOKENTRY/book_entry', $playername);
    }

  }
// ----------------------------------------------------------------------


  return tmpl_parse($template);
}

///////////////////////////////////////////////////////////////////////////////
// NEW_MESSAGE_RESPONSE                                                      //
///////////////////////////////////////////////////////////////////////////////

function messages_sendMessage($caveID) {

  global $no_resource_flag, $params, $config;
  $no_resource_flag = 1;

// ADDED by chris--- for adressbook
if (!$params->POST->empfaenger) {
  if ($params->POST->empfaenger2) {
    if ($params->POST->empfaenger2 != "Bitte w&auml;hlen:") $empfaenger = str_replace(array('_', '%'), array('\_', '\%'), $params->POST->empfaenger2);
  }
} else

  $empfaenger = str_replace(array('_', '%'), array('\_', '\%'), $params->POST->empfaenger);
  $betreff    = $params->POST->betreff;
  $nachricht  = nl2br($params->POST->nachricht);

  if ($betreff == "") $betreff = "&lt;leer&gt;";

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/messageResponse.ihtml');

  if (messages_insertMessageIntoDB($empfaenger, $betreff, $nachricht))  {
    tmpl_set($template, 'success', 'Ihre Nachricht wurde verschickt!');
  } else {
    tmpl_set($template, 'success', 'Fehler! Nachricht konnte nicht verschickt werden!' .
                                    'Stellen Sie sicher, dass es den angegebenen Empf&auml;nger ' .
                                    'gibt.');
  }

  tmpl_set($template, 'linkbackparams', '?modus=' . MESSAGES . '&box=' . $params->POST->box);

  return tmpl_parse($template);
}
?>
