<?
function ranking_checkOffset($playerID, $offset){

  global $db;

  // get numRows of Ranking
  $query = "SELECT COUNT(*) AS num_rows FROM Ranking";

  $db_result = $db->query($query);
  if (!$db_result){
    return -1;
  }
  if (($row = $db_result->nextrow(MYSQL_ASSOC)) != FALSE){
    $num_rows = $row['num_rows'];
  } else {
    // something is real wrong, just return '-1' as error message
    return -1;
  }

  if (strval(intval($offset)) != $offset){
    // $offset is NOT a line number

    if (!isset($offset)){
      // $offset is not set yet, show the actual player in the middle of the list
      $query = "SELECT rank FROM Ranking WHERE playerID = " . $playerID;

    } else {
      // $offset is a player name
      $query = 'SELECT rank FROM Ranking WHERE name LIKE "' . $offset . '"';
    }

    $db_result = $db->query($query);
    if (!$db_result){
      return -1;
    }

    // if at least one record exists, get 'rank'
    if ($row = $db_result->nextrow()){
      $offset = $row['rank'] - (!isset($offset) ? floor(RANKING_ROWS/2) : 0);
    } else {
      $offset = 1;
    }
  }

  // the $offset is possibly out of bounds so make it right
  if ($offset < 1)
    $offset = 1;
  if ($offset > $num_rows)
    $offset = $num_rows;

  return $offset;
}

function rankingTribe_checkOffset($offset){

  global $db, $params; // ADDED by chris---: params

  // get numRows of Ranking
  $query = "SELECT COUNT(*) AS num_rows FROM RankingTribe";

  $db_result = $db->query($query);
  if (!$db_result){
    return -1;
  }
  if (($row = $db_result->nextrow(MYSQL_ASSOC)) != FALSE){
    $num_rows = $row['num_rows'];
  } else {
    // something is real wrong, just return '-1' as error message
    return -1;
  }

  if (!isset($offset))
// ADDED by chris---
//      $offset = 1;
    $query = 'SELECT rank FROM RankingTribe WHERE tribe LIKE "' . $params->SESSION->user['tribe'] . '"';

  if (strval(intval($offset)) != $offset){
    $query = 'SELECT rank FROM RankingTribe WHERE tribe LIKE "' . $offset . '"';

    $db_result = $db->query($query);
    if (!$db_result){
      return -1;
    }

    // if at least one record exists, get 'rank'
    if ($row = $db_result->nextrow()) {
      $offset = $row['rank'] ;
    } else {
      $offset = 1;
    }
  }

  // the $offset is possibly out of bounds so make it right
  if ($offset < 1)
    $offset = 1;
  if ($offset > $num_rows)
    $offset = $num_rows;

  return $offset;
}


// ADDED by chris--- for ranking sorting: $order
function ranking_getRowsByOffset($caveID, $offset, $order = "rank", $direct = "ASC"){

  global $db;


  $query = "SELECT r.rank, r.playerID AS link, r.name, r.average AS points, ".
           "r.religion, p.tribe, r.caves, r.fame, p.awards, ".
           "(IF(ISNULL(t.leaderID),0,r.playerID = t.leaderID)) AS is_leader ".
           "FROM Ranking r LEFT JOIN Player p ON r.playerID = p.playerID ".
           "LEFT JOIN Tribe t ON p.tribe = t.tag ".

// ADDED by chris--- for ranking sorting
//           "ORDER BY rank ASC LIMIT " . ($offset - 1) . ", " . RANKING_ROWS;
           "ORDER BY ".$order." ".$direct." LIMIT " . ($offset - 1) . ", " . RANKING_ROWS;

  $db_result = $db->query($query);
  if (!$db_result){
    return array();
  }

  $result = array();
  while ($row = $db_result->nextrow(MYSQL_ASSOC)){
    if (!empty($row['awards'])){
      $tmp = explode('|', $row['awards']);
      $awards = array();
      foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      $row['award'] = $awards;
    }
    $row['link']      = "?modus=" . PLAYER_DETAIL . "&detailID=" . $row['link'];
    $row['tribelink'] = "?modus=" . TRIBE_DETAIL  . "&tribe="    . urlencode(unhtmlentities($row['tribe']));

// ADDED by chris--- for religion
    if ($row['religion'] == "agga") $row['religion'] = "Dunkelheit";
    if ($row['religion'] == "uga") $row['religion'] = "Licht";

    $result[] = $row;
  }

  return $result;
}

function rankingTribe_getRowsByOffset($caveID, $offset, $order = "rank", $direct = "ASC"){

  global $db;


  $query = "SELECT r.*, r.playerAverage AS average, t.awards ".
           "FROM RankingTribe r LEFT JOIN Tribe t ON r.tribe = t.tag ".

// ADDED by chris--- for ranking sorting
//           "ORDER BY r.rank ASC LIMIT " . ($offset - 1) . ", " . RANKING_ROWS;
           "ORDER BY r.".$order." ".$direct." LIMIT " . ($offset - 1) . ", " . RANKING_ROWS;

  $db_result = $db->query($query);
  if (!$db_result){
    return array();
  }

  $result = array();
  while ($row = $db_result->nextrow(MYSQL_ASSOC)){
    if (!empty($row['awards'])){
      $tmp = explode('|', $row['awards']);
      $awards = array();
      foreach ($tmp AS $tag) $awards[] = array('tag' => $tag, 'award_modus' => AWARD_DETAIL);
      $row['award'] = $awards;
    }
    $row['link'] = "?modus=" . TRIBE_DETAIL . "&tribe=" . urlencode(unhtmlentities($row['tribe']));
    $result[] = $row;
  }

  return $result;
}

function ranking_getReligiousDistribution(){

  global $db;

  $query = "SELECT religion, SUM(average) as sum FROM Ranking WHERE religion NOT LIKE 'none' GROUP BY religion";

  $db_result = $db->query($query);
  if (!$db_result){
    return array();
  }

  $result = array();
  while ($row = $db_result->nextrow(MYSQL_ASSOC)){
    $result[$row['religion']] = $row['sum'];
  }

  return $result;
}

// ADDED by chris--- for ranking update time

function getUpdateTime() {

  global $db;

  $query = "SELECT ranking_date FROM stats";

  $db_result = $db->query($query);
  if (!$db_result){
    echo "Database error!";
    return;
  }

$row = $db_result->nextrow(MYSQL_ASSOC);
$upd_date = $row[ranking_date];

    $t = $upd_date;    
    $time = $t{6}.$t{7}  .".".
            $t{4}.$t{5}  .".".
            $t{0}.$t{1}  .
            $t{2}.$t{3}  ." - ".
            $t{8}.$t{9}  .":".
            $t{10}.$t{11}.":".
            $t{12}.$t{13};
return $time;
}
// --------------------------------------------------------------


// ADDED by chris--- for ranking points

function getRankingPoints($playerID) {

  global $db;

  $query = "SELECT military_rank, resources_rank, buildings_rank, sciences_rank FROM ranking WHERE playerID = ". $playerID;

  $db_result = $db->query($query);
  if (!$db_result){
    echo "Database error!";
    return;
  }

$row = $db_result->nextrow(MYSQL_ASSOC);

return $row;

}
// --------------------------------------------------------------
?>
