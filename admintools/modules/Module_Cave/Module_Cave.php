<?
/*
 * Module_Cave.php - 
 * Copyright (c) 2007  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once("Module_Base.lib.php");
require_once("Menu.lib.php");
require_once("Menu_Item.lib.php");

class Module_Cave extends Module_Base {

  var $error;
  var $resource;
  var $building;
  var $unit;
  var $defense;
  var $cave;

  function Module_Cave(){

    $this->modi[] = 'cave_search';
    $this->modi[] = 'cave_show';
    $this->modi[] = 'cave_modify';

    $this->error    = false;
    $this->resource = array();
    $this->building = array();
    $this->unit     = array();
    $this->defense  = array();
    $this->Cave     = array();
  }

  function getContent($modus){

    $content = "";
    switch ($modus){
      default:
      case 'cave_search':
        $content = $this->_search();
        break;
      case 'cave_show':
        $content = $this->_show();
        break;
      case 'cave_modify':
        $content = $this->_modify();
        break;
    }
    return $content;
  }

  function getMenu(){

    $menu = new Menu($this->getName());
    $menu->addItem(new Menu_Item("?modus=cave_search", "find"));
    return $menu->getMenu();
  }

  function getName(){

    return "Cave";
  }

  function _search($feedback = NULL){

    $template = tmpl_open("modules/Module_Cave/templates/search.ihtml");
    if ($feedback) tmpl_set($template, '/MESSAGE/message', $feedback);
    return tmpl_parse($template);
  }
  
  function _show(){

    global $db_game, $params;

    $this->_getDetails();
    if (empty($this->Cave)){
      return $this->_search('Could not find cave');
    }

    $template = tmpl_open("modules/Module_Cave/templates/show.ihtml");

    tmpl_set($template, array('CaveID'        => $this->Cave['caveID'],
                              'name'          => $this->Cave['name'],
                              'xCoord'        => $this->Cave['xCoord'],
                              'yCoord'        => $this->Cave['yCoord'],
                              'PlayerName'    => !empty($this->Cave['PlayerName']) ? $this->Cave['PlayerName'] : 'Dies ist eine freie H�hle',

                              'SECURECAVE'            => ($this->Cave['secureCave'] == TRUE) ? array('iterate' => '') : NULL,
                              'secureCaveValue'       => !empty($this->Cave['secureCave']) ? 1 : 0,

                              'TAKEOVERABLE'          => $this->Cave['takeoverable'] ? array('iterate' => '') : NULL,
                              'takeoverableValue'     => !empty($this->Cave['takeoverable']) ? 1 : 0,

                              'STARTINGPOSITION'      => $this->Cave['starting_position'] ? array('iterate' => '') : NULL,
                              'startingpositionValue' => !empty($this->Cave['starting_position']) ? 1 : 0,
    ));

    // init game rules
    $this->_get_game_rules();

    // parse all...
    $this->_print('resource', 'resourceID',       '/RESOURCE', $template);
    $this->_print('building', 'buildingID',       '/BUILDING', $template);
    $this->_print('unit',     'unitID',           '/UNIT',     $template);
    $this->_print('defense',  'defenseSystemID',  '/DEFENSE',  $template);

    // parse resource boni and factor
    foreach ($this->resource as $value){

      $BoniName = $value->dbFieldName . '_bonus';
      if (isset($this->Cave[$BoniName])){
        $other_ary[] = array( 'id'  	=> $BoniName,
                              'lang'	=> !empty($value->name) ? 'Boni ' . $value->name : $BoniName,
                              'value' => $this->Cave[$BoniName]
                            );
      }

      $FactorName = $value->dbFieldName . '_factor';
      if (isset($this->Cave[$FactorName])){
        $other_ary[] = array( 'id'  	=> $FactorName,
                              'lang'	=> !empty($value->name) ? 'Faktor ' . $value->name : $FactorName,
                              'value'	=> $this->Cave[$FactorName]
                            );
      }
    }

    $other_values = $this->_get_other_values();
    foreach ($other_values as $value){
      if (isset($this->Cave[$value['name']])){
      
        $other_ary[] = array( 'id'  	=> $value['name'],
                              'lang'	=> $value['lang'],
                              'value' => $this->Cave[$value['name']]
                            );
      }
    }

    if (!empty($other_ary)){
      tmpl_iterate($template, '/OTHER');
      tmpl_set($template, '/OTHER', $other_ary);
    }

    if ($this->error){
      return $this->_search('Achtung!!! Datenbank oder Game Regel Fehler!!!');
    }

    return tmpl_parse($template);
  }

  function _modify(){

    global $db_game, $params;

    $this->_getDetails();
    if (empty($this->Cave)){
      return $this->_search('Could not find cave');
    }

    // init game rules
    $this->_get_game_rules();

    $sql_set = array();
    if ($params->name != $params->old_name){
      $sql_set[] = "name = '" . $params->name . "'";
    }

	if ($params->secureCave != $params->old_secureCave){
      $sql_set[] = "secureCave = '" . $params->secureCave . "'";
    }

	if ($params->takeoverable != $params->old_takeoverable){
      $sql_set[] = "takeoverable = '" . $params->takeoverable . "'";
    }

    if ($params->startingposition != $params->old_startingposition){
      $sql_set[] = "starting_position = '" . $params->startingposition . "'";
    }

    // read all...
    $this->_read('resource',  'resourceID',      $sql_set);
    $this->_read('building',  'buildingID',      $sql_set);
    $this->_read('unit',      'unitID',          $sql_set);
    $this->_read('defense',   'defenseSystemID', $sql_set);

    // read resource boni and factor
    foreach ($this->resource as $value){

      $BoniName = $value->dbFieldName . '_bonus';
      if (isset($this->Cave[$BoniName])){
        $new_name = 'other_' . $BoniName;
        $old_name = 'old_other_' . $BoniName;

        if (!isset($params->$new_name) || !isset($params->$old_name)){
          $this->error = true;
          break;
        }

        $new_value = floatval($params->$new_name);
        $old_value = floatval($params->$old_name);

        if ($new_value != $old_value){
          $sql_set[] = "{$BoniName} = '" . $new_value . "'";
        }
      }

      $FactorName = $value->dbFieldName . '_factor';
      if (isset($this->Cave[$FactorName])){
        $new_name = 'other_' . $FactorName;
        $old_name = 'old_other_' . $FactorName;

        if (!isset($params->$new_name) || !isset($params->$old_name)){
          $this->error = true;
          break;
        }

        $new_value = floatval($params->$new_name);
        $old_value = floatval($params->$old_name);

        if ($new_value != $old_value){
          $sql_set[] = "{$FactorName} = '" . $new_value . "'";
        }
      }
    }

    $other_values = $this->_get_other_values();
    foreach ($other_values as $value){
      $new_name = 'other_' . $value['name'];
      $old_name = 'old_other_' . $value['name'];

      if (!isset($params->$new_name) || !isset($params->$old_name) || !isset($this->Cave[$value['name']])){
        $this->error = true;
        break;
      }

      $new_value = floatval($params->$new_name);
      $old_value = floatval($params->$old_name);

      if ($new_value != $old_value){
        $sql_set[] = "{$value['name']} = '" . $new_value . "'";
      }
    }

    if ($this->error){
      $retval = 'Achtung!!! Fehler in den GameRules oder bei der Formularerstellung!!!';
    }
    else if (empty($sql_set)){
      $retval = "Es wurden keine �nderungen vorgenommen. H�hle wurde nicht aktualisiert.";
    }
    else {
      $sql_set = implode(', ', $sql_set);

      $query = sprintf("UPDATE Cave SET %s WHERE caveID = %d", $sql_set, $params->CaveID);
      $result = $db_game->query($query);

      if (!$result){
        $retval = sprintf("Fehler beim H�hle aktualisiern.<br />%s", $db_game->get_error());
      }
      else {
        $retval = "H�hle wurde erfolgreich aktualisiert.";
      }
    }

    return $this->_search($retval);
  }

  function _getDetails(){

    global $db_game, $params;

    if ($params->CaveID){
      $sql_where = sprintf("Cave.CaveID = %d", $params->CaveID);
    }
    else if ($params->CaveName){
      $sql_where = sprintf("Cave.name = '%s'", $params->CaveName);
    }
    else if ($params->xCoord && $params->yCoord){
      $sql_where = sprintf("Cave.xCoord = %d AND Cave.yCoord = %d", $params->xCoord, $params->yCoord);
    }
    else {
      return FALSE;
    }

    // get cave values
    $query = sprintf("SELECT Cave.*, Player.name AS PlayerName FROM Cave LEFT JOIN Player ON Cave.playerID = Player.playerID WHERE %s", $sql_where);
    $result = $db_game->query($query);

    if (!$result)
      return FALSE;

    if ($result->isEmpty())
      return FALSE;

    $this->Cave  = $result->nextRow();

    return TRUE;
  }

  function _print($Type, $IDName, $TemplateName, &$template){

    if (empty($this->$Type) || empty($IDName) || empty($TemplateName)  || $this->error){
      return FALSE;
    }

    foreach ($this->$Type AS $value){
      if (!isset($this->Cave[$value->dbFieldName])){
        $this->error = true;
        return FALSE;
      }

      $return_ary[] = array( 'id'  	  => $value->$IDName,
                             'lang'	  => !empty($value->name) ? $value->name : $value->dbFieldName,
                             'value'	=> $this->Cave[$value->dbFieldName]
                           );
    }

    if (!empty($return_ary)){
      tmpl_iterate($template, $TemplateName);
      tmpl_set($template, $TemplateName, $return_ary);
    }

    return TRUE;
  }

  function _read($Type, $IDName, &$sql_set){

    global $params;

    if (empty($this->$Type) || empty($IDName) || $this->error){
      return FALSE;
    }

    foreach ($this->$Type AS $value){

      $new_name = $Type . '_' . $value->$IDName;
      $old_name = 'old_' . $Type . '_' . $value->$IDName;

      if (!isset($params->$new_name) || !isset($params->$old_name) || !isset($this->Cave[$value->dbFieldName])){
        $this->error = true;
        return FALSE;
      }

      $new_value = intval($params->$new_name);
      $old_value = intval($params->$old_name);

      if ($new_value != $old_value){
        $sql_set[] = "{$value->dbFieldName} = '" . $new_value . "'";
      }
    }

    return TRUE;
  }

  function _get_game_rules(){

    global $cfg;

    // init game rules
    require_once($cfg['cfgpath'] . "game_rules.php");

    global $resourceTypeList, $buildingTypeList, $unitTypeList, $defenseSystemTypeList;
    $resourceTypeList = $buildingTypeList = $unitTypeList = $defenseSystemTypeList = '';

    init_Resources();
    init_Buildings();
    init_Units();
    init_DefenseSystems();

    $this->resource = $resourceTypeList;
    unset($resourceTypeList);

    $this->building = $buildingTypeList;
    unset($buildingTypeList);

    $this->unit = $unitTypeList;
    unset($unitTypeList);

    $this->defense = $defenseSystemTypeList;
    unset($defenseSystemTypeList);
  }

  function _get_other_values(){

    return array(
      array('name' => 'movement_cost',  'lang' => 'Bewegungskosten'),
      array('name' => 'movement_speed', 'lang' => 'Bewegungsgeschwindigkeit')
    );
  }
}
?>