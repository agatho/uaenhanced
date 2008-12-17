<?php
/*
 * db.inc - Super Class of database
 * Copyright (c) 2006  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * @author  David Unger
 * @package    ualogin
 * @subpackage includes
 */

ini_set('mysql.connect_timeout', 5);

function _sql_type_cast($value, $quotes=true) {

  return
    is_string($value) ? ($quotes ? '\'' : '') . str_replace(array('\\\'', '\\"'), array('\'\'', '"'), addslashes($value)) . ($quotes ? '\'' : '') : (
    is_float($value) ? doubleval($value) : (
    is_integer($value) || is_bool($value) ? intval($value) : (
    '\'\''
  )));
}

function _sql_map_fields($key, $value) {

  return $key . ' = ' . _sql_type_cast($value);
}

class ualogin_db {

  var $connect_id;
  var $error = false;
  var $sql_update, $sql_fields, $sql_values;

  function ualogin_db() {

  }

  function connect($sqlserver, $sqluser, $sqlpassword, $database) {

    $this->user = $sqluser;
    $this->password = $sqlpassword;
    $this->server = $sqlserver;
    $this->dbname = $database;

    /**
     * connect
     */
    $this->connect_id = @mysql_connect($this->server, $this->user, $this->password);

    if ( $this->connect_id ) {
      if ( $this->dbname != "" ) {
        /**
         * select database
         */
        $dbselect = @mysql_select_db($this->dbname, $this->connect_id);

        if ( !$dbselect ) {
          /**
           * database error
           */
          $this->error(@mysql_errno($this->connect_id) . ': ' . @mysql_error($this->connect_id));
          @mysql_close($this->connect_id);
          $this->error = true;
          unset($this->connect_id);

          return false;
        }
      }

      return $this->connect_id;
    }
    else {
      /**
       * connection error
       */
      $this->error = true;
      unset($this->connect_id);

      return false;
    }
  }

  function close() {

    /**
     * close connection
     */
    if ( $this->connect_id ) {
      $return = @mysql_close($this->connect_id);
      unset($this->connect_id);

      return $return;
    }
    else {
      return false;
    }
  }

  function query($query) {

    if ( $this->connect_id && $this->error == false ) {
      $result = @mysql_query($query, $this->connect_id);

      /**
       * log sql error
       */
      if ( !$result ) {
        $this->error(@mysql_errno($this->connect_id) . ': ' . @mysql_error($this->connect_id));
        return false;
      }
      else {
        return $result;
      }
    }
    else {
      return false;
    }
  }

  /**
   * select something...
   */
  function numrows($query_id = 0) {

    return ( $query_id ) ? @mysql_num_rows($query_id) : false;
  }

  function nextid() {

    return ( $this->connect_id ) ? @mysql_insert_id($this->connect_id) : false;
  }

  function fetchrow($query_id = 0) {

    if( $query_id ) {
      return @mysql_fetch_array($query_id, MYSQL_ASSOC);
    }
    else {
      return false;
    }
  }

  function fetchobjekt($query_id = 0) {

    if( $query_id ) {
      return @mysql_fetch_object($query_id);
    }
    else {
      return false;
    }
  }

  function freeresult($query_id = 0) {

    if ( $query_id ) {
      @mysql_free_result($query_id, $this->connect_id);
			return true;
    }
    else {
      return false;
    }
  }

  /**
   * escape string
   */
  function sql_escape_string($str) {

    return _sql_type_cast((string) $str, false);
  }

  /**
   * prepare sql
   */
  function sql_statement(&$fields, $fields_inc='') {

    // init result
    $this->sql_fields = $this->sql_values = $this->sql_update = '';
    if ( empty($fields) && empty($fields_inc) ) {
      return;
    }

    // process
    $this->sql_fields = $this->sql_fields('fields', $fields);
    $this->sql_values = $this->sql_fields('values', $fields);
    $this->sql_update = $this->sql_fields('update', $fields, $fields_inc);
  }

  /**
   * built sql fields
   */
  function sql_fields($mode, &$fields, $fields_inc='') {

    switch ( $mode ) {
      case 'fields':
        return empty($fields) ? '' : implode(', ', array_keys($fields));
      case 'values':
        return empty($fields) ? '' : implode(', ', array_map('_sql_type_cast', array_values($fields)));
      case 'update':
        $inc = array();
        if ( !empty($fields_inc) ) {
          foreach ( $fields_inc as $field => $indent ) {
            if ( $indent != 0 ) {
              $inc[] = $field . ' = ' . $field . ($indent < 0 ? ' - ' : ' + ') . abs($indent);
            }
          }
        }
        return empty($fields) && empty($inc) ? '' : implode(', ', array_merge(empty($inc) ? array() : $inc, empty($fields) ? array() : array_map('_sql_map_fields', array_keys($fields), array_values($fields))));
      default:
        return '';
    }
  }

  /**
   * error function
   */
  function error($msg) {

    watchdog('Uga-Agga Modul', $msg, WATCHDOG_ERROR);
  }
}
