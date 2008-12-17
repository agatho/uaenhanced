<?
/*
 * Movement.php -
 * Copyright (c) 2004  Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

class Movement {

  var $id;
  var $speedfactor;
  var $foodfactor;
  var $mayBeInvisible;
  var $playerMayChoose;
  var $description;

  function Movement($id, $speedfactor, $foodfactor, $returnID,
                    $mayBeInvisible, $playerMayChoose, $description){

    $this->id              = $id;
    $this->speedfactor     = $speedfactor;
    $this->foodfactor      = $foodfactor;
    $this->returnID        = $returnID;
    $this->mayBeInvisible  = $mayBeInvisible;
    $this->playerMayChoose = $playerMayChoose;
    $this->description     = htmlentities($description);
  }

  function getMovements(){
    static $ua_movements = NULL;

    if ($ua_movements === NULL){
      $ua_movements = array();
      $ua_movements[1] = new Movement(1, 1, 2,  5, false, true,  _('Rohstoffe bringen'));
      $ua_movements[2] = new Movement(2, 1, 1,  5, false, true,  _('Einheiten/Rohstoffe verschieben'));
      $ua_movements[3] = new Movement(3, 1, 2,  5, false, true,  _('Angreifen'));
      $ua_movements[4] = new Movement(4, 0.5, 2,  5, true,  true,  _('Spionieren'));
      $ua_movements[5] = new Movement(5, 1, 1, -1, false, false, _('R�ckkehr'));
      $ua_movements[6] = new Movement(6, 5, 2,  5, false, true,  _('�bernahme'));
    }

    return $ua_movements;
  }
}
