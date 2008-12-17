<?php 
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";
global $config;




$config = new Config();

if (!($db_game =
      new Db($config->DB_GAME_HOST, $config->DB_GAME_USER,
             $config->DB_GAME_PWD, $config->DB_GAME_NAME))) {
  exit(1);
}
$db_game->query("UPDATE `RankingTribe` SET `points_rank` = (`points_rank` * 0.995) WHERE `points_rank` > 1520");
$db_game->query("UPDATE `RankingTribe` SET `points_rank` = (`points_rank` * 1.005) WHERE `points_rank` < 1480");
?>
