<?
/*
 * movement.lib.php - routines for movement processing
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

/** This function computes the distance between two caves using their IDs.
 */
function getDistanceByID($srcID, $destID){

  $srcCave  = getCaveByID($srcID);
  $destCave = getCaveByID($destID);

  return getDistanceByCoords($srcCave['xCoord'],  $srcCave['yCoord'],
                             $destCave['xCoord'], $destCave['yCoord']);
}

/** This function computes the distance between two caves using their Coords.
 */
function getDistanceByCoords($srcX, $srcY, $tarX, $tarY){

  /* Using torus edge conditions */  
  $size = getMapSize();
  $dim_x = ($size['maxX'] - $size['minX'] + 1)/2;
  $dim_y = ($size['maxY'] - $size['minY'] + 1)/2;

  $xmin = $dim_x - abs(abs($srcX - $tarX) - $dim_x);
  $ymin = $dim_y - abs(abs($srcY - $tarY) - $dim_y);

  return sqrt($xmin * $xmin + $ymin * $ymin);
 
}

/** This function computes the vision range of a given cave.
 *  The measuring unit of the returned value is 'caves'.
 *  For example a returned value of 3 equals a vision range of
 *  three caves in any direction.
 */
function getVisionRange($cave_data){
  global $WATCHTOWERVISIONRANGE;
  return eval('return '.formula_parseToPHP($WATCHTOWERVISIONRANGE.";", '$cave_data'));
}

/** This function computes the amount of food needed to move with
 *  given units from one cave to its direct neighbour.
 */
function calcRequiredFood($units){
  global $unitTypeList;

  $foodPerCave = 0;
  foreach ($units as $unitID => $amount)
    $foodPerCave += $unitTypeList[$unitID]->foodCost * $amount;
  return $foodPerCave;
}

/** This function computes the greatest speed factor of a given
 *  set of units. A greater speed factor means a slower movement.
 */
function getMaxSpeedFactor($units){
  global $unitTypeList;

  $maxSpeed = 0;

  foreach ($units as $unitID => $amount)
    if ($amount > 0 && $unitTypeList[$unitID]->wayCost > $maxSpeed)
      $maxSpeed = $unitTypeList[$unitID]->wayCost;
  return $maxSpeed;
}
?>
