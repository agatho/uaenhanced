<?php
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
include INC_DIR."game_rules.php";

$config = new Config();
$db     = new Db();

init_buildings();
init_defenseSystems();
init_resources();
init_sciences();
init_units();

global $buildingTypeList,
       $defenseSystemTypeList,
       $resourceTypeList,
       $scienceTypeList,
       $unitTypeList;

///////////////////////////// constant values //////////////////////////////

// playerID => ranking_points
$constant_values = array (
);


echo "-----------------------------------------------------------------------\r\n";
echo "- RANKING LOG FILE ----------------------------------------------------\r\n";
echo "  vom " . date("r") . "\r\n";

// Ranking nach 2-Schritte-Prozess:

// 1. Teilbereiche ranken
// 2. Durchschnitt bilden +++NEU+++ Der Durchschnitt wird nun für drei Tage
//    aufbewahrt und daraus ein 3-Tage-Mittel bestimmt

// Die Teilbereiche fuer Schritt 1 lauten:

//  a.) Summe der Groessen der milit. Einheiten + Verteidigungsanlagen
//  ausgeschieden b.) Summe der Hoehlen
//  c.) Summe aller vorhandenen Rohstoffe
//  d.) Summe aller Gebaeude in allen Hoehlen
//  e.) Summe aller Entdeckungen
//  f.) Summe aller Artefakte

// ----------------------------------------------------------------------------
// Schritt (0.a.) alte Werte loeschen

  $query = "SELECT r.playerID FROM Ranking r LEFT JOIN Player p" .
           " ON p.playerID = r.playerID WHERE ISNULL(p.name) OR p.tribe LIKE '".GOD_ALLY."'";

  $db_deleted_players = $db->query($query);
  if (!$db_deleted_players){
    echo "Fehler beim Auslesen gelöschter Spieler in Schritt (0.a.i)\r\n";
    // was bitte soll die -17 heissen??? (-el)
    return -17;
  }

  $deleted_players = array();
  while($row = $db_deleted_players->nextrow()){
    array_push($deleted_players, $row['playerID']);
  }
  echo "Folgende SpielerIDs wurden aus der Ranking Tabelle gelöscht:<br>\r\n";

  for ($i = 0; $i < sizeof($deleted_players); ++$i){

    echo "playerID = " . $deleted_players[$i] . "<br>\r\n";

    $query = "DELETE FROM Ranking WHERE playerID = " . $deleted_players[$i];
    if (!$db->query($query)){
      echo "Fehler beim loeschen der alten Werte.\r\n";
      return -1;
    }

  }
// ----------------------------------------------------------------------------
// Schritt (0.b.) neue Werte eintragen

  $query = "INSERT IGNORE INTO Ranking (playerID, name, religion) ".
           "SELECT p.playerID, p.name, ".
           "CASE WHEN p.".DB_UGA_FIELDNAME." > p.".DB_AGGA_FIELDNAME." THEN 'uga' ".
                "WHEN p.".DB_UGA_FIELDNAME." < p.".DB_AGGA_FIELDNAME." THEN 'agga' ".
                "ELSE 'none' END AS religion ".
           "FROM Player p ".
           "WHERE p.tribe NOT LIKE '".GOD_ALLY."' ";

  if (!$db->query($query)) {echo $query;
    echo "Fehler beim Anlegen der neuen Werte.\r\n";
    return -2;
  }

// ----------------------------------------------------------------------------
// Schritt (0.c.) Banned Liste erstellen, von Spielern, die nicht ins Ranking sollen

  $query = "SELECT playerID, name FROM Player ".
           "WHERE tribe LIKE '".GOD_ALLY."'";

  $db_banned_players = $db->query($query);
  if (!$db_banned_players){
    echo "Fehler beim Anlegen der banned Liste. (0.c.)\r\n";
    return -17;
  }

  echo "Folgende SpielerIDs sind vom Ranking gebanned:<br>\r\n";
  $banned_players = array();
  while($row = $db_banned_players->nextrow(MYSQL_ASSOC)){
    array_push($banned_players, $row['playerID']);
    echo "ID : " . $row['playerID'] . " Name : " . $row['name'] . "<br>\r\n";
  }

// ----------------------------------------------------------------------------
// Schritt (0.d.) Religion und Ruhm erneuern

  $query = "SELECT playerID, fame, ".
           "CASE WHEN ".DB_UGA_FIELDNAME." > ".DB_AGGA_FIELDNAME." THEN 'uga' ".
                "WHEN ".DB_UGA_FIELDNAME." < ".DB_AGGA_FIELDNAME." THEN 'agga' ".
                "ELSE 'none' END AS religion ".
           "FROM Player ".
           "WHERE tribe NOT LIKE '".GOD_ALLY."'";

  $db_religion = $db->query($query);
  if (!$db_religion){
    echo "Fehler beim Auslesen der Religion. (0.d.)\r\n";
    return -1;
  }

  while($row = $db_religion->nextrow(MYSQL_ASSOC)){
    $sql = "UPDATE Ranking SET religion = '{$row['religion']}', fame = '".$row[fame]."' WHERE playerID = {$row['playerID']} ";
    if (!$db->query($sql)){
      echo "Fehler beim Eintragen der Religion und des Ruhmes. (0.d.)\r\n";
      return -1;
    }    
  }
 

// ----------------------------------------------------------------------------
// Schritt (1.a.) Summe der Groessen der milit. Einheiten + Verteidigungsanlagen

// Funktion zur Bewertung der Stärke einer milit. Einheit für das Ranking
function unit_rating ($unit) {
  return round(($unit->attackRange * 1.3 + $unit->attackAreal * 0.2 +
		$unit->attackRate + $unit->defenseRate +
		$unit->hitPoints) / 3);
}

$unitColNames = array();
for ($i = 0; $i < sizeof($unitTypeList); ++$i){
    array_push($unitColNames, unit_rating($unitTypeList[$i]) . " * " . $unitTypeList[$i]->dbFieldName);
}
$unitColNames  = implode(" + ", $unitColNames);

$defenseColNames = array();
for ($i = 0; $i < sizeof($defenseSystemTypeList); ++$i){
    array_push($defenseColNames, unit_rating($defenseSystemTypeList[$i]) . " * " . $defenseSystemTypeList[$i]->dbFieldName);
}
$defenseColNames = implode(" + ", $defenseColNames);

$military = array();

// zuerst Einheiten aus der Tabelle 'Cave' einfuegen
$query = "SELECT playerID, SUM(" . $unitColNames . " + " . $defenseColNames . ") AS military" .
         " FROM Cave" .
         " GROUP BY playerID" .
         " HAVING playerID != 0";

$db_unit_standing = $db->query($query);
if (!$db_unit_standing){
  echo "Fehler beim Auslesen in Schritt (1.a.i)\r\n";
  return -3;
}

while($row = $db_unit_standing->nextrow()){
  $military[$row['playerID']] += $row['military'];
}

// dann Einheiten aus der Tabelle 'Event_Movement' dazu addieren
$movingUnitColNames = array();
for ($i = 0; $i < sizeof($unitTypeList); ++$i){
    array_push($movingUnitColNames, unit_rating($unitTypeList[$i]) . " * m." . $unitTypeList[$i]->dbFieldName);
}
$movingUnitColNames  = implode(" + ", $movingUnitColNames);

$query = "SELECT c.playerID, m.caveID," .
         " SUM(" . $movingUnitColNames . ") AS military" .
         " FROM Event_movement m LEFT JOIN Cave c ON c.caveID = m.caveID" .
         " GROUP BY m.caveID" .
         " HAVING caveID != 0";

$db_unit_movement = $db->query($query);
if (!$db_unit_movement){
    echo "Fehler beim Auslesen in Schritt (1.a.ii)\r\n";
  return -4;
}

while($row = $db_unit_movement->nextrow()){
  $military[$row['playerID']] += $row['military'];
}

// military ranking

// first delete banned players from ranking
$military = unsetBanned($military, $banned_players);

$maxval = max($military) / 10000;
if (!$maxval)
  $maxval = 1;
foreach ($military as $playerID => $military){

  $query = "UPDATE Ranking SET military = " . $military .
           ", military_rank = " . floor($military/$maxval) .
           " WHERE playerID = " . $playerID;
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.a.iii)\r\n";
    return -5;
  }
}

// ----------------------------------------------------------------------------
// (1.b.) Summe der Hoehlen


$query = "UPDATE Ranking SET caves = 0";
if (!$db->query($query)){
  echo "Fehler beim Siedlungenzählen (1.b.)\r\n";
  return -1;
}

$query = "SELECT playerID, Count(caveID) AS anzahl FROM Cave GROUP BY playerID HAVING playerID != 0";

$db_caves = $db->query($query);
if (!$db_caves){
    echo "Fehler beim Siedlungenzaehlen in Schritt (1.b.i)\r\n";
  return -6;
}

$caves = array();
while($row = $db_caves->nextrow()){
  $caves[$row['playerID']] = $row['anzahl'];
}

$maxval = max($caves) / 10000;
foreach ($caves as $playerID => $anzahl){

  $query = "UPDATE Ranking SET caves = {$anzahl} WHERE playerID = {$playerID}";
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.b.ii)\r\n";
    return -7;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.c.) Summe aller vorhandenen Rohstoffe
// FIXME: Die Rohstoffe müssen gewichtet werden!
$resourcesColNames = array();
for ($i = 0; $i < sizeof($resourceTypeList); ++$i){
    array_push($resourcesColNames, $resourceTypeList[$i]->takeoverValue . " * " . $resourceTypeList[$i]->dbFieldName);
}
$resourcesColNames  = implode(" + ", $resourcesColNames);

$query = "SELECT playerID, SUM(" . $resourcesColNames . ") as resources" .
         " FROM Cave GROUP BY playerID HAVING playerID != 0";

$db_resources = $db->query($query);
if (!$db_resources){
    echo "Fehler beim Rohstoffe zaehlen in Schritt (1.c.i)\r\n";
  return -8;
}

$resources = array();
while($row = $db_resources->nextrow()){
  $resources[$row['playerID']] = $row['resources'];
}


// first delete banned players from ranking
$resources = unsetBanned($resources, $banned_players);

$maxval = max($resources) / 10000;
foreach ($resources as $playerID => $resources){

  $query = "UPDATE Ranking SET resources = " . $resources .
           ", resources_rank = " . floor($resources/$maxval) .
           " WHERE playerID = " . $playerID;
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.c.ii)\r\n";
    return -9;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.d.) Summe aller Gebaeude in allen Hoehlen

$buildingsColNames = array();
for ($i = 0; $i < sizeof($buildingTypeList); ++$i){
    array_push($buildingsColNames, $buildingTypeList[$i]->ratingValue . " * " . $buildingTypeList[$i]->dbFieldName);
}
$buildingsColNames  = implode(" + ", $buildingsColNames);


$query = "SELECT playerID, SUM(" . $buildingsColNames . ") as buildings" .
         " FROM Cave GROUP BY playerID HAVING playerID != 0";

$db_buildings = $db->query($query);
if (!$db_buildings){
    echo "Fehler beim Gebaeude zaehlen in Schritt (1.d.i)\r\n";
  return -10;
}

$buildings = array();
while($row = $db_buildings->nextrow()){
  $buildings[$row['playerID']] = $row['buildings'];
}

// first delete banned players from ranking
$buildings = unsetBanned($buildings, $banned_players);

$maxval = max($buildings) / 10000;
foreach ($buildings as $playerID => $buildings){

  $query = "UPDATE Ranking SET buildings = " . $buildings .
           ", buildings_rank = " . floor($buildings/$maxval) .
           " WHERE playerID = " . $playerID;
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.d.ii)\r\n";
    return -11;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.e.) Summe aller Entdeckungen

$sciencesColNames = array();
for ($i = 0; $i < sizeof($scienceTypeList); ++$i){
    array_push($sciencesColNames, $scienceTypeList[$i]->dbFieldName);
}
$sciencesColNames  = implode(" + ", $sciencesColNames);


$query = "SELECT playerID, (" . $sciencesColNames . ") AS sciences FROM Player ORDER BY sciences";

$db_sciences = $db->query($query);
if (!$db_sciences){
    echo "Fehler beim Wissenschaftszaehlen in Schritt (1.e.i)\r\n";
  return -12;
}

$sciences = array();
while($row = $db_sciences->nextrow()){
  $sciences[$row['playerID']] = $row['sciences'];
}

// first delete banned players from ranking
$sciences = unsetBanned($sciences, $banned_players);

$maxval = max($sciences) / 10000;
if (!$maxval) 
  $maxval = 1;
foreach ($sciences as $playerID => $sciences){

  $query = "UPDATE Ranking SET sciences = " . $sciences .
           ", sciences_rank = " . floor($sciences/$maxval) .
           " WHERE playerID = " . $playerID;
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.e.ii)\r\n";
    return -13;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.f.) Summe aller Artfakte

$query = "SELECT playerID, SUM(artefacts) AS artefacts FROM Cave " .
         "WHERE playerID != 0 GROUP BY playerID ORDER BY artefacts";

$db_artefacts = $db->query($query);
if (!$db_artefacts){
    echo "Fehler beim Artefaktzaehlen in Schritt (1.f.i)\r\n";
  return -1;
}

$artefacts = array();
while($row = $db_artefacts->nextrow()){
  $artefacts[$row['playerID']] = $row['artefacts'];
}

// first delete banned players from ranking
$artefacts = unsetBanned($artefacts, $banned_players);

$maxval = max($artefacts) / 10000;
if (!$maxval)
  $maxval = 1;
foreach ($artefacts as $playerID => $artefacts){

  $query = "UPDATE Ranking SET artefacts = " . $artefacts .
           ", artefacts_rank = " . floor($artefacts/$maxval) .
           " WHERE playerID = " . $playerID;
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.f.ii)\r\n";
    return -1;
  }
}

// ----------------------------------------------------------------------------
// Schritt (1.g.) Clanpunkte übertragen 

$query = "SELECT p.playerID, (t.points_sum / t.members) AS tribePoints, t.fame_rank AS tribeFame ".
         "FROM Player p ".
	 "LEFT JOIN RankingTribe t ON t.tribe LIKE p.tribe ".
	 "WHERE t.tribe IS NOT NULL";
	 

$db_tribePoints = $db->query($query);
if (!$db_tribePoints){
    echo "Fehler beim Finden der Clanpunkte in Schritt (1.g)\r\n" .$query . "\r\n";
  return -12;
}

$query = "SELECT MAX(points_sum / members) AS max ".
         "FROM RankingTribe ";
	 

$db_max = $db->query($query);
if (!$db_max || !($row = $db_max->nextRow())){
    echo "Fehler beim Finden der maximalen Clanpunkte in Schritt (1.g)\r\n" .$query . "\r\n";
  return -12;
}

$max = $row[max] ? $row[max] : 1;
$factor = 10000 / $max;

$tribePoints = array();
$tribeFame = array();
while($row = $db_tribePoints->nextRow()){
  $tribePoints[$row['playerID']] = $row['tribePoints'] * $factor ;
  $tribeFame[$row['playerID']] = $row['tribeFame'];
}

foreach ($tribePoints as $playerID => $value){

  $query = "UPDATE Ranking SET tribePoints = '" . $value . "', ".
           "tribeFame = '".$tribeFame[$playerID]."' ".
           "WHERE playerID = " . $playerID;
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen neuer Werte in Schritt (1.g)\r\n";
    return -13;
  }
}

// ----------------------------------------------------------------------------
// Schritt (2.a.) Durchschnitt bilden

$query = "UPDATE Ranking SET average_2 = average_1, average_1 = average_0," .
                           " playerPoints = SIGN(caves)*(4 * military_rank + 2 * resources_rank + 2*buildings_rank + sciences_rank) / 9," .
			   " average_0 = (38 * playerPoints + tribePoints + tribeFame) / 40, ".
                           " average   = (average_0 + average_1 + average_2)/3";
if (!$db->query($query)){
  echo "Fehler beim Einfuegen der durchschnittlichen Punktzahl (2.a.i)\r\n";
  return -14;
}


// ----------------------------------------------------------------------------
// Schritt (2.a.2) Constant ranking values

foreach ($constant_values AS $playerID => $value) {
  $query = "UPDATE Ranking ".
           "SET average = '$value' ".
           "WHERE playerID = '$playerID'";
  if (!$db->query($query)) {
    echo "Fehler beim Setzen der konstanten Punktzahl in (2.a.2)\r\n";
  }
  echo "PlayerID $playerID: Feste Punktzahl $value";
}


// ----------------------------------------------------------------------------
// Schritt (2.b.) Rang eintragen

$query = "SELECT playerID FROM Ranking ORDER BY average DESC";

$db_rank = $db->query($query);
if (!$db_rank){
    echo "Fehler beim Einfuegen des Rangs in Schritt (2.b.i)\r\n";
  return -15;
}
$count = 1;
while($row = $db_rank->nextrow()){
  $query = "UPDATE Ranking SET rank = " . $count++ . " WHERE playerID = " . $row['playerID'];
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen des Rangs in Schritt (2.b.ii)\r\n";
    return -16;
  }
}

// ***** FUNCTIONS *****
function unsetBanned($haystack, $banned){
  for ($banned_count = 0; $banned_count < sizeof($banned); ++$banned_count){
    unset($haystack[$banned[$banned_count]]);
  }
  return $haystack;
}

// -----------------------------------------------------------------------------
// TRIBE RANKING: STEP 1: Group tribes and accumulate average


if (!$db->query("DELETE FROM RankingTribe")) {
  echo "Error deleting old tribe ranks.";
  return -17;
}
$query =
  "INSERT INTO RankingTribe (tribe, points_sum, members, caves, fame, playerAverage) ".
  "SELECT t.tag, SUM(r.playerPoints), COUNT(r.playerID), SUM(r.caves), t.fame+SUM(p.fame), SUM(r.average) / COUNT(r.playerID) ".
  "FROM Tribe t ".
  "LEFT JOIN Player p ON p.tribe LIKE t.tag ".
  "LEFT JOIN Ranking r ON r.playerID = p.playerID ".
  "WHERE r.playerID IS NOT NULL ".
  "GROUP BY t.tag, t.fame ";

if (!$db->query($query)) { echo $query;
  echo "Error accumulating tribe points.\r\n";
  return -18;
}

// ---------------------------------------------------------------------------
// TRIBE RANKING: STEP 1.1: Normalizing the tribe points

$query =
  "SELECT MAX(points_sum) AS max, MAX(fame) AS maxFame, MIN(fame) AS minFame FROM ".
  "RankingTribe ";

if (!($result = $db->query($query))) { echo $query;
  echo "Error retrieving maximum of tribe points.\r\n";
  return -18;
}

$row = $result->nextRow();
$maxPoints = $row[max];
$maxFame = $row[maxFame];
$minFame = $row[minFame];

$fameRange = $maxFame - $minFame;

if ($maxPoints == 0) {
  $maxPoints = 1;
}
if ($fameRange == 0) {
  $fameRange = 1;
}
echo "Maximum tribe points: $maxPoints\r\n";
echo "fameRange: $fameRange\r\n";

$query =
  "UPDATE RankingTribe ".
  "SET fame_rank = (fame-$minFame) * 10000 / $fameRange, ".
  "points_rank = points_sum * 10000 / $maxPoints, ".
  "points = (2 * points_rank + fame_rank) / 3 ";

if (!$db->query($query)) { echo $query;
  echo "Error normalizing tribe points.\r\n";
  return -18;
}


// ----------------------------------------------------------------------------
// TRIBE RANKING: STEP 2: Calculate ranks

$query = "SELECT rankingID FROM RankingTribe ORDER BY points DESC";

$db_rank = $db->query($query);
if (!$db_rank){
    echo "Fehler beim Einfuegen des Rangs in Schritt (Tribe Ranking 2)\r\n";
  return -15;
}
$count = 1;
while($row = $db_rank->nextrow()){
  $query = "UPDATE RankingTribe SET rank = " . $count++ . " WHERE rankingID = " . $row['rankingID'];
  if (!$db->query($query)){
    echo "Fehler beim Einfuegen des Rangs in Schritt (Tribe Ranking 2)\r\n";
    return -16;
  }
}
// ADDED by chris--- for stats, 20.8.2004
// ----------------------------------------------------------------------------
// INSERTING update time to stats table

$query = "UPDATE stats SET ranking_date = '".date("YmdHis",time())."'";

if (!$db->query($query)) {
  echo "Fehler beim eintragen der Update-Zeit in die Stats-Tabelle!\r\n";
  return -100;
}


?>
