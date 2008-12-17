<?
/*
 * formula_parser.inc.php -
 * Copyright (c) 2004  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

require_once("game_rules.php");
require_once("effect_list.php");

init_Buildings();
init_Units();
init_Resources();
init_Sciences();
init_DefenseSystems();

init_Symbols();

function init_Symbols(){
  global $resourceTypeList, $buildingTypeList, $unitTypeList, $scienceTypeList,
         $defenseSystemTypeList, $effectTypeList;

  global $FORMULA_SYMBOLS;
  
  $FORMULA_SYMBOLS = array("R" => &$resourceTypeList,
                           "B" => &$buildingTypeList,
                           "U" => &$unitTypeList,
                           "S" => &$scienceTypeList,
                           "D" => &$defenseSystemTypeList,
                           "E" => &$effectTypeList);
}

function sign($value){
  if ($value > 0) return 1;
  if ($value < 0) return -1;
  return 0;
}

function formula_parseToSQL($formula){
  global $FORMULA_SYMBOLS, $params;


  $farmmalus = max($params->SESSION->player->fame - FREE_FARM_POINTS , 0);
  $formula = str_replace("[E25.ACT]",$farmmalus,$formula);
  // abstract functions are sql functions -> no translation needed
  
  // parse symbols
  for ($i = 0; $i < strlen($formula); $i++){

    // opening brace
    if ($formula{$i} == '['){

      $symbol = $formula{++$i};
      $index = 0;

      while($formula{++$i} != '.')
        $index = $index * 10 + ($formula{$i} + 0);

      $field  = substr($formula, ++$i, 3);

      // 'ACT]' or 'MAX]'
      $i += 3;

      if (strncasecmp($field, "ACT", 3) == 0)
        $sql .= $FORMULA_SYMBOLS[$symbol][$index]->dbFieldName;

      else if (strncasecmp($field, "MAX", 3) == 0)
        $sql .= formula_parseToSQL($FORMULA_SYMBOLS[$symbol][$index]->maxLevel);

    } else {
      $sql .= $formula{$i};
    }
  }
  
  $sql = str_replace(array('min(',   'max(',      'sgn('),
                     array('LEAST(', 'GREATEST(', 'SIGN('),
                     $sql);
   
  return $sql;
}

function formula_parseToPHP($formula, $detail){

  global $FORMULA_SYMBOLS, $config, $params;

  $farmmalus = max($params->SESSION->player->fame - FREE_FARM_POINTS , 0);
  $formula = str_replace("[E25.ACT]",$farmmalus,$formula);
  
  if ($config->RUN_TIMER)
    $timer = page_startTimer();

  // translate abstract functions to php functions
  $formula = str_replace(array('sgn'), array('SIGN'), $formula);


  // translate variables
  for ($i = 0; $i < strlen($formula); $i++){

    if ($formula{$i} == '['){

      $symbol = $formula{++$i};
      $index = 0;

      while($formula{++$i} != '.')
        $index = $index * 10 + ($formula{$i} + 0);

      $field = substr($formula, ++$i, 3);
      // 'ACT]' or 'MAX]'
      $i += 3;

      if (strncasecmp($field, "ACT", 3) == 0)
        $php .= $detail . '[' . $FORMULA_SYMBOLS[$symbol][$index]->dbFieldName . ']';

      else if (strncasecmp($field, "MAX", 3) == 0)
        $php .= formula_parseToPHP($FORMULA_SYMBOLS[$symbol][$index]->maxLevel, $detail);

    } else {
      $php .= $formula{$i};
    }
  }

  if ($config->RUN_TIMER)
    echo "<p>rules_parseToPHP: " . page_stopTimer($timer) . "s</p>";

  return $php;
}

/** This function checks if an object can be build by examining its dependencies.
 *
 *  @param $object    the object to be checked
 *  @param $caveData  the data to be checked against
 *
 *  @return  returns TRUE if the object can be build,
 *           FALSE if the object cannot be build at all because of mutual exclusion
 *           or a string describing the circumstances needed to build that object
 */
function rules_checkDependencies($object, $caveData){

  global $buildingTypeList,
         $defenseSystemTypeList,
         $resourceTypeList,
         $scienceTypeList,
         $unitTypeList;

  foreach ($object->maxBuildingDepList as $key => $value)
    if ($value != -1 && $value < $caveData[$buildingTypeList[$key]->dbFieldName])
      return FALSE;
  foreach ($object->maxDefenseSystemDepList as $key => $value)
    if ($value != -1 && $value < $caveData[$defenseSystemTypeList[$key]->dbFieldName])
      return FALSE;
  foreach ($object->maxResourceDepList as $key => $value)
    if ($value != -1 && $value < $caveData[$resourceTypeList[$key]->dbFieldName])
      return FALSE;
  foreach ($object->maxScienceDepList as $key => $value)
    if ($value != -1 && $value < $caveData[$scienceTypeList[$key]->dbFieldName])
      return FALSE;
  foreach ($object->maxUnitDepList as $key => $value)
    if ($value != -1 && $value < $caveData[$unitTypeList[$key]->dbFieldName])
      return FALSE;

  foreach($object->buildingDepList as $key => $value)
    if ($value != "" && $value > $caveData[$buildingTypeList[$key]->dbFieldName])
        $dep .= $buildingTypeList[$key]->name . ": $value ";
  foreach($object->defenseSystemDepList as $key => $value)
    if ($value != "" && $value > $caveData[$defenseSystemTypeList[$key]->dbFieldName])
        $dep .= $defenseSystemTypeList[$key]->name . ": $value ";
  foreach($object->resourceDepList as $key => $value)
    if ($value != "" && $value > $caveData[$resourceTypeList[$key]->dbFieldName])
        $dep .= $resourceTypeList[$key]->name . ": $value ";
  foreach($object->scienceDepList as $key => $value)
    if ($value != "" && $value > $caveData[$scienceTypeList[$key]->dbFieldName])
      $dep .= $scienceTypeList[$key]->name . ": $value ";
  foreach($object->unitDepList as $key => $value)
    if ($value != "" && $value > $caveData[$unitTypeList[$key]->dbFieldName])
      $dep .= $unitTypeList[$key]->name . ": $value ";

  return ($dep === NULL ? TRUE : $dep);
}
?>
