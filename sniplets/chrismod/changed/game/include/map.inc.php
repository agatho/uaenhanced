<?php
function getCaveDetailsByCoords($minX, $minY, $maxX, $maxY){
	global $db;

  $caveDetails = array();
	$query = "SELECT c.terrain, c.name AS cavename, c.caveID, c.xCoord, ".
	         "c.yCoord, c.secureCave, c.artefacts, c.takeoverable, c.quest_cave, c.invisible_name,". // ADDED by chris--- for Quests: c.quest_cave,
	         "p.name, p.playerID, p.tribe ".
	         "FROM Cave c LEFT JOIN Player p ".
	         "ON c.playerID = p.playerID ".
	         "WHERE $minX <= c.xCoord AND c.xCoord <= $maxX ".
	         "AND   $minY <= c.yCoord AND c.yCoord <= $maxY ".
	         "ORDER BY c.yCoord, c.xCoord";
	
	if($res = $db->query($query))
		while($row = $res->nextRow(MYSQL_ASSOC))
			array_push($caveDetails, $row);
	return $caveDetails;
}

function getEmptyCell(){
  return array('HEADER' => array('text' => '&nbsp;'));
}

function getLegendCell($name, $value){
  return array('HEADER' => array('text' => "<small>$name: $value</small>"));
}

function getMapCell($map, $xCoord, $yCoord){
  if (!is_array($map[$xCoord][$yCoord]))
    return getEmptyCell();
  else
    return array('MAPCELL' => $map[$xCoord][$yCoord]);
}


// ADDED by chris--- for farmschutz

function getFarmschutz($playerID) {
  global $db, $params;

$query = "SELECT round( sum( r.average ) / count( r.average ) / 1.5 ) AS grenze, p.farmschutz AS protection, r2.average AS punkte FROM ranking r LEFT JOIN player p ON p.playerID = ".$playerID." LEFT JOIN ranking r2 ON p.playerID = r2.playerID GROUP BY p.playerID";
  if (!($result = $db->query($query)))
    page_dberror();
  if (!($myrow = $result->nextRow(MYSQL_ASSOC)))
//    page_dberror();
	return "Noch nicht berechenbar";

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

  return $row['farmschutz'];

}

?>