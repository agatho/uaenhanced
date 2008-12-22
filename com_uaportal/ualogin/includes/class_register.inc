<?php
/*
 * class_register.inc - Register Account
 * Copyright (c) 2007 David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Show form.
 *
 * @author  David Unger
 * @package    ualogin
 * @subpackage includes
 */

define('REGISTER_SUCCESS',           1);
define('LOGIN_FAILURE',              0);

define('LOGIN_WRONG_SECURITY_CODE', -1);
define('LOGIN_ACTIVATE_FIRST',       2);
define('LOGIN_CREATED',              3);

error_reporting(E_ALL);

class ualogin_register {

  var $username, $sex, $easyStart, $password, $password2, $email, $email2, $agb, $regionID, $reg_code, $playerID, $actCode, $tribe, $caveID, $refererID;
  var $use_reg_code;

  /**
   * create class
   */
	function ualogin_register() {

	  $this->use_reg_code = variable_get("ualogin_reg_code_enabled", 1);

		$this->validate();
		$this->display();
	}

	/**
   * Display Form
   */
  function display() {

    $data = array();
    $disabled = ( variable_get("ualogin_disable_game", 1) == 1 ) ? 1 : variable_get("ualogin_disable_register", 1);
    if ( $disabled || !file_exists(drupal_get_path('module', 'ualogin') .'/game_rules.php') ) {
      $data['/DISABLED/message'] = 'Eine Registrierung ist zur Zeit nicht möglich.';
    }
    else {
      if ( $this->region_activate() > 1 ) {
        watchdog('Uga-Agga Modul', 'Aktivieren der Region fehlgeschlagen!', WATCHDOG_WARNING);
      }

      if ( !$this->checkCapacity() || !$regions = $this->get_regions() ) {
        $data['/DISABLED/message'] = t("Es wurde bisher nur eine begrenzte Anzahl von Accounts " .
                                        "bereitgestellt (derzeit: " . variable_get("ualogin_max_users", 0) .
                                        "). Es werden aber in unregelm&auml;&szlig;igen Zeitabst&auml;nden weitere " .
                                        "Kontingente folgen. ".
		                                    "<b>Wenn Sie im Kasten rechts Ihre Email ".
		                                    "registrieren, benachrichtigen wir Sie, sobald eine gr&ouml;&szlig;ere ".
		                                    " Menge neuer Accounts freigeschaltet werden.</b>".
		                                    "Sie k&ouml;nnen es auch sp&auml;ter noch einmal versuchen: ".
		                                    "Es werden zweimal am Tag inaktive Spieler gel&ouml;scht und deren ".
		                                    "Accounts f&uuml;r Neuanmeldungen frei gegeben.");
      }
      else {
        $easyStartEnabled   = variable_get("ualogin_easy_start_enabled", 1);
        $easyStartSelected  = variable_get("ualogin_easy_start_selected", 1);

        $data['/REGISTER/iterate'] = '';
        $data['/REGISTER/REGIONS/REGION'] = $regions;
        $data['/REGISTER/forbidden_names_list'] = variable_get('ualogin_forbidden_names', 'Uga, Agga');

        $data['/REGISTER/username'] = ( !empty($this->username) ) ? $this->username : '';
        $data['/REGISTER/email'] = ( !empty($this->email) ) ? $this->email : '';
        $data['/REGISTER/email2'] = ( !empty($this->email2) ) ? $this->email2 : '';

        if ( $easyStartEnabled ) {
          if ( $easyStartSelected ) {
            $data['/REGISTER/EASY_START_OPTION/SELECTED/iterate'] = '';
          }
          else {
            $data['/REGISTER/EASY_START_OPTION/UNSELECTED/iterate'] = '';
          }
        }

        if ( $this->use_reg_code ) {
          $data['/REGISTER/REG_CODE/iterate'] = '';
          $data['/REGISTER/REG_CODE/reg_code'] = ( !empty($this->reg_code) ) ? $this->reg_code : '';
        }

        if ( $this->refererID ) {
          $data['/REGISTER/REFERERID/iterate'] = '';
          $data['/REGISTER/REFERERID/refererID'] = ( !empty($this->refererID) ) ? $this->refererID : '';
        }
      }
    }

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('Register');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }

  function validate() {
    global $db_login, $input;

    if ( !file_exists(drupal_get_path('module', 'ualogin') .'/game_rules.php') ) {
      drupal_set_message(t('Es wurden keine Regeln gefunden.'));
      return FALSE;
    }

    $this->refererID = $input->read('refererID', TYPE_INT);

    if ( !isset($_POST['submit']) ) {
      return TRUE;
    }

    if ( !$this->checkCapacity() ) {
      return FALSE;
    }

    $this->username     = $input->read('username', TYPE_NO_HTML, true);
    $this->sex          = $input->read('sex', TYPE_NO_HTML, true);
    $this->easyStart    = $input->read('easyStart', TYPE_INT, true);
    $this->password     = $input->read('password', TYPE_NO_HTML, true);
    $this->password2    = $input->read('password2', TYPE_NO_HTML, true);
    $this->email        = $input->read('email', TYPE_NO_HTML, true);
    $this->email2       = $input->read('email2', TYPE_NO_HTML, true);
    $this->agb          = $input->read('agb', TYPE_INT, true);
    $this->regionID     = $input->read('regionID', TYPE_INT, true);
    $this->reg_code     = $input->read('reg_code', TYPE_NO_HTML, true);
    $this->caveID       = 0;

    /**
     * Validate agb
     */
    if ( !isset($this->agb) ) {
      drupal_set_message(t('Du mußt den Nutzungsbedingungen zustimmen.'));
      return FALSE;
    }

    /**
     * Validate Username
     */
    if ( !isset($this->username) || strlen($this->username) == 0 ) {
      drupal_set_message(t('Bitte einen Benutzername angeben.'));
      return FALSE;
    }

    if ( !preg_match('/^\w([\w .-]*\w|)$/', $this->username) ) {
       drupal_set_message(t('Der Benutzername enthält ungültige Zeichen.'));
      return FALSE;
    }

    if ( !$this->checkUsername() ) {
      drupal_set_message(t("Der Benutzername &auml;hnelt einem der verbotenen ".
                           "W&ouml;rter. Bitte w&auml;hlen Sie daher einen anderen ".
                           "Namen. Zu beachten ist: Namen, die eines der verbotenen ".
                           "W&ouml;rter enthalten oder einem dieser Worte &auml;hneln, ".
                           "sind nicht erlaubt.<br>Die Ausschlussliste umfasst ".
                           "zur Zeit: ". variable_get('ualogin_forbidden_names', 'Uga, Agga')));
      return FALSE;
    }

    /**
     * Validate Password
     */
    if ( !isset($this->password) || strlen($this->password) == 0 ) {
      drupal_set_message(t('Bitte ein Passwort angeben.'));
      return FALSE;
    }

    if ( !isset($this->password2) || strlen($this->password2) == 0 ) {
      drupal_set_message(t('Bitte das zweite Passwort angeben'));
      return FALSE;
    }

    if ( $this->password != $this->password2 || !preg_match('/^\w{6,}$/', $this->password) ) {
      drupal_set_message(t('Die Passw&ouml;rter stimmen nicht &uuml;berein oder das Passwort hat weniger als 6 Zeichen!'));
      return FALSE;
    }

    /**
     * Validate E-Mail
     */
    if ( !isset($this->email) || strlen($this->email) == 0 ) {
      drupal_set_message(t('Bitte eine E-Mail Adresse angeben'));
      return FALSE;
    }

    if ( !isset($this->email2) || strlen($this->email2) == 0 ) {
      drupal_set_message(t('Bitte die E-Mail Adresse bestätigen'));
      return FALSE;
    }

    if ( strtolower($this->email) != strtolower($this->email2) ) {
      drupal_set_message(t('Die Emailadressen stimmen nicht &uuml;berein.'));
      return FALSE;
    }

    if ( !$this->checkEmail() ) {
      drupal_set_message(t('Du hast keine g&uuml;ltige E-Mail Adresse eingegeben. Bitte &uuml;berprüfe die Eingabe.'));
      return FALSE;
    }

    /**
     * Validate sex
     */
    if ( $this->sex != 'm' && $this->sex != 'w' ) {
      drupal_set_message(t('Ung&uuml;ltiges Geschlecht!'));
      return TRUE;
    }

    /**
     * Validate region
     */
    if ( !isset($this->regionID) || $this->regionID < 1 ) {
      drupal_set_message(t('Fehler bei der Regions Auswahl.'));
      return FALSE;
    }

    /**
     * Validate reg_code
     */
    if ( $this->use_reg_code ) {
      if ( !isset($this->reg_code) || strlen($this->reg_code) == 0 ) {
        drupal_set_message(t('Bitte gib deinen Registrierungscode an.'));
        return FALSE;
      }
    }

    /**
     * Validate input form
     */
    if ( $this->userExists() ) {
      drupal_set_message(t('Der Benutzername <b>' . $this->username . '</b> ist schon vergeben.'));
      return FALSE;
    }

    if ( $this->userReserved() ) {
      drupal_set_message(t('Der Benutzername <b>' . $this->username . '</b> ist reserviert. '.
                           'Bitte geben Sie das korrekte Passwort aus der letzten Runde ein, '.
                           'wenn Sie diesen Namen wiederverwenden wollen.'));
      return FALSE;
    }

    if ( $this->emailExists() ) {
      drupal_set_message(t('Auf die Email <b>' .  $this->email . '</b> ist schon ein Benutzer registriert.'));
      return FALSE;
    }

    if ( $this->use_reg_code ) {
      if ( !$this->checkRegCode() ) {
        drupal_set_message(t('Der Registrierungscode <b>' .  $this->reg_code . '</b> ist ung&uuml;ltigt.'));
        return FALSE;
      }
    }

    if ( !$this->checkRegion() ) {
      drupal_set_message(t('Die gew&auml;hlte Region ist schon voll. Bitte w&auml;hle eine Region aus der Liste.'));
      return FALSE;
    }

    if ( !$this->createAccount() ) {
      drupal_set_message(t('Schwerer Fehler beim Anlegen des Benutzerkontos!'));
      return FALSE;
    }
    else {
      if ( $this->region_activate() > 1 ) {
        watchdog('Uga-Agga Modul', 'Aktivieren der Region fehlgeschlagen!', WATCHDOG_WARNING);
      }
    }

    drupal_set_message(t('Ihr Benutzerkonto wurde angelegt. Ihnen wurde ein Aktivierungskode zugesendet. '.
	                       'Bitte aktivieren Sie Ihr Konto innerhalb der n&auml;chsten 48 Stunden, '.
	                       'andernfalls wird es automatisch gel&ouml;scht.'));
    drupal_goto("ualogin/Activate");

    return TRUE;
  }

  function checkCapacity() {
    error_reporting(E_ALL);
	global $db_login;

    /**
     * Count all Users
     */
    $query = 'SELECT COUNT(*) AS n 
	          FROM agatho_login.Login';
    $result = $db_login->query($query);

    if ( $result == FALSE ) 
   {
      drupal_set_message(t('ua_dberror_register_1'));
      return FALSE;
    }

    $row = $db_login->fetchrow($result);
    $max = variable_get("ualogin_max_users", 0);

    return $row['n'] <= $max;
  }

  function checkEmail() {

    /**
     * check email and mail server
     */
     return true;
    if ( preg_match('/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*[a-z]+$/i', $this->email) ) {
      list(, $domain) = split('@', $this->email);

      if ( function_exists('checkdnsrr') ) {
        if ( !@checkdnsrr($domain, 'MX') ) {
          return false;
        }
      }

      $fp = @fsockopen($domain, 25, $errno, $errstr, 30);
      if ( !$fp ) {
        return false;
      }
      @fclose($fp);

      return true;
    }

    return false;
  }

  function checkRegCode() {
    global $db_login;

    /**
     * Select regcode
     */
    $query = 'SELECT regcodeID
               FROM reg_code
               WHERE regcode = \'' . $db_login->sql_escape_string($this->reg_code) . '\'
                 AND aktive = 1';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror_register_2'));
      return FALSE;
    }

    /**
     * regcode exists?
     */
    if ( $db_login->fetchrow($result) == 1 ) {
      return TRUE;
    }

    return FALSE;
  }

  function checkRegion() {
    global $db_game;

    /**
     * look for region being active and containg free starting caves
     */
    $query = 'SELECT Cave.caveID
               FROM Regions
                 INNER JOIN Cave ON Cave.regionID = Regions.regionID
               WHERE Regions.startRegion = 1
                 AND Cave.playerID = 0
                 AND Cave.starting_position > 0
                 AND Regions.regionID = ' . intval($this->regionID) . '
               ORDER BY RAND()
               LIMIT 0,1';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror3'));
      return FALSE;
    }

    $region = $db_game->fetchrow($result);

    if ( empty($region) ) {
      return FALSE;
    }

    $this->caveID = $region['caveID'];

    return TRUE;
  }

  function checkUsername() {

    $forbidden = explode(", ", variable_get('ualogin_forbidden_names', 'Uga, Agga'));

    /**
     * calculate the distance to forbidden names
     */
    $distance = 99.0;
    if ( !empty($forbidden) ) {
      foreach ( $forbidden AS $f ) {
        /**
         * username contains f
         */
        if ( stristr($this->username, $f) ) {
          return FALSE;
        }
        else {
          $ls = levenshtein($this->username, $f, 2, 1, 2);
          if ( $ls < $distance ) {
            $distance = $ls;
          }
        }
      }

      if ( $distance < 3 ) {
        return FALSE;
      }
    }

    return TRUE;
  }

  function createAccount() {
    global $db_login, $db_game;

    /**
     * select creation date
     */
    $query = 'SELECT created
               FROM LoginReserved
               WHERE user = \'' . $db_login->sql_escape_string($this->username) . '\'';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror4'));
      return FALSE;
    }

    $reserved = $db_login->fetchrow($result);

    if ( !$reserved ) {
      $created = gmdate("Y-m-d H:i:s", time());
    }
    else {
      $created = $reserved['created'];
    }

    /**
     * produce activation code
     */
    srand((double)microtime() * 1000000);
    $this->actCode = rand(100000, 999999);

    /**
     * get last playerID
     */
    $query = 'SELECT MAX(LoginID) AS total FROM Login';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror5'));
      return FALSE;
    }

    $row = $db_login->fetchrow($result);
    $this->playerID = $row['total'] + 1;

    /**
     * insert new player into login database
     */
    $fields = array(
      'LoginID'      => intval($this->playerID),
      'user'         => $this->username,
      'password'     => $this->password,
      'email'        => $this->email,
      'activationID' => intval($this->actCode),
      'activated'    => 0,
      'creation'     => date("YmdHis"),
      'created'      => $created,
    );
    $db_login->sql_statement($fields);
    $query = 'INSERT INTO Login
               (' . $db_login->sql_fields . ') VALUES(' . $db_login->sql_values . ')';
    if ( !$db_login->query($query) ) {
      drupal_set_message(t('ua_dberror6'));
      return FALSE;
    }

    /**
     * insert new player into game database
     */
    $fields = array(
      'playerID'            => intval($this->playerID),
      'email'               => $this->email,
      'email2'              => $this->email,
      'name'                => $this->username,
      'sex'                 => $this->sex,
      'created'             => $created,
      'template'            => 1,
      'takeover_max_caves'  => intval(variable_get("ualogin_takeover_max_caves", 6)),
      'SecureCaveCredits'   => intval(variable_get("ualogin_secure_cave_credits", 2)),
      'gfxpath'             => variable_get("ualogin_gfx_path", ''),
    );
    $db_game->sql_statement($fields);
    $query = 'INSERT INTO Player
               (' . $db_game->sql_fields . ') VALUES(' . $db_game->sql_values . ')';
    if ( !$db_game->query($query) ) {
        $query = 'DELETE FROM Login WHERE LoginID = ' . intval($this->playerID);
        $db_login->query($query);

        return FALSE;
    }

    /**
     * Create Cave
     */
    if ( !$this->createCave() ) {
      $query = 'DELETE FROM Player WHERE playerID = ' . intval($this->playerID);
      $db_game->query($query);

      $query = 'DELETE FROM Login WHERE LoginID = ' . intval($this->playerID);
      $db_login->query($query);

      return FALSE;
    }

    /**
     * insert welcome Message
     */
    $fields = array(
      'recipientID'     => intval($this->playerID),
      'senderID'        => 0,
      'messageClass'    => intval(variable_get("ualogin_welcome_message_class", 99)),
      'messageSubject'  => variable_get("ualogin_welcome_message_subjekt", 'Welcome'),
      'messageText'     => variable_get("ualogin_welcome_message_text", 'Fix Me'),
    );
    $db_game->sql_statement($fields);

    $query = 'INSERT INTO Message
               (' . $db_game->sql_fields . ') VALUES(' . $db_game->sql_values . ')';
    $db_game->query($query);

    /**
     * insert Verein Message
     */
    if (variable_get("ualogin_verein_send_message", 0)){
      $fields = array(
        'recipientID'     => intval($this->playerID),
        'senderID'        => 0,
        'messageClass'    => intval(variable_get("ualogin_verein_message_class", 99)),
        'messageSubject'  => variable_get("ualogin_verein_message_subjekt", 'Welcome'),
        'messageText'     => variable_get("ualogin_vereine_message_text", 'Fix Me'),
      );
      $db_game->sql_statement($fields);
      
      $query = 'INSERT INTO Message
                 (' . $db_game->sql_fields . ') VALUES(' . $db_game->sql_values . ')';
      $db_game->query($query);
    }

    if ( $this->use_reg_code ) {
      $query = 'UPDATE reg_code
                 SET aktive = 0
                 WHERE regcode = \'' . $db_login->sql_escape_string($this->reg_code) . '\'';
      $db_login->query($query);
    }

    if ( $this->refererID ) {
      $query = 'SELECT *
                 FROM Login
                 WHERE LoginID = ' . intval($this->refererID);
      $result = $db_login->query($query);

      if ( $result === FALSE ) {
        drupal_set_message(t('ua_dberror7'));
        return TRUE;
      }

      if ( $db_login->numrows($result) != 0 ) {
        $query = 'UPDATE Login
                   SET referer_count = referer_count + 1
                   WHERE LoginID = ' . intval($this->refererID);
        $db_login->query($query);

        $query = 'UPDATE Player
                   SET referer_count = referer_count + 1
                   WHERE playerID = ' . intval($this->refererID);
        $db_game->query($query);

        $fields = array(
          'playerID'  => intval($this->playerID),
          'refererID' => intval($this->refererID)
        );
        $db_login->sql_statement($fields);
        $query = 'INSERT INTO Referer
                   (' . $db_login->sql_fields . ') VALUES(' . $db_login->sql_values . ')';
        $db_login->query($query);
      }
    }

    $this->SendWelcomeMail();

    return TRUE;
  }

  function createCave() {
    global $db_game;

    $start_value = array();
    if ( $this->easyStart ) {
      /**
       * Select startvalue
       */
      $query = 'SELECT * FROM StartValue';
      $result = $db_game->query($query);

      if ( $result === FALSE ) {
        drupal_set_message(t('ua_dberror8'));
        return FALSE;
      }

      while ( $row = db_fetch_array($result) ) {
        $start_value[$row['dbFieldName']] = $row['value'];
      }
    }

    if ( !$this->easyStart || empty($start_value) ) {
      $item_array = explode("\n", variable_get('ualogin_start_setting', ''));

      foreach( $item_array as $id => $dummy ) {
        $item = explode(",", $item_array[$id]);
        $start_value[$item[0]] = intval($item[1]);
      }
    }

    if ( empty($start_value) ) {
	    drupal_set_message(t('Keine Start Ressourcen!'));
      return FALSE;
    }

    /**
     * get game rules
     */
    require_once(drupal_get_path('module', 'ualogin') .'/game_rules.php');
    global $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList;
    init_Buildings();
    init_Units();
    init_Resources();
    init_Sciences();
    init_DefenseSystems();

    $set = array();
    foreach ($resourceTypeList      AS $value) $set[ $value->dbFieldName ] = 0;
    foreach ($buildingTypeList      AS $value) $set[ $value->dbFieldName ] = 0;
    foreach ($unitTypeList          AS $value) $set[ $value->dbFieldName ] = 0;
    foreach ($scienceTypeList       AS $value) $set[ $value->dbFieldName ] = 0;
    foreach ($defenseSystemTypeList AS $value) $set[ $value->dbFieldName ] = 0;

    /**
     * generate cave value and update cave
     */
    $time = time() + ( variable_get('ualogin_beginner_protect', 336) * 60  * 60);
    $set['protection_end'] = date('YmdHis', $time);
    $set['secureCave'] = '1';
    $set['playerID'] = intval($this->playerID);

    foreach( $start_value as $name => $dummy ) {
      if ( isset($set[ $name ]) ) {
        $set[ $name ] = $start_value[ $name ];
      }
    }

    $db_game->sql_statement($set);
    $query = 'UPDATE Cave
               SET ' . $db_game->sql_update . '
               WHERE caveID = ' . intval($this->caveID);
    if ( !$db_game->query($query) ) {
      drupal_set_message(t('ua_dberror9'));
      return FALSE;
    }

    /**
     * write science start values to the player table
     */
    $set = array();
    foreach( $scienceTypeList as $value ) {
      if ( $start_value[ $value->dbFieldName ] ) {
        $set[ $value->dbFieldName ] = $start_value[ $value->dbFieldName ];
      }
      else {
        $set[ $value->dbFieldName ] = 0;
      }
    }

    $db_game->sql_statement($set);
    if( $db_game->sql_update ) {
       $query = 'UPDATE Player
                  SET ' . $db_game->sql_update . '
                  WHERE playerID = ' . intval($this->playerID);
      if ( !$db_game->query($query) ) {
        drupal_set_message(t('ua_dberror10'));
        return FALSE;
      }
    }

    return TRUE;
  }

  function emailExists() {
    global $db_login;

    /**
     * check if email exists
     */
    $query = 'SELECT email
               FROM Login
               WHERE email = \'' . $db_login->sql_escape_string($this->email) . '\'';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror11'));
      return TRUE;
    }

    if ( $db_login->numrows($result) != 0 ) {
      return TRUE;
    }

    return FALSE;
  }

  function get_regions() {
    global $db_game;

    /**
     * get active regions
     */
    $query = 'SELECT DISTINCT Regions.regionID, Regions.name AS regionName
               FROM Regions
                 INNER JOIN Cave ON Cave.regionID = Regions.regionID
               WHERE Regions.startRegion = 1
                 AND Cave.playerID = 0
                 AND Cave.starting_position > 0
               ORDER BY Regions.name';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror12'));
      return FALSE;
    }

    while ( $row  = $db_game->fetchrow($result) ) {
      $regions[ $row['regionID'] ] = $row;
      $regions[ $row['regionID'] ]['regionAll'] = 0;
      $regions[ $row['regionID'] ]['regionFull'] = 0;
    }

    if ( empty($regions) ) {
      return FALSE;
    }

    /**
     * count free caves
     */
    $query = 'SELECT regionID, count(*) AS Count
               FROM Cave
               WHERE Cave.starting_position > 0
                 AND regionID IN (' . implode(', ', array_keys($regions)) . ')
               GROUP BY regionID';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror13'));
      return FALSE;
    }

    while ( $row = $db_game->fetchrow($result) ) {
      $regions[ $row['regionID'] ]['regionAll'] = $row['Count'];
    }

    /**
     * count full caves
     */
    $query = 'SELECT regionID, count(*) AS Count
               FROM Cave
               WHERE Cave.playerID > 0
                 AND Cave.starting_position > 0
                 AND regionID IN (' . implode(', ', array_keys($regions)) . ')
               GROUP BY regionID';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror14'));
      return FALSE;
    }

    while ( $row = $db_game->fetchrow($result) ) {
      $regions[ $row['regionID'] ]['regionFull'] = $row['Count'];
    }

    /**
     * generate regions output
     */
    foreach ( $regions as $regionID => $dummy ) {
       $regions[ $regionID ]['regionName'] = utf8_encode($regions[ $regionID ]['regionName']);
       $regions[ $regionID ]['regionPercentage'] = round(($regions[ $regionID ]['regionFull'] / $regions[ $regionID ]['regionAll']) * 100, 0);
    }

    return $regions;
  }

  function region_activate() {
    global $db_game;

    /**
     * check if any region left
     */
    $query = 'SELECT count(*) AS count
               FROM Regions
               WHERE startRegion = 0';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      return TRUE;
    }

    $count = $db_game->fetchrow($result);

    if ( $count['count'] == 0 ) {
      return TRUE;
    }

    /**
     * get percentage
     */
    $percentage = $this->region_get_percentage();

    if ( $percentage < 0.95 ) {
      return TRUE; // perfectly fine, we don't need to do anything
    }

    if ( $percentage > 1 ) {
      return FALSE; // this indicates some sort of error, for sure
    }

    do {
      if ( !$this->region_update() ) {
        return FALSE;
      }
      $percentage = $this->region_get_percentage();
    }
    while ( $percentage > 0.95 && $percentage <= 1 );

    return ($percentage <= 1);
  }

  function region_update() {
    global $db_game;

    /**
     * get last region id
     */
/*
    $query = 'SELECT regionID AS id
               FROM Regions
               WHERE startRegion = 1
               ORDER BY regionID DESC
               LIMIT 1';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      return FALSE;
    }

    $last_region = $db_game->fetchrow($result);

    if ( !$last_region ) {
      return FALSE;
    }

    $regionID = $last_region['id'] + 1;
*/

    /**
     * check if region edit
     */
/*
    $query = 'SELECT regionID
               FROM Regions
               WHERE regionID = ' . intval($regionID);
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      return FALSE;
    }

    if ( $db_game->fetchrow($result) == 0 ) {
      return FALSE;
    }
*/
    $query = 'SELECT regionID AS id
               FROM Regions
               WHERE startRegion = 0
               ORDER BY regionID ASC
               LIMIT 1';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      return FALSE;
    }

    $new_region = $db_game->fetchrow($result);

    if ( !$new_region ) {
      return FALSE;
    }

    $regionID = $new_region['id'];

    /**
     * activate region
     */
    $query = 'UPDATE Regions SET startRegion = 1
               WHERE regionID = ' . intval($regionID);
    $db_game->query($query);

    return TRUE;
  }

  function region_get_percentage() {
    global $db_game;

    /**
     * get percentage
     */
    $query = 'SELECT ROUND(count(*) /
                 (SELECT count(*)
                   FROM Cave
                     INNER JOIN Regions ON Regions.regionID = Cave.regionID
                   WHERE Cave.starting_position > 0
                     AND Regions.startRegion = 1), 3) AS percentage
               FROM Cave
                 INNER JOIN Regions ON Regions.regionID = Cave.regionID
               WHERE Cave.starting_position > 0
                 AND Cave.playerID > 0
                 AND Regions.startRegion = 1';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      return FALSE;
    }

    $row  = $db_game->fetchrow($result);

    return is_null($row['percentage']) ? 2 : $row['percentage'];
  }

  function SendWelcomeMail() {
    global $base_url;

    /**
     * send welcome message
     */
    $mail = new ualogin_mail;
    $mail->replyto = $this->email;
    $mail->subject = "Ihr neuer Account bei " . $_SERVER['HTTP_HOST'];
    $mail->message = "Herzlich willkommen " . $this->username . "!\n\n".
                     "Hier Ihre Account-Informationen:\n".
                     "Spieler: " . $this->username . "\n".
                     "Passwort: " . $this->password . "\n".
                     "Aktivierungscode: " . $this->actCode . "\n\n".

                     "Sollte Ihr Email-Client dies unterstützen, k�nnen Sie zur Aktivierung ".
                     "auch direkt auf diesen Link klicken: ".

                     $base_url . "/?q=ualogin/Activate&username=" . $this->username . "&actcode=" . $this->actCode . "\n".

                     "Falls Ihr Browser den Link nicht unterstützt, melden Sie sich ".
                     "einfach mit Ihren Logindaten an. Sie werden dann beim ersten ".
                     "Versuch nach dem Aktivierungscode gefragt.\n".
                     "Sollten Sie Ihren Account nicht innerhalb den nächsten 48 Stunden aktivieren, ".
                     "wird er beim nächsten Aufräumen des Datenbestandes gelöscht.\n\n".
                     "Das Uga-Agga Team wünscht Ihnen viel Spaß beim Spielen!\n\n".
                     "Sollten Sie sich nicht bei dem Spiel angemeldet haben, ignorieren Sie diese Mail einfach.";
    if ( !$mail->send() ) {
      drupal_set_message(t('Beim Passwort verschicken ist ein Fehler aufgetreten'));
      return FALSE;
    }
    unset($mail);

    return TRUE;
  }

  function userExists() {
    global $db_login, $db_game;

    /**
     * check if user exists in login database
     */
    $query = 'SELECT user
               FROM Login
               WHERE user = \'' . $db_login->sql_escape_string($this->username) . '\'';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror15'));
      return TRUE;
    }

    $login_num = $db_login->numrows($result);

    /**
     * check if user exits in game database
     */
    $query = 'SELECT name
               FROM Player
               WHERE name = \'' . $db_game->sql_escape_string($this->username) . '\'';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror16'));
      return TRUE;
    }

    $game_num = $db_game->numrows($result);

    /**
     * user exists?
     */
    if ( $login_num != 0 || $game_num != 0 ) {
      return TRUE;
    }

    return FALSE;
  }

  function userReserved() {
    global $db_login;

    /**
     * check if user reserved
     */
    $query = 'SELECT user
               FROM LoginReserved
               WHERE user = \'' . $db_login->sql_escape_string($this->username) . '\'
                 AND password != \'' . $db_login->sql_escape_string($this->password) . '\'';
    $result = $db_login->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror17'));
      return TRUE;
    }

    if ( $db_login->numrows($result) != 0 ) {
      return TRUE;
    }

    return FALSE;
  }
}