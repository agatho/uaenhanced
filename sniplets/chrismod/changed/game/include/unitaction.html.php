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

function unitAction($caveID, &$meineHoehlen){

  global $config, $db,
         $MAX_RESOURCE,
         $MOVEMENTCOSTCONSTANT,
         $MOVEMENTSPEEDCONSTANT,
         $params,
         $ua_movements,
         $resourceTypeList,
         $unitTypeList,
	   $effectTypeList, // ADDED by chris--- for movement speed factor
         $FUELRESOURCEID;

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


  // get Map Size
  $size = getMapSize();
  $dim_x = ($size['maxX'] - $size['minX'] + 1)/2;
  $dim_y = ($size['maxY'] - $size['minY'] + 1)/2;

  $foodPerCave    = eval('return '. formula_parseToPHP($MOVEMENTCOSTCONSTANT . ';', '$details'));
  $minutesPerCave = eval('return '. formula_parseToPHP($MOVEMENTSPEEDCONSTANT . ';', '$details'));


  if (isset($params->POST->moveit)){
    $targetXCoord   = $params->POST->targetXCoord;
    $targetYCoord   = $params->POST->targetYCoord;
    $targetCaveName = $params->POST->targetCaveName;
    $movementID     = $params->POST->movementID;

    // check for scripters
    check_timestamp($params->POST->tstamp);

    // HöhlenName >>> Koordinate
    $validCaveName = FALSE;
    if ((empty($targetXCoord) || empty($targetYCoord)) AND !empty($targetCaveName)){
      $result = getCaveByName($targetCaveName);
      if (sizeof($result) != 0){
        $targetXCoord = $result['xCoord'];
        $targetYCoord = $result['yCoord'];
        $validCaveName = TRUE;
      }
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

// ADDED by chris--- for farmschutz
if (FARMSCHUTZ_ACTIVE == 1) {
  if ($params->POST->movementID == 3 || $params->POST->movementID == 6) {
    $farmschutz = farmschutz($targetXCoord, $targetYCoord, $params->SESSION->user['playerID'], $db);
  }
}
// ------------------------------------

    if ($params->POST->movementID == 0)
      $msg = "*#$@*#$%: Bitte Bewegungsart ausw&auml;hlen!";

    else if (!sizeof($unit))
      $msg = "*#$@*#$%: Es sind keine Einheiten ausgew&auml;hlt!";

    else if ((empty($targetXCoord) || empty($targetYCoord)) AND empty($targetCaveName))
      $msg = "*#$@*#$%: Es fehlt eine Zielkoordinate oder ein Zielsiedlungsname!";

    else if ((empty($targetXCoord) || empty($targetYCoord)) AND !empty($targetCaveName) AND $validCaveName === FALSE)
      $msg = "*#$@*#$%: Es gibt keine Siedlung mit dem Namen '" . $targetCaveName . "'!";

    else if ($overloaded)
      $msg = "*#$@*#$%: Deine Krieger k&ouml;nnen die Menge an Ressourcen nicht tragen!!";

    else if (beginner_isCaveProtectedByCoord($targetXCoord, $targetYCoord, $db))
      $msg ="*#$@*#$%: Die Zielsiedlung steht unter Anf&auml;ngerschutz. ";

    else if (beginner_isCaveProtectedByID($caveID, $db))
      $msg = "*#$@*#$%: Ihre Siedlung steht unter Anf&auml;ngerschutz. ".
             "Sie k&ouml;nnen den Schutz sofort unter dem Punkt Bericht: Alle ".
             "meine Siedlungen beenden";

    else if ($params->POST->movementID == 6 && cave_isCaveSecureByCoord($targetXCoord, $targetYCoord, $db))
      $msg = "*#$@*#$%: Sie k&ouml;nnen diese Siedlung nicht &uuml;bernehmen. ".
             "Sie ist gegen &Uuml;bernahmen gesch&uuml;tzt.";

// ADDED by chris--- for farmschutz:
    else if (FARMSCHUTZ_ACTIVE == 1 && ($params->POST->movementID == 3 || $params->POST->movementID == 6) && $farmschutz == 1)
      $msg = "*#$@*#$%: Der Spieler steht unter Farmschutz. Sie k&ouml;nnen ihn nicht angreifen.";

    else if (FARMSCHUTZ_ACTIVE == 1 && ($params->POST->movementID == 3 || $params->POST->movementID == 6) && $farmschutz == 2)
      $msg = "*#$@*#$%: Sie stehen unter Farmschutz. Dieser Spieler ist zu gro&szlig; zum angreifen.";
// ----------------------------------

    //  Einheiten bewegen!
    else {

      // Entfernung x Dauer pro Höhle x größter Geschwindigkeitsfaktor x Bewegungsfaktor
      $duration = ceil(
        getDistanceByCoords($details['xCoord'], $details['yCoord'],
                            $targetXCoord, $targetYCoord) *
        $minutesPerCave *
        getMaxSpeedFactor($unit) *
        $ua_movements[$movementID]->speedfactor
	* (1+$details[$effectTypeList[25]->dbFieldName]) // ADDED by chris--- for movement_speed_factor
	);

      // Dauer x Rationen x Größe einer Ration x Bewegungsfaktor
      $reqFood = ceil($duration *
                      calcRequiredFood($unit) *
                      $foodPerCave *
                      $ua_movements[$movementID]->foodfactor);


      if ($details[$resourceTypeList[$FUELRESOURCEID]->dbFieldName]< $reqFood){
        $msg = "*#$@*#$%: Nicht genug Nahrung zum Ern&auml;hren der Krieger auf ihrem langen Marsch vorhanden!!";

      } else {

        $msgID = setMovementEvent(
          $caveID, $details,
          $targetXCoord, $targetYCoord,
          $unit, $resource,
          $movementID, $reqFood, $duration,
          $moveArtefact,
          $minutesPerCave * $ua_movements[$movementID]->speedfactor);


        switch ($msgID){
          case 0: $msg = "Die Krieger wurden losgeschickt und haben $reqFood Nahrung mitgenommen!";
                  break;
          case 1: $msg = "*#$@*#$%: In diesen Koordinaten liegt keine Siedlung!";
                  break;
          case 2: $msg = "*#$@*#$%: F&uuml;r diese Bewegung sind nicht gen&uuml;gend Einheiten/Rohstoffe verf&uuml;gbar!";
                  break;
          case 3: $msg = "Schwerer *#$@*#$%: Bitte Admin kontaktieren!";
        }
      }
    }
  } else if (!empty($params->POST->eventID)){

    $msgID = reverseMovementEvent($caveID, $params->POST->eventID);
    switch ($msgID){
      case 0: $msg = "Die Einheiten kehren zurück!"; break;
      case 1: $msg = "*#$@*#$%: Fehler bei der Rückkehr!"; break;
    }
  }

  // refresh this cave
  $temp = getCaveSecure($caveID, $params->SESSION->user['playerID']);
  $meineHoehlen[$caveID] = $details = $temp->nextRow(MYSQL_ASSOC);
  // make sure that bagged artefacts are not shown again
  if ($moveArtefact != 0)
    $myartefacts = artefact_getArtefactsReadyForMovement($caveID);

// //////////////////////////////////////////////////////////////
// Create the page
// //////////////////////////////////////////////////////////////

  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/unitaction.ihtml");

  // messages
  if (isset($msg)) tmpl_set($template, '/MESSAGE/msg', $msg);

  // javascript support
  tmpl_set($template, 'currentX',             $details['xCoord']);
  tmpl_set($template, 'currentY',             $details['yCoord']);
  tmpl_set($template, 'dim_x',                $dim_x);
  tmpl_set($template, 'dim_y',                $dim_y);
  tmpl_set($template, 'speed',                $minutesPerCave);
  tmpl_set($template, 'movementcostconstant', $foodPerCave);
  tmpl_set($template, "resourceTypes",        $MAX_RESOURCE);

  tmpl_set($template, "movement_speed_factor",        $details[$effectTypeList[25]->dbFieldName]); // ADDED by chris--- for movement_speed_factor


// ADDED by chris--- for cavebook:
  tmpl_set($template, 'show_book_modus', CAVE_BOOK);

  // Getting entries
 $cavelist = cavebook_getEntries($params->SESSION->user['playerID']);

  // Show the cave table
  for($i = 0; $i < sizeof($cavelist[id]); $i++) {

    $cavename = $cavelist[name][$i]; // the current cavename
    $cavebookID = $cavelist[id][$i];
    $cave_x = $cavelist[x][$i];
    $cave_y = $cavelist[y][$i];

    tmpl_iterate($template, '/BOOKENTRY');
    tmpl_set($template, 'BOOKENTRY/book_entry', $cavename);
    tmpl_set($template, 'BOOKENTRY/book_id', $cavebookID);
    tmpl_set($template, 'BOOKENTRY/book_x', $cave_x);
    tmpl_set($template, 'BOOKENTRY/book_y', $cave_y);

    tmpl_iterate($template, '/BOOKENTRYJS');
    tmpl_set($template, 'BOOKENTRYJS/book_entry', unhtmlentities($cavename));
    tmpl_set($template, 'BOOKENTRYJS/book_id', $cavebookID);
    tmpl_set($template, 'BOOKENTRYJS/book_x', $cave_x);
    tmpl_set($template, 'BOOKENTRYJS/book_y', $cave_y);
  }




  // movements
  $selectable_movements = array();
  foreach ($ua_movements AS $value)
    if ($value->playerMayChoose)
      $selectable_movements[] = get_object_vars($value);
  tmpl_set($template, 'SELECTACTION', $selectable_movements);



  // resources
  $resources = array();
  for($res = 0; $res < sizeof($resourceTypeList); $res++)
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

  $unitsAll  = array(); // ADDED by chris---
  for($i = 0; $i < sizeof($unitTypeList); $i++){

    // if no units of this type, next type
    if (!$details[$unitTypeList[$i]->dbFieldName]) continue;

    $temp = array();
    $encumbrance = array();
    for( $j = 0; $j < count($resourceTypeList); $j++) {
      $encumbrance[$j] = array(
        'resourceID' => $j,
        'load' => "0" + $unitTypeList[$i]->encumbranceList[$j]);
      $temp[] = "0" + $unitTypeList[$i]->encumbranceList[$j];
    }

    $unitprops[] = array(
      'unitID'       => $unitTypeList[$i]->unitID,
      'foodCost'     => $unitTypeList[$i]->foodCost,
      'speedFactor'  => $unitTypeList[$i]->wayCost,
      'resourceLoad' => implode(",", $temp));

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

$unitAnzahl = sizeof($units);

  }
  tmpl_set($template, 'UNITPROPS',     $unitprops);
  tmpl_set($template, 'SELECTWARRIOR', $units);
  tmpl_set($template, '/unitAnzahl', $unitAnzahl);

  // weitergereichte Koordinateny
  if (empty($params->POST->movementID)){
    tmpl_set($template, 'targetXCoord',   $params->POST->targetXCoord);
    tmpl_set($template, 'targetYCoord',   $params->POST->targetYCoord);
    tmpl_set($template, 'targetCaveName', $params->POST->targetCaveName);
  }

  // weitere Paramter
  $hidden = array(
    array('name'=>'modus',  'value'=>MOVEMENT),
    array('name'=>'moveit', 'value'=>'true'),
    array('name'=>'trigger','value'=>'self'),
    array('name'=>'tstamp', 'value'=>"".time()));
  tmpl_set($template, 'PARAMS', $hidden);


  $movements = digest_getMovements(array($caveID => $details), array(), true);
  //$movements = digest_getMovements($meineHoehlen, array(), true);
  foreach($movements AS $move){
    if ($move['isOwnMovement']){

if (isCaveInvisibleToPlayer($move['target_caveID'], $params->SESSION->user['playerID'], $db)) {
  $move['target_player_tribe'] = "";
  $move['target_player_name'] = "";
}
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
  return tmpl_parse($template);
}

?>
