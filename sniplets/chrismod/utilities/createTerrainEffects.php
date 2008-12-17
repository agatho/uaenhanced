<?
  include "util.inc.php";

  include INC_DIR."config.inc.php";
  include INC_DIR."db.inc.php";
  include INC_DIR."game_rules.php";

$config = new Config();
$db = new Db();

// set memory limit
ini_set("memory_limit", "32M");

/***************************************************************************/
/* SETUP EFFECTS                                                           */
/***************************************************************************/

// effect for plains
$effect[0][0] = "effect_food_factor = '0.05'";
$effect[0][1] = "effect_population_factor = '0.05'";

// effect for forrest
$effect[1][0] = "effect_wood_factor = '0.05'";
$effect[1][1] = "effect_food_factor = '0.05'";

// effect for swamp
$effect[2][0] = "effect_sulfur_factor = '0.08'";
$effect[2][1] = "effect_wood_factor = '0.02'";

// effect for mountains
$effect[3][0] = "effect_stone_factor = '0.07'";
$effect[3][1] = "effect_metal_factor = '0.03'";



/***************************************************************************/
/* QUERY                                                                   */
/***************************************************************************/

for ($i=0;$i<4;$i++) {
  for ($x=0;$x<count($effect[$i]);$x++) {
    $query = "UPDATE Cave SET ".$effect[$i][$x]." WHERE terrain = ".$i;
    echo $query."\r\n";
    if (!$db->query($query)){
      echo "Fehler beim Eintragen der Terraineffekte!\r\n";
      echo "QUERY WAS: " . $query;
      exit;
    }
  }
}









?> 
