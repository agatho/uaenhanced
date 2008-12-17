<?
/*
 * profile.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

################################################################################

/**
 * This function delegates the task at issue to the respective function.
 */

function profile_main($caveID, $meineHoehlen){
  global $params;

  // initialize return value
  $result = '';

  // get current task
  $task = $params->POST->task;

  // connect to login db
  $db_login = db_connectToLoginDB();
  if (!$db_login) page_dberror();

  switch ($task){

    // show main page
    default:
      $result = profile_show($db_login);
      break;

    // change cave page
    case 'change':
      $result = profile_change($db_login);
      break;
  }

  return $result;
}


################################################################################


function profile_show($db_login, $feedback = NULL){
  global $params;

  // get login data
  $playerData = profile_getPlayerData($db_login);
  if (!$playerData) page_dberror();

  // open template
  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'profile.ihtml');

  // show message
  if ($feedback)
    tmpl_set($template, '/MESSAGE/message', $feedback);

  // show the profile's data
  profile_fillUserData($template, $playerData);

  return tmpl_parse($template);
}


################################################################################


/** This function gets the players data out of the game and login
 *  database.
 */
function profile_change($db_login){
  global $params;

  // proccess form data
  $message = profile_update($db_login);

  // update player's data
  page_refreshUserData();

  // show new data
  return profile_show($db_login, $message);
}


################################################################################


/** This function gets the players data out of the game and login
 *  database.
 */
function profile_getPlayerData($db_login){
  global $params, $db;

  // get playerID
  $playerID = $params->SESSION->player->playerID;

  // get game data
  $query  = sprintf('SELECT * FROM Player WHERE playerID = %d', $playerID);
  $r_game = $db->query($query);
  if (!$r_game) return NULL;

  // get login data
  $query   = sprintf('SELECT * FROM Login WHERE LoginID = %d', $playerID);
  $r_login = $db_login->query($query);
  if (!$r_login) return NULL;

  // empty records
  if ($r_login->isEmpty() || $r_game->isEmpty()) return NULL;

  // recode description
  $game = $r_game->nextRow();
  $game['description'] = str_replace("<br />", "", $game['description']);

  return array("game" => $game, "login" => $r_login->nextRow());
}


################################################################################


/** This function gets the players data out of the game and login
 *  database.
 */
function profile_fillUserData($template, $playerData){
  global $params, $config;

  ////////////// user data //////////////////////
  
  $p = new ProfileDataGroup(_('Benutzerdaten'));
  $p->add(new ProfileElementInfo(_('Name'), $playerData['game']['name']));
  $p->add(new ProfileElementInfo(_('Geschlecht'), $playerData['game']['sex']));
  $p->add(new ProfileElementInfo(_('Email'), $playerData['game']['email']));
  $p->add(new ProfileElementInput(_('Email 2'), $playerData['game']['email2'], 'data', 'email2', 30, 90));
  $p->add(new ProfileElementInput(_('Herkunft'), $playerData['game']['origin'], 'data', 'origin', 30, 30));
  $p->add(new ProfileElementInput(_('ICQ#'), $playerData['game']['icq'], 'data', 'icq', 15, 15));
  $p->add(new ProfileElementInput(_('Avatar URL'), $playerData['game']['avatar'], 'data', 'avatar', 60, 200));
  $p->add(new ProfileElementMemo(_('Beschreibung'), $playerData['game']['description'], 'data', 'description', 25, 8));

  tmpl_set($template, '/DATA_GROUP', $p->getTmplData());

  ////////////// L10N //////////////////////
  
  $uaLanguageNames = LanguageNames::getLanguageNames();
  
  $p = new ProfileDataGroup(_('Lokalisierung'));
  $slct = new ProfileElementSelection(_('Sprache'), 'data', 'language');
  foreach ($uaLanguageNames as $key => $text)
    $slct->add(new ProfileSelector($key, $text, $key == $params->SESSION->player->language));
  $p->add($slct);

  tmpl_iterate($template, '/DATA_GROUP');
  tmpl_set($template, '/DATA_GROUP', $p->getTmplData());

  ////////////// template //////////////////////

  $p = new ProfileDataGroup(_('Template auswählen'));
  $slct = new ProfileElementSelection(_('Template auswählen'), 'data', 'template');
  foreach ($config->template_paths as $key => $text)
    $slct->add(new ProfileSelector($key, $text, $key == $params->SESSION->player->template));
  $p->add($slct);

  tmpl_iterate($template, '/DATA_GROUP');
  tmpl_set($template, '/DATA_GROUP', $p->getTmplData());

  ////////////// gfxpath //////////////////////
  $p = new ProfileDataGroup(_('Grafikpack'));
  $p->add(new ProfileElementInput(sprintf(_('Pfad zum Grafikpack<br />(default:%s)'), DEFAULT_GFX_PATH), $playerData['game']['gfxpath'], 'data', 'gfxpath', 60, 200));

  tmpl_iterate($template, '/DATA_GROUP');
  tmpl_set($template, '/DATA_GROUP', $p->getTmplData());

  ////////////// password //////////////////////
  $p = new ProfileDataGroup(_('Passwort-Änderung'));
  $p->add(new ProfileElementPassword(_('Neues Passwort'),  '', 'password', 'password1', 15, 15));
  $p->add(new ProfileElementPassword(_('Neues Passwort - Wiederholung'), '', 'password', 'password2', 15, 15));

  tmpl_iterate($template, '/DATA_GROUP');
  tmpl_set($template, '/DATA_GROUP', $p->getTmplData());
}


################################################################################


/** This function sets the changed data specified by the user.
 */
function profile_update($db_login){
  global $params, $db;

  $playerID = $params->SESSION->player->playerID;
  $data     = $params->POST->data;
  $password = $params->POST->password;

  // list of fields, that should be inserted into the player record
  $fields = array("origin", "icq", "avatar", "description", "template", "language", "gfxpath", "email2");

  // validate language code
  $uaLanguageNames = LanguageNames::getLanguageNames();
  if (!array_key_exists($data['language'], $uaLanguageNames))
    unset($data['language']);
  
  // recode description
  $data['description'] = nl2br($data['description']);

  if ($set = db_makeSetStatementSecure($data, $fields)){
    $query = sprintf('UPDATE Player SET %s WHERE playerID = %d', $set, $playerID);
    if (!$db->query($query))
      return _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.');
  }

  // ***** now update the password, if it is set **** **************************
  if (strlen($password['password1'])){

    // typo?
    if (strcmp($password['password1'], $password['password2']) != 0)
      return _('Das Paßwort stimmt nicht mit der Wiederholung überein.');

    // password too short?
    if(!preg_match('/^\w{6,}$/', unhtmlentities($password['password1'])))
      return _('Das Passwort muss mindestens 6 Zeichen lang sein!');
      
    // set password
    $query = sprintf("UPDATE Login SET password = '%s' WHERE LoginID = %d", $password['password1'], $playerID);
    if (!$db_login->query($query))
      return _('Die Daten konnten gar nicht oder zumindest nicht vollständig aktualisiert werden.');
  }

  return _('Die Daten wurden erfolgreich aktualisiert.');
}


################################################################################


class ProfileDataGroup {

  var $heading;
  var $elements;

  function ProfileDataGroup($heading){
    $this->heading = $heading;
    $this->elements = array();
  }

  function add($element){
    $this->elements[] = $element;
  }

  function getTmplData(){
    $result = array('heading' => $this->heading);

    foreach ($this->elements as $element)
      $result[$element->getTmplContext()][] = $element->getTmplData();

    return $result;
  }
}


################################################################################


class ProfileElement {
  function getTmplContext(){
    return NULL;
  }
  function getTmplData(){
    return NULL;
  }
  function validate(){
    return true;
  }
}


################################################################################


class ProfileElementInfo extends ProfileElement {

  var $name;
  var $value;

  function ProfileElementInfo($name, $value){
    $this->name  = $name;
    $this->value = $value;
  }

  function getTmplContext(){
    return 'ENTRY_INFO';
  }

  function getTmplData(){
    return array('name' => $this->name, 'value' => $this->value);
  }
}


################################################################################


class ProfileElementInput extends ProfileElement {

  var $name;
  var $value;
  var $dataarray;
  var $dataentry;
  var $size;
  var $maxlength;

  function ProfileElementInput($name, $value, $dataarray, $dataentry, $size, $maxlength){
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->size       = $size;
    $this->maxlength  = $maxlength;
  }

  function getTmplContext(){
    return 'ENTRY_INPUT';
  }

  function getTmplData(){
    return array('name'      => $this->name,
                 'value'     => $this->value,
                 'dataarray' => $this->dataarray,
                 'dataentry' => $this->dataentry,
                 'size'      => $this->size,
                 'maxlength' => $this->maxlength);
  }
}


################################################################################


class ProfileElementPassword extends ProfileElementInput {

  function ProfileElementPassword($name, $value, $dataarray, $dataentry, $size, $maxlength){
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->size       = $size;
    $this->maxlength  = $maxlength;
  }

  function getTmplContext(){
    return 'ENTRY_INPUT';
  }
}


################################################################################


class ProfileElementMemo extends ProfileElement {

  var $name;
  var $value;
  var $dataarray;
  var $dataentry;
  var $cols;
  var $rows;

  function ProfileElementMemo($name, $value, $dataarray, $dataentry, $cols, $rows){
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->cols       = $cols;
    $this->rows       = $rows;
  }

  function getTmplContext(){
    return 'ENTRY_MEMO';
  }

  function getTmplData(){
    return array('name'      => $this->name,
                 'value'     => $this->value,
                 'dataarray' => $this->dataarray,
                 'dataentry' => $this->dataentry,
                 'cols'      => $this->cols,
                 'rows'      => $this->rows);
  }
}


################################################################################


class ProfileElementSelection extends ProfileElement {

  var $name;
  var $dataarray;
  var $dataentry;
  var $selectors;

  function ProfileElementSelection($name, $dataarray, $dataentry){
    $this->name       = $name;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->selectors  = $selectors;
  }

  function add($selector){
    $this->selectors[] = $selector;
  }

  function getTmplContext(){
    return 'ENTRY_SELECTION';
  }

  function getTmplData(){
    $result = array('name'      => $this->name,
                    'dataarray' => $this->dataarray,
                    'dataentry' => $this->dataentry);
    foreach ($this->selectors as $selector)
      $result[$selector->getTmplContext()][] = $selector->getTmplData();
    return $result;
  }
}


################################################################################


class ProfileSelector extends ProfileElement {

  var $text;
  var $key;
  var $selected;

  function ProfileSelector($key, $text, $selected = FALSE){
    $this->key      = $key;
    $this->text     = $text;
    $this->selected = $selected;
  }

  function getTmplContext(){
    return 'SELECTOR';
  }

  function getTmplData(){
    $result = array('text' => $this->text, 'key' => $this->key);
    if ($this->selected) $result['SELECTION'] = array('iterate' => '');
    return $result;
  }
}


################################################################################


class ProfileCheckbox extends ProfileElement {

  var $name;
  var $value;
  var $dataarray;
  var $dataentry;
  var $checked;

  function ProfileCheckbox($name, $value, $dataarray, $dataentry, $checked){
    $this->name       = $name;
    $this->value      = $value;
    $this->dataarray  = $dataarray;
    $this->dataentry  = $dataentry;
    $this->checked    = $checked;
  }

  function getTmplContext(){
    return 'ENTRY_CHECKBOX';
  }

  function getTmplData(){
    return array('name'      => $this->name,
                 'value'     => $this->value,
                 'dataarray' => $this->dataarray,
                 'dataentry' => $this->dataentry,
                 'checked'   => $this->checked);
  }
}
