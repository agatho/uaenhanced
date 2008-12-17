<?
/*
 * unitaction.html.php -
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once('lib/Movement.php');
@include_once('modules/CaveBookmarks/model/CaveBookmarks.php');

function unitAction($caveID, &$meineHoehlen){

  global $config, $db,
         $MAX_RESOURCE,
         $MOVEMENTCOSTCONSTANT,
         $MOVEMENTSPEEDCONSTANT,
         $params,
         $resourceTypeList,
         $unitTypeList,
         $FUELRESOURCEID;

  // get movements
  $ua_movements = Movement::getMovements();
  
  $details = $meineHoehlen[$caveID];

  /***************************************************************************/
  /**                                                                       **/
  /** CHECK ARTEFACTS                                                       **/
  /**                                                                       **/
  /***************************************************************************/

  // artefact moving: get ID if any
  //
  // $params->POST->myartefacts will be
  //   NULL, if it is not set at all
  //   -1 when choosing no artefact to move
  //   0 if there was a real choice

  // default: Move No Artefact (this var holds the artefactID to move)
  $moveArtefact = 0;

  // this array shall contain the artefacts if any
  $myartefacts = array();

  // does the cave contain an artefact at least?
  if ($details['artefacts'] > 0){

    // get artefacts
    $myartefacts = artefact_getArtefactsReadyForMovement($caveID);

    // was an artefact chosen?
    if (((int)$params->POST->myartefacts) > 0){

      $tempID = (int)$params->POST->myartefacts;

      // now check, whether this artefactID belongs to this cave
      foreach ($myartefacts as $key => $value){

        // if found, set it
        if ($tempID == $value['artefactID']){
          $moveArtefact = $tempID;
          break;
        }
      }
    }
  }
  // now $moveArtefact should contain 0 for 'move no artefact'
  // or the artefactID of the artefact to be moved

  /***************************************************************************/
  /***************************************************************************/
  /***************************************************************************/

  // put user, its session and nogfx flag into session
  $_SESSION['player'] =Player::getPlayer($params->SESSION->player->playerID);
  $params->SESSION->player = $_SESSION['player'];

  // get Map Size
  $size = getMapSize();
  $dim_x = ($size['maxX'] - $size['minX'] + 1)/2;
  $dim_y = ($size['maxY'] - $size['minY'] + 1)/2;

  $foodPerCave    = eval('return '. formula_parseToPHP($MOVEMENTCOSTCONSTANT . ';', '$details'));
  $minutesPerCave = eval('return '. formula_parseToPHP($MOVEMENTSPEEDCONSTANT . ';', '$details'));
  $minutesPerCave *= MOVEMENT_TIME_BASE_FACTOR/60;


  if (isset($params->POST->moveit) && sizeof($params->POST->unit)){
    $targetXCoord   = intval($params->POST->targetXCoord);
    $targetYCoord   = intval($params->POST->targetYCoord);
    $targetCaveName = $params->POST->targetCaveName;
    $targetCaveID   = intval($params->POST->targetCaveID);
    $movementID     = intval($params->POST->movementID);

    // check for scripters
    check_timestamp($params->POST->tstamp);

    $validCaveName = FALSE;

    // targetCaveID >>> coords
    if (isset($targetCaveID) && $targetCaveID > 0){
      $result = getCaveByID(intval($targetCaveID));
      if (sizeof($result) != 0){
        $targetXCoord = $result['xCoord'];
        $targetYCoord = $result['yCoord'];
        $validCaveName = TRUE;
      }

    // name >>> coords
    } else if (isset($targetCaveName)){
      $result = getCaveByName($targetCaveName);
      if (sizeof($result) != 0){
        $targetXCoord = $result['xCoord'];
        $targetYCoord = $result['yCoord'];
        $validCaveName = TRUE;
      }
    }

    // get target player
    $result = getCaveByCoords(intval($targetXCoord), intval($targetYCoord));
    if (sizeof($result) != 0) {
      $targetPlayer = new Player(getPlayerByID($result['playerID']));
    }

    // Array von Nullwerten befreien
    $unit     = array_filter($params->POST->unit, "filterZeros");
    $unit     = array_map("checkFormValues", $unit);
    $resource = array_map("checkFormValues", $params->POST->rohstoff);

    // Test, ob Einheitentragekapazität ausgelastet
    foreach ($resource as $resKey => $aRes){
      $capacity = 0;
      foreach ($unit as $unitKey => $aUnit)
        $capacity += $aUnit * $unitTypeList[$unitKey]->encumbranceList[$resKey];

      if ($capacity < $aRes){
        $overloaded = 1;
        break;
      }
    }
    
                      
    if ($movementID == 2) {  // move units/resources
      if (strtoupper($targetPlayer->tribe) != strtoupper($params->SESSION->player->tribe)) {  //may tade in own tribe
    	
      	$ownTribe = $params->SESSION->player->tribe;
      	$targetTribe = $targetPlayer->tribe;
      	$targetIsNonPlayer = $targetPlayer->playerID == 0;
      	
      	
        $ownTribeAtWar = tribe_isAtWar($ownTribe,TRUE,$db);
        $targetTribeAtWar = tribe_isAtWar($targetTribe,TRUE,$db);
        $TribesMayTrade = relation_areAllies($ownTribe,$targetTribe,$db) ||
                          relation_areEnemys($ownTribe,$targetTribe,$db) ||
                          $targetIsNonPlayer;

        $denymovement_nonenemy = $ownTribeAtWar && !$TribesMayTrade;
        $denymovement_targetwar =  $targetTribeAtWar && !$TribesMayTrade;  
      }  
    }

    if ($params->POST->movementID == 0)
      $msg = _('Bitte Bewegungsart auswählen!');

    else if (!sizeof($unit))
      $msg = _('Es sind keine Einheiten ausgewählt!');

    else if ((empty($targetXCoord) || empty($targetYCoord)) AND empty($targetCaveName))
      $msg = _('Es fehlt eine Zielkoordinate oder ein Zielhöhlenname!');

    else if ((empty($targetXCoord) || empty($targetYCoord)) AND !empty($targetCaveName) AND $validCaveName === FALSE)
      $msg = sprintf(_('Es gibt keine Höhle mit dem Namen "%s"!'), $targetCaveName);

    else if ($overloaded)
      $msg = _('Deine Krieger können die Menge an Ressourcen nicht tragen!!');

    else if (beginner_isCaveProtectedByCoord($targetXCoord, $targetYCoord, $db))
      $msg = _('Die Zielhöhle steht unter Anfängerschutz.');

    else if (beginner_isCaveProtectedByID($caveID, $db))
      $msg = _('Ihre Höhle steht unter Anfängerschutz. Sie können den Schutz sofort unter dem Punkt <a href="?modus=cave_detail">Bericht über diese Höhle</a> beenden');

    else if ($params->POST->movementID == 6 && cave_isCaveSecureByCoord($targetXCoord, $targetYCoord, $db))
      $msg = _('Sie können diese Höhle nicht übernehmen. Sie ist gegen Übernahmen geschützt.');

    else if ($denymovement_nonenemy)
      $msg = _('Sie können im Krieg keine Einheiten zu unbeteiligten Parteien verschieben!');

    else if ($denymovement_targetwar)
      $msg = _('Sie können keine Einheiten zu kriegführenden Stämmen verschieben, wenn Sie unbeteiligt sind.');

    //  Einheiten bewegen!
    else {

      // Entfernung x Dauer pro Höhle x größter Geschwindigkeitsfaktor x Bewegungsfaktor
      $duration = ceil(
        getDistanceByCoords($details['xCoord'], $details['yCoord'],
                            $targetXCoord, $targetYCoord) *
        $minutesPerCave *
        getMaxSpeedFactor($unit) *
        $ua_movements[$movementID]->speedfactor);
  $distance = ceil(getDistanceByCoords($details['xCoord'], $details['yCoord'],
                            $targetXCoord, $targetYCoord));
  $tmpdist = 0;
  $i = 0;
  if($distance > 15){
    $distance = $distance - 15;
    $tmpdist = 15;
    if(floor($distance/5)<11)
      $tmpdist += ($distance % 5) * (1-0.1*floor($distance/5));

    for($i = 1; $i <= floor( $distance / 5) && $i < 11; $i++){
      $tmpdist += 5*(1-0.1*($i-1));
    }
  }else{
      $tmpdist = $distance;
  }

      // Dauer x Rationen x Größe einer Ration x Bewegungsfaktor
      $reqFood = ceil($tmpdist *
                      $minutesPerCave *
                      getMaxSpeedFactor($unit) *
                      $ua_movements[$movementID]->speedfactor *
                      calcRequiredFood($unit) *
                      $foodPerCave *
                      $ua_movements[$movementID]->foodfactor);

      if ($details[$resourceTypeList[$FUELRESOURCEID]->dbFieldName]< $reqFood){
        $msg = _('Nicht genug Nahrung zum Ernähren der Krieger auf ihrem langen Marsch vorhanden!');

      } else {
        $msgID = setMovementEvent(
          $caveID, $details,
          $targetXCoord, $targetYCoord,
          $unit, $resource,
          $movementID, $reqFood, $duration,
          $moveArtefact,
          $minutesPerCave * $ua_movements[$movementID]->speedfactor);

        switch ($msgID){
          case 0: $msg = sprintf(_('Die Krieger wurden losgeschickt und haben %d Nahrung mitgenommen!'), $reqFood);
                  break;
          case 1: $msg = _('In diesen Koordinaten liegt keine Höhle!');
                  break;
          case 2: $msg = _('Für diese Bewegung sind nicht genügend Einheiten/Rohstoffe verfügbar!');
                  break;
          case 3: $msg = _('Schwerer Fehler: Bitte Admin kontaktieren!');
        }
      }
    }
  } else if (!empty($params->POST->eventID)){

    $msgID = reverseMovementEvent($caveID, $params->POST->eventID);
    switch ($msgID){
      case 0: $msg = _('Die Einheiten kehren zurück!'); break;
      case 1: $msg = _('Fehler bei der Rückkehr!'); break;
    }
  }

  // refresh this cave
  $temp = getCaveSecure($caveID, $params->SESSION->player->playerID);
  $meineHoehlen[$caveID] = $details = $temp->nextRow(MYSQL_ASSOC);
  // make sure that bagged artefacts are not shown again
  if ($moveArtefact != 0)
    $myartefacts = artefact_getArtefactsReadyForMovement($caveID);

// //////////////////////////////////////////////////////////////
// Create the page
// //////////////////////////////////////////////////////////////

  $template = tmpl_open($params->SESSION->player->getTemplatePath() . 'unitaction.ihtml');

  // messages
  if (isset($msg)) tmpl_set($template, '/MESSAGE/msg', $msg);

  // javascript support
  tmpl_set($template, 'currentX',             $details['xCoord']);
  tmpl_set($template, 'currentY',             $details['yCoord']);
  tmpl_set($template, 'dim_x',                $dim_x);
  tmpl_set($template, 'dim_y',                $dim_y);
  tmpl_set($template, 'speed',                $minutesPerCave);
  tmpl_set($template, 'fuel_id',              $FUELRESOURCEID);
  tmpl_set($template, 'fuel_name',            $resourceTypeList[$FUELRESOURCEID]->name);
  tmpl_set($template, 'movementcostconstant', $foodPerCave);
  tmpl_set($template, "resourceTypes",        $MAX_RESOURCE);
  tmpl_set($template, "rules_path",           RULES_PATH);

  // movements
  $selectable_movements = array();
  foreach ($ua_movements AS $value)
    if ($value->playerMayChoose)
      $selectable_movements[] = get_object_vars($value);
  tmpl_set($template, 'SELECTACTION', $selectable_movements);


  // resources
  $resources = array();
  for($res = 0; $res < sizeof($resourceTypeList); $res++)
    if (!$resourceTypeList[$res]->nodocumentation)
    $resources[] = array(
      'resourceID'    => $resourceTypeList[$res]->resourceID,
      'name'          => $resourceTypeList[$res]->name,
      'currentAmount' => "0" + $details[$resourceTypeList[$res]->dbFieldName],
      'dbFieldName'   => $resourceTypeList[$res]->dbFieldName);

  tmpl_set($template, 'RESOURCE',         $resources);
  tmpl_set($template, 'TOTAL',            $resources);
  tmpl_set($template, 'RESOURCE_LUGGAGE', $resources);

  // units table
  $unitprops = array();
  $units     = array();
  for($i = 0; $i < sizeof($unitTypeList); $i++){

    // if no units of this type, next type
    if (!$details[$unitTypeList[$i]->dbFieldName]) continue;

    $temp = array();
    $encumbrance = array();
    for( $j = 0; $j < count($resourceTypeList); $j++) {
      if (!$resourceTypeList[$j]->nodocumentation) {
        $encumbrance[$j] = array(
          'resourceID' => $j,
          'load' => "0" + $unitTypeList[$i]->encumbranceList[$j]);
        $temp[] = "0" + $unitTypeList[$i]->encumbranceList[$j];
      }
    }

    $unitprops[] = array(
      'unitID'           => $unitTypeList[$i]->unitID,
      'foodCost'         => $unitTypeList[$i]->foodCost,
      'speedFactor'      => $unitTypeList[$i]->wayCost,
      'resourceLoad'     => implode(",", $temp),
      'maxWarriorAnzahl' => $details[$unitTypeList[$i]->dbFieldName]);

    $units[] = array(
      'name'             => $unitTypeList[$i]->name,
      'modus'            => UNIT_PROPERTIES,
      'unitID'           => $unitTypeList[$i]->unitID,
      'foodCost'         => $unitTypeList[$i]->foodCost,
      'speedFactor'      => $unitTypeList[$i]->wayCost,
      'maxWarriorAnzahl' => $details[$unitTypeList[$i]->dbFieldName],
      // ?? warum -> ?? $i gegen namen ersetzen!!! TODO
      'warriorID'        => $i,
      'ENCUMBRANCE'      => $encumbrance);
  }
  tmpl_set($template, 'UNITPROPS',     $unitprops);
  tmpl_set($template, 'SELECTWARRIOR', $units);

  // weitergereichte Koordinaten
  if (empty($params->POST->movementID)){
    tmpl_set($template, 'targetXCoord',   $params->POST->targetXCoord);
    tmpl_set($template, 'targetYCoord',   $params->POST->targetYCoord);
    tmpl_set($template, 'targetCaveName', $params->POST->targetCaveName);
  }

  // weitere Paramter
  $hidden = array(
    array('name'=>'modus',  'value'=>UNIT_MOVEMENT),
    array('name'=>'moveit', 'value'=>'true'),
    array('name'=>'trigger','value'=>'self'),
    array('name'=>'tstamp', 'value'=>"".time()));
  tmpl_set($template, 'PARAMS', $hidden);


  $movements = digest_getMovements(array($caveID => $details), array(), true);
  //$movements = digest_getMovements($meineHoehlen, array(), true);
  foreach($movements AS $move){
    if ($move['isOwnMovement']){
      tmpl_iterate($template, 'MOVEMENT/MOVE');
      tmpl_set($template, 'MOVEMENT/MOVE', $move);
    } else {
      tmpl_iterate($template, 'OPPMOVEMENT/MOVE');
      tmpl_set($template, 'OPPMOVEMENT/MOVE', $move);
    }
  } 

  // artefakte
  if (sizeof($myartefacts) != 0)
    tmpl_set($template, '/ARTEFACTS/ARTEFACT', $myartefacts);

  // Module "CaveBookmarks" Integration
  // FIXME should know whether the module is installed
  if (TRUE){
    
    // show CAVEBOOKMARKS context
    tmpl_set($template, '/CAVEBOOKMARKS/iterate', '');
    
    // get model
    $cb_model = new CaveBookmarks_Model();
    
    // get bookmarks
    $bookmarks = $cb_model->getCaveBookmarks(true);
    
    // set bookmarks
    if (sizeof($bookmarks)){
      tmpl_set($template, '/CAVEBOOKMARKS/CAVEBOOKMARK',   $bookmarks);
      tmpl_set($template, '/CAVEBOOKMARKS/CAVEBOOKMARKJS', $bookmarks);
    }
  }

  return tmpl_parse($template);
}

?>
