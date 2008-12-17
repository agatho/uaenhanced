<?
function artefact_getDetail($caveID, &$myCaves, $artefactID){

  global $params, $config, $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList;

  $template = @tmpl_open('templates/' . $config->template_paths[$params->SESSION->user['template']] . '/artefactdetail.ihtml');

  $show_artefact = TRUE;
  $artefact = artefact_getArtefactByID($artefactID);

  $description_initiated = $artefact['description_initiated'];
  unset($artefact['description_initiated']);

  // Gott oder nicht?
  if ($params->SESSION->user['tribe'] != GOD_ALLY){
    // gibts nicht oder nicht in einer Höhle
    if (!$artefact['caveID']){
      $show_artefact = FALSE;

    } else {

      $cave = getCaveByID($artefact['caveID']);

      // leere Höhle
      if (!$cave['playerID']){
        $show_artefact = FALSE;

      } else {

        $owner = getPlayerFromID($cave['playerID']);
        // Besitzer ist ein Gott
        if ($owner['tribe'] == GOD_ALLY){
          $show_artefact = FALSE;
        }
      }
    }
  }

  if ($show_artefact){

    // eigene Höhle ...
    if (array_key_exists($artefact['caveID'], $myCaves)){

      // Ritual ausführen?
      if (isset($params->POST->initiate)){
        $message = artefact_beginInitiation($artefact);
        tmpl_set($template, 'message', $message);

        // reload
        $myCaves = getCaves($params->SESSION->user['playerID']);
      }

      // wenn noch uneingeweiht und in der "richtigen" Höhle, ritual zeigen
      else if ($artefact['caveID'] == $caveID && $artefact['initiated'] == ARTEFACT_UNINITIATED){

        // Check, ob bereits eingeweiht wird.
        if (sizeof(artefact_getArtefactInitiationsForCave($caveID)) == 0){

          // Hol das Einweihungsritual
          $ritual = artefact_getRitualByID($artefact['initiationID']);

          // Hol die Kosten und beurteile ob genug da ist
          $merged_game_rules = array_merge($resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList, $defenseSystemTypeList);

          $cost = array();
          foreach($merged_game_rules as $val){
            if ($ritual[$val->dbFieldName]){

              $object_context = (ceil($ritual[$val->dbFieldName]) > floor($myCaves[$artefact['caveID']][$val->dbFieldName])) ?
                                'LESS' : 'ENOUGH';
              array_push($cost, array('object' => $val->name, $object_context.'/amount' => $ritual[$val->dbFieldName]));
            }
          }

	    // ADDED by chris--- for artefact destroying
	    // hol die destroy_chance
	    $destroy_chance = artefact_getDestroyChance($artefactID);

          $artefact['INITIATION'] = array('COST'        => $cost,
                                          'name'        => $ritual['name'],
							'destroy_chance' => $destroy_chance, // ADDED by chris--- for artefact destroying
                                          'description' => $ritual['description'],
                                          'duration'    => time_formatDuration($ritual['duration']),
                                          'HIDDEN'      => array(array('name' => "artefactID", 'value' => $artefact['artefactID']),
                                                                 array('name' => "modus",      'value' => ARTEFACT_DETAIL),
                                                                 array('name' => "initiate",   'value' => 1)));
        }

        // es wird bereits in dieser Höhle eingeweiht...
        else {
          tmpl_iterate($template, 'ARTEFACT/NO_INITIATION');
        }
      }
      // "geheime" Beschreibung nur zeigen, wenn eingeweiht
      if ($artefact['initiated'] == ARTEFACT_INITIATED)
        $artefact['description_initiated'] = $description_initiated;
    }

    tmpl_set($template, 'ARTEFACT', $artefact);
  } else {
    tmpl_set($template, 'message', "Über dieses Artefakt weiss man nichts.");
  }

  return tmpl_parse($template);
}

function artefact_getList($caveID, $myCaves){
  global $params, $config;

  $template = @tmpl_open('templates/' . $config->template_paths[$params->SESSION->user['template']] . '/artefactlist.ihtml');

  $artefacts = getArtefactList();

  foreach ($artefacts AS $value){
    
    // eigenes Artefakt
    if (array_key_exists($value['caveID'], $myCaves)){
      $context = 'ARTEFACT_OWN';
      $value['alternate'] = ++$alternate_own % 2 ? "alternate" : "";

      switch ($value['initiated']){

        case ARTEFACT_UNINITIATED: if ($value['caveID'] == $caveID)
                                     $value['INITIATION_POSSIBLE'] = array(
                                       'modus_artefact_detail' => ARTEFACT_DETAIL,
                                       'artefactID' => $value['artefactID']);
                                   else
                                     $value['INITIATION_NOT_POSSIBLE'] = array('status' => "uneingeweiht");
                                   break;
        case ARTEFACT_INITIATING:  $value['INITIATION_NOT_POSSIBLE'] = array('status' => "wird gerade eingeweiht");break;
        case ARTEFACT_INITIATED:   $value['INITIATION_NOT_POSSIBLE'] = array('status' => "eingeweiht");break;
        default:                   $value['INITIATION_NOT_POSSIBLE'] = array('status' => "Fehler!");
      }

    // fremdes Artefakt
    } else {
      
      // Berechtigung prüfen
      
      // ***** kein Gott! *****************************************************
//      if ($params->SESSION->user['tribe'] != GOD_ALLY){

// ADDED by chris---: security for gods. not all gods should see this
      if ($params->SESSION->user['playerID'] != 1){
        
        // Artefakt liegt in einer Höhle
        if ($value['caveID'] != 0){
          
          // A. in Einöden und von Göttern sind Tabu
          if ($value['playerID'] == 0 || $value['tribe'] == GOD_ALLY) continue;
          
          $context = 'ARTEFACT_OTHER';
          $value['alternate'] = ++$alternate_other % 2 ? "alternate" : "";
        }
        
        // Artefakt liegt nicht in einer Höhle
        else {
          
          // A. wird bewegt?
          $move = getArtefactMovement($value['artefactID']);
          
          // nein. Limbusartefakt!
          if (sizeof($move) == 0)
            continue;
          
          // A. wird bewegt!
          $context = 'ARTEFACT_MOVING_ETA';
          $value += $move;
          $value['alternate'] = ++$alternate_moving % 2 ? "alternate" : "";
        }
      }
      
      // ***** Gott! *****************************************************+++++
      else {

        // Artefakt liegt in einer Höhle
        if ($value['caveID'] != 0){
          

          // A. liegt in Einöde.
          if ($value['playerID'] == 0){
            $context = 'ARTEFACT_HIDDEN';
            $value['alternate'] = ++$alternate_hidden % 2 ? "alternate" : "";
          }
          
          // A. liegt bei einem Spieler
          else {
            $context = 'ARTEFACT_OTHER';
            $value['alternate'] = ++$alternate_other % 2 ? "alternate" : "";
          }
        }
        
        // Artefakt liegt nicht in einer Höhle
        else {
          
          // A. wird bewegt?
          $move = getArtefactMovement($value['artefactID']);
          
          // nein. Limbusartefakt!
          if (sizeof($move) == 0){
            $context = 'ARTEFACT_LIMBUS';
            $value['alternate'] = ++$alternate_limbus % 2 ? "alternate" : "";
          }          
          
          // A. wird bewegt!
          else {
            $context = 'ARTEFACT_MOVING_ETA';
            $value += $move;
            $value['alternate'] = ++$alternate_moving % 2 ? "alternate" : "";
          }
        }        
      } // Gott
    } // fremdes Artefakt

    $value['modus_artefact_detail'] = ARTEFACT_DETAIL;
    $value['modus_map_detail']      = MAP_DETAIL;
    $value['modus_player_detail']   = PLAYER_DETAIL;
    $value['modus_tribe_detail']    = TRIBE_DETAIL;

    tmpl_iterate($template, $context . '/ARTEFACT');
    tmpl_set($template, $context . '/ARTEFACT', $value);
  }

  return tmpl_parse($template);
}
?>
