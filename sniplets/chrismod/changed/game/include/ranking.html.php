<?
define("RANKING_ROWS", 10);

function ranking_getContent($caveID, $offset){
  global $no_resource_flag, $config, $params;

  $no_resource_flag = 1;

  $religions = ranking_getReligiousDistribution();

  if($religions['uga']+$religions['agga']!=0){
    $goodpercent = round($religions['uga']/($religions['uga'] + $religions['agga'])*100);
  }else{
    $goodpercent = 0;
  }

// ADDED by chris--- for ranking sorting
$order = $params->POST->order;
if ($order != "rank" && $order != "caves" && $order != "name" && $order != "points" && $order != "tribe" && $order != "fame") $order = "rank";

$direct = $params->POST->direct;
if ($direct != "ASC" && $direct != "DESC") $direct = "ASC";



  $row = ranking_getRowsByOffset($caveID, $offset, $order, $direct);


// ADDED by chris--- for ranking sorting
//  $up   = array('link' => "?modus=" . RANKING . "&offset=" . ($offset - RANKING_ROWS));
//  $down = array('link' => "?modus=" . RANKING . "&offset=" . ($offset + RANKING_ROWS));

$up_link = "?modus=" . RANKING . "&offset=" . ($offset - RANKING_ROWS);
$down_link = "?modus=" . RANKING . "&offset=" . ($offset + RANKING_ROWS);

if ($order) {
  $up_link .= "&amp;order=".$order;
  $down_link .= "&amp;order=".$order;
}
if ($direct) {
  $up_link .= "&amp;direct=".$direct;
  $down_link .= "&amp;direct=".$direct;
}

$up   = array('link' => $up_link);
$down = array('link' => $down_link);

// Header links
$link_rank = "?modus=" . RANKING . "&amp;order=rank";
$link_name = "?modus=" . RANKING . "&amp;order=name";
$link_caves = "?modus=" . RANKING . "&amp;order=caves";
$link_points = "?modus=" . RANKING . "&amp;order=points";
$link_tribe = "?modus=" . RANKING . "&amp;order=tribe";
$link_fame = "?modus=" . RANKING . "&amp;order=fame";

if ($direct) {
  $link_rank .= "&amp;direct=" . $direct;
  $link_name .= "&amp;direct=" . $direct;
  $link_caves .= "&amp;direct=" . $direct;
  $link_points .= "&amp;direct=" . $direct;
  $link_tribe .= "&amp;direct=" . $direct;
  $link_fame .= "&amp;direct=" . $direct;
}

if ($order == "rank" && $direct == "ASC") $link_rank = "?modus=" . RANKING . "&amp;order=rank&amp;direct=DESC";
if ($order == "rank" && $direct == "DESC") $link_rank = "?modus=" . RANKING . "&amp;order=rank&amp;direct=ASC";
if ($order == "name" && $direct == "ASC") $link_name = "?modus=" . RANKING . "&amp;order=name&amp;direct=DESC";
if ($order == "name" && $direct == "DESC") $link_name = "?modus=" . RANKING . "&amp;order=name&amp;direct=ASC";
if ($order == "caves" && $direct == "ASC") $link_caves = "?modus=" . RANKING . "&amp;order=caves&amp;direct=DESC";
if ($order == "caves" && $direct == "DESC") $link_caves = "?modus=" . RANKING . "&amp;order=caves&amp;direct=ASC";
if ($order == "points" && $direct == "ASC") $link_points = "?modus=" . RANKING . "&amp;order=points&amp;direct=DESC";
if ($order == "points" && $direct == "DESC") $link_points = "?modus=" . RANKING . "&amp;order=points&amp;direct=ASC";
if ($order == "tribe" && $direct == "ASC") $link_tribe = "?modus=" . RANKING . "&amp;order=tribe&amp;direct=DESC";
if ($order == "tribe" && $direct == "DESC") $link_tribe = "?modus=" . RANKING . "&amp;order=tribe&amp;direct=ASC";
if ($order == "fame" && $direct == "ASC") $link_fame = "?modus=" . RANKING . "&amp;order=fame&amp;direct=DESC";
if ($order == "fame" && $direct == "DESC") $link_fame = "?modus=" . RANKING . "&amp;order=fame&amp;direct=ASC";

// ------------------------------------------------------


  $hidden = array(array('name' => "modus", 'value' => RANKING));

// ADDED by chris--- for ranking points
$points = getRankingPoints($params->SESSION->user['playerID']);

  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/ranking.ihtml');

  tmpl_set($template, array('modus'      => RANKING_TRIBE,
                            'UP'         => $up,
                            'DOWN'       => $down,
                            'HIDDEN'     => $hidden,
			    'update'	=> getUpdateTime(), // ADDED by chris--- for update time
			    'points_military' => $points[military_rank], // ADDED by chris--- for ranking points
			    'points_resources' => $points[resources_rank],
			    'points_buildings' => $points[buildings_rank],
			    'points_sciences'  => $points[sciences_rank],
                            'RELIGIOUS_DISTRIBUTION' => array('goodpercent' => $goodpercent, 'badpercent' => 100 - $goodpercent),

// ADDED by chris--- for ranking sorting
'link_rank'	=> $link_rank,
'link_caves'	=> $link_caves,
'link_points'	=> $link_points,
'link_tribe'	=> $link_tribe,
'link_fame'	=> $link_fame,
'link_name'	=> $link_name
));

  for ($i = 0; $i < sizeof($row); ++$i){
    tmpl_iterate($template, 'ROWS');
    if ($i % 2) tmpl_set($template, 'ROWS/ROW_ALTERNATE', $row[$i]);
    else tmpl_set($template, 'ROWS/ROW', $row[$i]);
  }

  return tmpl_parse($template);
}

function rankingTribe_getContent($caveID, $offset){
  global $no_resource_flag, $config, $params;

  $no_resource_flag = 1;

// ADDED by chris--- for ranking sorting
$order = $params->POST->order;
if ($order != "rank" && $order != "tribe" && $order != "points" && $order != "fame" && $order != "points_rank" && $order != "members" && $order != "playerAverage" && $order != "caves") $order = "rank";

$direct = $params->POST->direct;
if ($direct != "ASC" && $direct != "DESC") $direct = "ASC";



  $row = rankingTribe_getRowsByOffset($caveID, $offset, $order, $direct);

// ADDED by chris--- for ranking sorting
//  $up   = array('link' => "?modus=" . RANKING_TRIBE . "&offset=" . ($offset - RANKING_ROWS));
//  $down = array('link' => "?modus=" . RANKING_TRIBE . "&offset=" . ($offset + RANKING_ROWS));

$up_link = "?modus=" . RANKING_TRIBE . "&offset=" . ($offset - RANKING_ROWS);
$down_link = "?modus=" . RANKING_TRIBE . "&offset=" . ($offset + RANKING_ROWS);

if ($order) {
  $up_link .= "&amp;order=".$order;
  $down_link .= "&amp;order=".$order;
}
if ($direct) {
  $up_link .= "&amp;direct=".$direct;
  $down_link .= "&amp;direct=".$direct;
}

$up   = array('link' => $up_link);
$down = array('link' => $down_link);

// Header links
$link_rank = "?modus=" . RANKING_TRIBE . "&amp;order=rank";
$link_points_rank = "?modus=" . RANKING_TRIBE . "&amp;order=points_rank";
$link_caves = "?modus=" . RANKING_TRIBE . "&amp;order=caves";
$link_points = "?modus=" . RANKING_TRIBE . "&amp;order=points";
$link_tribe = "?modus=" . RANKING_TRIBE . "&amp;order=tribe";
$link_fame = "?modus=" . RANKING_TRIBE . "&amp;order=fame";
$link_members = "?modus=" . RANKING_TRIBE . "&amp;order=members";
$link_playerAverage = "?modus=" . RANKING_TRIBE . "&amp;order=playerAverage";

if ($direct) {
  $link_rank .= "&amp;direct=" . $direct;
  $link_points_rank .= "&amp;direct=" . $direct;
  $link_caves .= "&amp;direct=" . $direct;
  $link_points .= "&amp;direct=" . $direct;
  $link_tribe .= "&amp;direct=" . $direct;
  $link_fame .= "&amp;direct=" . $direct;
  $link_members .= "&amp;direct=" . $direct;
  $link_playerAverage .= "&amp;direct=" . $direct;
}

if ($order == "rank" && $direct == "ASC") $link_rank = "?modus=" . RANKING_TRIBE . "&amp;order=rank&amp;direct=DESC";
if ($order == "rank" && $direct == "DESC") $link_rank = "?modus=" . RANKING_TRIBE . "&amp;order=rank&amp;direct=ASC";
if ($order == "points_rank" && $direct == "ASC") $link_points_rank = "?modus=" . RANKING_TRIBE . "&amp;order=points_rank&amp;direct=DESC";
if ($order == "points_rank" && $direct == "DESC") $link_points_rank = "?modus=" . RANKING_TRIBE . "&amp;order=points_rank&amp;direct=ASC";
if ($order == "caves" && $direct == "ASC") $link_caves = "?modus=" . RANKING_TRIBE . "&amp;order=caves&amp;direct=DESC";
if ($order == "caves" && $direct == "DESC") $link_caves = "?modus=" . RANKING_TRIBE . "&amp;order=caves&amp;direct=ASC";
if ($order == "points" && $direct == "ASC") $link_points = "?modus=" . RANKING_TRIBE . "&amp;order=points&amp;direct=DESC";
if ($order == "points" && $direct == "DESC") $link_points = "?modus=" . RANKING_TRIBE . "&amp;order=points&amp;direct=ASC";
if ($order == "tribe" && $direct == "ASC") $link_tribe = "?modus=" . RANKING_TRIBE . "&amp;order=tribe&amp;direct=DESC";
if ($order == "tribe" && $direct == "DESC") $link_tribe = "?modus=" . RANKING_TRIBE . "&amp;order=tribe&amp;direct=ASC";
if ($order == "members" && $direct == "ASC") $link_members = "?modus=" . RANKING_TRIBE . "&amp;order=members&amp;direct=DESC";
if ($order == "members" && $direct == "DESC") $link_members = "?modus=" . RANKING_TRIBE . "&amp;order=members&amp;direct=ASC";
if ($order == "fame" && $direct == "ASC") $link_fame = "?modus=" . RANKING_TRIBE . "&amp;order=fame&amp;direct=DESC";
if ($order == "fame" && $direct == "DESC") $link_fame = "?modus=" . RANKING_TRIBE . "&amp;order=fame&amp;direct=ASC";
if ($order == "playerAverage" && $direct == "ASC") $link_playerAverage = "?modus=" . RANKING_TRIBE . "&amp;order=playerAverage&amp;direct=DESC";
if ($order == "playerAverage" && $direct == "DESC") $link_playerAverage = "?modus=" . RANKING_TRIBE . "&amp;order=playerAverage&amp;direct=ASC";

// ------------------------------------------------------


  $hidden = array(array('name' => "modus", 'value' => RANKING_TRIBE));


  $template = @tmpl_open('./templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/rankingTribe.ihtml');

  for ($i = 0; $i < sizeof($row); ++$i){
    tmpl_iterate($template, 'ROWS');
    if ($i % 2) tmpl_set($template, 'ROWS/ROW_ALTERNATE', $row[$i]);
    else tmpl_set($template, 'ROWS/ROW', $row[$i]);
  }

  tmpl_set($template, array('modus'  => RANKING,
                            'UP'     => $up,
                            'DOWN'   => $down,
			    'update' => getUpdateTime(), // ADDED by chris--- for update time
                            'HIDDEN' => $hidden,

// ADDED by chris--- for ranking sorting
'link_rank'	=> $link_rank,
'link_caves'	=> $link_caves,
'link_points'	=> $link_points,
'link_tribe'	=> $link_tribe,
'link_fame'	=> $link_fame,
'link_points_rank'	=> $link_points_rank,
'link_members'	=> $link_members,
'link_playerAverage'	=> $link_playerAverage
));

  return tmpl_parse($template);
}
?>
