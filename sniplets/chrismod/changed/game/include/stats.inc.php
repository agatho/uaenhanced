<?

function stats_getStats()
{
  global
    $unitTypeList,
    $config,
    $params,
    $db;

  $stats = array();


// checking if the ticker is running

$file = "../utilities/ticker_status";

if (!file_exists($file)) $stats['ticker_status'] = "Unbekannt";
  else {
      // getting the filechangetime
     $time = getdate(filemtime($file));
     if ($time[mon] < 10) $time[mon] = "0".$time[mon];
     if ($time[mday] < 10) $time[mday] = "0".$time[mday];
     if ($time[hours] < 10) $time[hours] = "0".$time[hours];
     if ($time[minutes] < 10) $time[minutes] = "0".$time[minutes];
     if ($time[seconds] < 10) $time[seconds] = "0".$time[seconds];
     $modified = $time[year].$time[mon].$time[mday].$time[hours].$time[minutes].$time[seconds];

    if (filesize($file) < 1024) {
     // we need to update the stats table if it is 0 (eg the first time we found it down)
     $query = "SELECT ticker_downtime FROM stats";
     if (!($result = $db->query($query))) {
       echo "Database error!";
       return;
     }
     $row = $result->nextRow(MYSQL_ASSOC);
     if ($row[ticker_downtime] < 1) {
       // We need to update the status
       $query = "UPDATE stats SET ticker_downtime = ".$modified;
       if (!$db->query($query)) {
         echo "Database error!";
         return;
       }
     }
    $t = $modified;    
    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." - ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};
      $stats['ticker_status'] = "down since ".$time;
    } else {
      // oh the ticker is running, we need to reset the stats table
       $query = "UPDATE stats SET ticker_downtime = 0";
       if (!$db->query($query)) {
         echo "Database error!";
         return;
       }

// its running since...

    $file = "../utilities/ticker.pid";

    if (file_exists($file)) {

      $fp = @fopen($file,"r");
      $pid = fscanf($fp,"%d");
      $ticker_time = fscanf($fp,"Ticker start: %d");
      @fclose($fp);

      $ttime = getdate($ticker_time[0]);
      if ($ttime[mon] < 10) $ttime[mon] = "0".$ttime[mon];
      if ($ttime[mday] < 10) $ttime[mday] = "0".$ttime[mday];
      if ($ttime[hours] < 10) $ttime[hours] = "0".$ttime[hours];
      if ($ttime[minutes] < 10) $ttime[minutes] = "0".$ttime[minutes];
      if ($ttime[seconds] < 10) $ttime[seconds] = "0".$ttime[seconds];
      $running = $ttime[mday].".".$ttime[mon].".".$ttime[year]." - ".$ttime[hours].":".$ttime[minutes].":".$ttime[seconds];

    }

    $t = $modified;    
    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." - ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};
      $stats['ticker_status'] = "up and running since ".$running."&nbsp;&nbsp;&nbsp;(last check: ".$time.")";
    }


}






// -----------------------------------------------------------------------

  $query =
    "SELECT * ".
    "FROM stats ";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);


    $t = $row[runden_start];    
    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." - ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};

  $stats['runden_start'] = $time;

  $now = getUgaAggaTime(time());

  if ($now['moon'] == "z") $moon = "zunehmend";
  if ($now['moon'] == "a") $moon = "abnehmend";
  if ($now['moon'] == "n") $moon = "Neumond";
  if ($now['moon'] == "v") $moon = "Vollmond";

  $stats['uga_time'] = $now['day'] . ". Tag des " . getMonthName($now['month']) . "-Monats im Jahre " . $now['year'] . " um " . $now['hour'] . " Uhr. Mondphase: ". $moon .".";



$stats['kampfberichte'] = $row[kampfberichte];
$stats['spioberichte'] = $row[spioberichte];
$stats['takeover'] = $row[takeover_success];
$stats['max_active'] = $row[max_active];
$stats['max_date'] = $row[max_date];
$stats['wunder'] = $row[wunderberichte];

// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM player WHERE npcID = 0";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['spieler'] = $row[anzahl];

// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM tribe WHERE name != 'Astaroth'";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['clans'] = $row[anzahl];

// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM player WHERE tribe != 'Astaroth' AND tribe != ''";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['player_clans'] = $row[anzahl];

// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM player WHERE tribe != 'Astaroth' AND tribe = ''";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['player_noclan'] = $row[anzahl];

// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM ranking WHERE religion = 'none'";

  if (!($result = $db->query($query))) {
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$stats['player_noreligion'] = $row[anzahl];
if ($stats['player_noreligion'] < 1) $stats['player_noreligion'] = "keine";


// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM ranking WHERE religion = 'agga'";

  if (!($result = $db->query($query))) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['player_religion_agga'] = $row[anzahl];
if ($stats['player_religion_agga'] < 1) $stats['player_religion_agga'] = "keine";

// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM ranking WHERE religion = 'uga'";

  if (!($result = $db->query($query))) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['player_religion_uga'] = $row[anzahl];
if ($stats['player_religion_uga'] < 1) $stats['player_religion_uga'] = "keine";


// -------------------------------------------------------------------------

  $query = "SELECT count(*) AS anzahl FROM player WHERE science_hex > 1 AND npcID = 0";

  if (!($result = $db->query($query))) {
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['player_religion_hex'] = $row[anzahl];
if ($stats['player_religion_hex'] < 1) $stats['player_religion_hex'] = "keine";


// -------------------------------------------------------------------------

  $query = "SELECT ";

for($i = 0; $i < sizeof($unitTypeList); $i++) {
  $unit = $unitTypeList[$i]; // the current unit

if ($i > 0) $query .= "+ ";
  $query .= "sum(".$unit->dbFieldName.") ";
}

$query .= " AS anzahl FROM cave WHERE playerID != 1 AND playerID != 2 AND playerID != 3 AND playerID != 4 AND playerID != 5 AND playerID != 6 AND playerID != 7 AND playerID != 0 AND quest_cave = 0";

  if (!($result = $db->query($query)) || ($result->isEmpty())) {
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$stats['units'] = $row[anzahl];



// -------------------------------------------------------------------------

  $query = "SELECT ";

for($i = 0; $i < sizeof($unitTypeList); $i++) {
  $unit = $unitTypeList[$i]; // the current unit

if ($i > 0) $query .= "+ ";
  $query .= "sum(".$unit->dbFieldName.") ";
}

$query .= " AS anzahl FROM event_movement";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);
  $stats['units_moving'] = $row[anzahl];
if ($stats['units_moving'] < 1) $stats['units_moving'] = "keine";

// -------------------------------------------------------------------------

  $query = "SELECT messageID AS anzahl FROM message ORDER BY messageID DESC LIMIT 0 , 1";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }


  $row = $result->nextRow(MYSQL_ASSOC);

$stats['messages'] = $row[anzahl];
if ($stats['messages'] < 1) $stats['messages'] = "keine";

// -------------------------------------------------------------------------

  $query = "SELECT count( * ) AS anzahl FROM cave";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$stats['caves_all'] = $row[anzahl];
if ($stats['caves_all'] < 1) $stats['caves_all'] = "keine";

// -------------------------------------------------------------------------

  $query = "SELECT count( * ) AS anzahl FROM cave WHERE playerID >0 AND playerID != 1 AND playerID != 2 AND playerID != 3 AND playerID != 4 AND playerID != 5 AND playerID != 6 AND playerID != 7 AND quest_cave =0";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$stats['caves'] = $row[anzahl];
if ($stats['caves'] < 1) $stats['caves'] = "keine";

// -------------------------------------------------------------------------

  $query = "SELECT count( * ) AS anzahl FROM cave WHERE playerID = 0 AND takeoverable = 1";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$stats['caves_free'] = $row[anzahl];
if ($stats['caves_free'] < 1) $stats['caves_free'] = "keine";


// -------------------------------------------------------------------------

// Questions

  $query = "SELECT count(*) AS anzahl FROM questionnaire_questions";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$stats['questions'] = $row[anzahl];
if ($stats['questions'] < 1) $stats['questions'] = "keine";


// -------------------------------------------------------------------------

// aktive Spieler

$now = time()-10*60;

$timestamp = date("YmdHis",$now);

  $query = "SELECT count( * ) AS anzahl FROM session WHERE lastAction > ".$timestamp;

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$stats['user_active'] = $row[anzahl];
$active = $row[anzahl];
if ($stats['user_active'] < 1) $stats['user_active'] = "keine";


// checking max

if ($active > $stats['max_active']) {
  // new max, enter in db
  $now = time();
  $timestamp = date("YmdHis",$now);

  $query = "UPDATE stats SET max_active = ".$active.", max_date = ".$timestamp;
  if (!$db->query($query)) {
    echo "Database error!";
    return;
  }
  $stats['max_active'] = $active;
  $stats['max_date'] = $timestamp;
}

    $t = $stats['max_date'];    
    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." - ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};

  $stats['max_date'] = $time;


// -------------------------------------------------------------------------

// Höhlen anzahl

  $query = "SELECT count( * ) AS anzahl FROM cave WHERE playerID >0 AND playerID !=1 AND playerID != 2 AND playerID != 3 AND playerID != 4 AND playerID != 5 AND playerID != 6 AND playerID != 7 AND quest_cave =0 GROUP BY playerID";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  $count = 0;
  $count2 = 0;

  while($row = $result->nextrow(MYSQL_ASSOC)) {
    if ($row[anzahl] == 1) $count++;
    if ($row[anzahl] > 4) $count2++;
  }


if ($count < 1) $count2 = "keine";
$stats['one_cave'] = $count;

if ($count2 < 1) $count2 = "keine";
$stats['4_cave'] = $count2;



// -------------------------------------------------------------------------

// Artefakt anzahl

  $query = "SELECT a.artefactID, a.caveID, a.initiated, ac.name AS artefactname, ac.initiationID, c.name AS cavename, c.xCoord, c.yCoord, c.quest_cave, p.playerID, p.name, p.tribe FROM Artefact a LEFT  JOIN Artefact_class ac ON a.artefactClassID = ac.artefactClassID LEFT  JOIN Cave c ON a.caveID = c.caveID LEFT  JOIN Player p ON c.playerID = p.playerID WHERE p.playerID !=0 AND p.tribe !=  'Astaroth' AND c.quest_cave =0";

  $result = $db->query($query);

  $count = 0;

  while($row = $result->nextrow(MYSQL_ASSOC)) {
    $count++;
  }

$stats['artefact'] = $count;


// -------------------------------------------------------------------------

// Urlauber

  $query = "SELECT count(*) AS anzahl FROM player WHERE urlaub = 1";

  if (!($result = $db->query($query))) {
    echo "Database error!";
    return;
  }

  if ($result->isEmpty()) $stats['urlauber'] = "keine";
    else {
      $row = $result->nextrow(MYSQL_ASSOC);

      $stats['urlauber'] = $row[anzahl];
      if ($stats['urlauber'] < 1) $stats['urlauber'] = "keine";
    }




return $stats;

}
?>
