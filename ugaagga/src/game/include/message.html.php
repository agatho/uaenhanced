<?
/*
 * message.html.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('modules/Contacts/model/Contacts.php');

///////////////////////////////////////////////////////////////////////////////
// MESSAGES                                                                  //
///////////////////////////////////////////////////////////////////////////////

function messages_getMessages($caveID, $deletebox, $box){

  global $params, $config;

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'messagebox.ihtml');

  // Nachrichten löschen
  $deleted = 0;

  // checkboxes checked
  if (is_array($deletebox)){
    // mail and delete
    if (isset($params->POST->mail_and_delete))
      $deleted = messages_mailAndDeleteMessages($deletebox);
    // just delete
    else if (isset($params->POST->delete))
      $deleted = messages_deleteMessages($deletebox);
  }
  
  // delete all
  if (isset($params->POST->delete_all))
    $deleted = messages_deleteAllMessages($box, $params->POST->messageClass);

  // show number of deleted messages
  if ($deleted > 0)
    tmpl_set($template, '/STATUS_MESSAGE/status_message', sprintf(_('%d Nachricht(en) erfolgreich gelöscht.'), $deleted));

  // flag messages
  if (isset($params->POST->flag))
    messages_flag($params->POST->id);

  // unflag messages
  if (isset($params->POST->unflag))
    messages_unflag($params->POST->id);

  // verschiedene Boxes werden hier behandelt... //
  $boxes = array(BOX_INCOMING => array('boxname' => _('Posteingang'), 'von_an' => _('Absender')),
                 BOX_OUTGOING => array('boxname' => _('Postausgang'), 'von_an' => _('Empfänger')));

  if(!isset($boxes[$box])) $box = BOX_INCOMING;

  $classes = array();
  foreach (MessageClass::getMessageClasses() as $id => $text)
    if ($id != 1001)
      $classes[] = array('id' => $id, 'text' => $text);

  tmpl_set($template,
    array('/boxname'       => $boxes[$box]['boxname'],
          '/von_an'       => $boxes[$box]['von_an'],
          '/MESSAGECLASS' => $classes));
  
  /////////////////////////////////////////////////

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
  tmpl_set($template, array('message_min'   => $message_count == 0 ? 0 : $offset + 1,
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

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'messagesdetail.ihtml');

  if (!empty($messageID)){
    $message = getMessageDetail($messageID);

    if ($bild = $config->messageImage[$message['nachrichtenart']]){
      tmpl_set($template, 'BILD/bild', $bild);
      //unset($message['nachrichtenart']);
    }
    tmpl_set($template, $message);

    if ($message['sender'] != "System" && $box == BOX_INCOMING){

      $antworten = array('HIDDEN' => array(array('arg' => "modus",      'value' => NEW_MESSAGE),
                                           array('arg' => "caveID",     'value' => $caveID),
                                           array('arg' => "box",        'value' => BOX_INCOMING),
                                           array('arg' => "betreff",    'value' => messages_createSubject($message['betreff'])),
                                           array('arg' => "empfaenger", 'value' => $message['sender'])));
      $contacts = array('contact' => $message['sender']);
    }
    if ($message['nachrichtenart'] != 1001)
      $loeschen  = array('HIDDEN' => array(array('arg' => "modus",          'value' => MESSAGES),
                                           array('arg' => "caveID",         'value' => $caveID),
                                           array('arg' => "box",            'value' => $box),
                                           array('arg' => "deletebox[" .$messageID . "]", 'value' => $messageID)));

    tmpl_set($template, 'OBEN',  array('ANTWORTEN' => $antworten, 'LOESCHEN' => $loeschen, 'CONTACTS' => $contacts));
  }
  tmpl_set($template, 'linkbackparams', '?modus=messages&amp;box=' . $box);

  return tmpl_parse($template);
}

///////////////////////////////////////////////////////////////////////////////
// NEW_MESSAGE                                                               //
///////////////////////////////////////////////////////////////////////////////

function messages_newMessage($caveID){
  global $params, $config, $no_resource_flag;
  $no_resource_flag = 1;

  // get contacts model
  $contacts_model = new Contacts_Model();
  $contacts = $contacts_model->getContacts();

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'messageDialogue.ihtml');

  tmpl_set($template, array('sender'     => $params->SESSION->player->name,
                            'empfaenger' => unhtmlentities($params->POST->empfaenger),
                            'betreff'    => $params->POST->betreff,
                            'OPTION'     => $contacts));

  tmpl_set($template, 'HIDDEN', array(array('arg' => "box",    'value' => $params->POST->box),
                                      array('arg' => "caveID", 'value' => $caveID),
                                      array('arg' => "modus",  'value' => NEW_MESSAGE_RESPONSE)));

  tmpl_set($template, 'linkbackparams', '?modus=' . MESSAGES . '&amp;box=' . $params->POST->box);

  return tmpl_parse($template);
}

///////////////////////////////////////////////////////////////////////////////
// NEW_MESSAGE_RESPONSE                                                      //
///////////////////////////////////////////////////////////////////////////////

function messages_sendMessage($caveID) {

  global $no_resource_flag, $params, $config;
  $no_resource_flag = 1;
  $zeichen = 16384;

  $betreff    = $params->POST->betreff;
  $nachricht = $_POST["nachricht"];
  $nachricht = preg_replace("/(<)/", "_THIS_MUST_BE_LOWER_THEN_", $nachricht);
  $nachricht = preg_replace("/(>)/", "_THIS_MUST_BE_GREATER_THEN_", $nachricht);
  $nachricht = nl2br(clean($nachricht));
  $nachricht = preg_replace("/(_THIS_MUST_BE_LOWER_THEN_)/", "&lt;", $nachricht);
  $nachricht = preg_replace("/_THIS_MUST_BE_GREATER_THEN_/", "&gt;", $nachricht);
  
  // **** get recipient ****
  $contactID = $params->POST->contactID;

  // get recipient from contactlist
  $empfaenger = "";
  if ($contactID > 0){
    // get contacts model
    $contacts_model = new Contacts_Model();
    $contact = $contacts_model->getContact($contactID);
    $empfaenger = $contact['contactname'];

  // get recipient from textfield
  } else {
    $empfaenger = $params->POST->empfaenger;
  }

  if ($betreff == "") $betreff = _('&lt;leer&gt;');

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'messageResponse.ihtml');

  if (strlen($nachricht) > $zeichen) {
    tmpl_set($template, 'success', sprintf(_('Fehler! Nachricht konnte nicht verschickt werden! Stellen Sie sicher, dass die Nachricht nicht länger als %d Zeichen ist.'), $zeichen));
  }

  if (messages_insertMessageIntoDB($empfaenger, $betreff, $nachricht))  {
    tmpl_set($template, 'success', _('Ihre Nachricht wurde verschickt!'));
  } else {
    tmpl_set($template, 'success', _('Fehler! Nachricht konnte nicht verschickt werden! Stellen Sie sicher, dass es den angegebenen Empfänger gibt.'));
  }

  tmpl_set($template, 'linkbackparams', '?modus=messages&amp;box=' . $params->POST->box);

  return tmpl_parse($template);
}
?>
