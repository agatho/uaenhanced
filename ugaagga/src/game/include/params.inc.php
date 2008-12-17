<?
/*
 * params.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

function clean($string){
  return trim(htmlentities(strip_tags($string), ENT_QUOTES));
}

class POST{
  function Post(){
    $params = array_merge($_GET, $_POST);

    foreach ($params as $k=>$v){
      if (is_array($v)){
        $array = array();
        foreach ($v as $key => $values)
          $array[$key] = clean($values);
        $this->$k = $array;
      } else {
        $v = clean($v);
        $this->$k = $v;
      }
    }
  }
}

class Session{
  function Session(){
    foreach($_SESSION as $k=>$v)
      $this->$k = $v;
  }
}

class Params{
  function Params(){
    $this->POST = new post();
    $this->SESSION = new Session();
  }
}
?>