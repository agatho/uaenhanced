<?
/*
 * params.inc.php - 
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the Affero General Public License as
 * published by Affero, Inc.; either version 1 of
 * the License, or (at your option) any later version.
 */

function clean($string){
  return trim(htmlentities(strip_tags($string), ENT_QUOTES));
}

class Params{
  
  function Params(){
    
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
?>