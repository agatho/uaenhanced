<?
/*
 * playerDetail.html.php - 
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


function player_getContent($caveID, $playerID) {
  global $db, $no_resource_flag, $config, $params;

  $no_resource_flag = 1;

  if (!($r = $db->query("SELECT * FROM Player WHERE playerID = '$playerID'")))
    page_dberror();

  if (!($row = $r->nextRow(MYSQL_ASSOC)))
    page_dberror();
  
  $template = @tmpl_open("./templates/" .  $config->template_paths[$params->SESSION->user['template']] . "/playerDetail.ihtml");

  if ($row['avatar']) {

    $x = 120;
    $y = 120;

    if ($x != -1 && $y != -1){

      tmpl_set($template, 'DETAILS/AVATAR_IMG/avatar', $row[avatar]);
 
      if ($x > AVATAR_X || $y > AVATAR_Y){
        if ($x > $y){
          $y *= AVATAR_X / $x;
          $x  = AVATAR_X;        
        } else {
          $x *= AVATAR_Y / $y;
          $y  = AVATAR_Y;
        }        
      }
      tmpl_set($template, 'DETAILS/AVATAR_IMG/width',  floor($x));
      tmpl_set($template, 'DETAILS/AVATAR_IMG/height', floor($y));
    }
  }

  if (!empty($row['awards'])){
    $tmp = explode('|', $row['awards']);
    $awards = array();
    foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
    $row['award'] = $awards;
  }
  unset($row['awards']);

  foreach($row as $k => $v)
    if (! $v ) 
      $row[$k] = "k.A.";


  $row['mail_modus']    = NEW_MESSAGE;
  $row['mail_receiver'] = urlencode($row['name']);
  $row['caveID']        = $caveID;

// ADDED by chris--- for adressbook
  $row['adressbook_add_modus'] = MESSAGE_BOOK_ADD;

// ADDED by chris--- for rank_history
  $row['playerID'] = $playerID;

// ADDED by chris--- for farmschutz
if (FARMSCHUTZ_ACTIVE == 1) {
  $query = "SELECT round( sum( r.average ) / count( r.average ) / 1.5 ) AS grenze, p.farmschutz AS protection, r2.average AS punkte FROM ranking r LEFT JOIN player p ON p.playerID = ".$playerID." LEFT JOIN ranking r2 ON p.playerID = r2.playerID GROUP BY p.playerID";
    if (!($result = $db->query($query))) page_dberror();
    if (!($myrow = $result->nextRow(MYSQL_ASSOC)))
//	page_dberror();
	$row['farmschutz'] = "noch nicht berechenbar";

    else {

    $row['farmschutz'] = $myrow[protection]."% - ".$myrow[grenze];

//    if ($playerID == $params->SESSION->user['playerID']) {
      // eigenes Profil
      if ($myrow[grenze] > $myrow[punkte]) {
        // spieler unter grenze
        $pkmin = round($myrow[punkte]/100*$myrow[protection]);
        $pkmax = round($myrow[punkte]*100/$myrow[protection]);
        if ($pkmax > 10000) $pkmax = 10000;
        if ($pkmin < 0) $pkmin = 0;
        $row['farmschutz'] = $row['farmschutz']."<br><b>Punktegrenzen: ".$pkmin." - ".$pkmax."</b>";
      } else {
        $row['farmschutz'] = $row['farmschutz']."<br>Spieler liegt &uuml;ber der Noobgrenze";
      }
//    }
    }
} else {
  $row['farmschutz'] = "";
}



   
  tmpl_set($template, 'DETAILS', $row);
  
  if (!($dbresult = $db->query("SELECT xCoord, yCoord, name, caveID AS targetCaveID FROM Cave WHERE playerID =  '$playerID'")))
    page_dberror();
  $caves = array();
  while($row = $dbresult->nextRow(MYSQL_ASSOC)){
    $row['modus']  = MAP;
    $row['caveID'] = $caveID;
    $row['movementLink'] = MOVEMENT; // ADDED by chris--- for cavebook
    $row['addCaveLink'] = "?modus=".CAVE_BOOK_ADD."&amp;id="; // ADDED by chris--- for cavebook
    array_push($caves, $row);
  }

  tmpl_set($template, 'DETAILS/CAVES', $caves);
  
  return tmpl_parse($template);
}

?>
