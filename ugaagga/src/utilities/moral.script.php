<?php
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$config = new Config();
$db     = new Db();

echo "STARTING MORAL UPDATE ";
echo "MORAL UPDATE: (".date("d.m.Y H:i:s",time()).") Running...";
/*{
  $query1 =
    "UPDATE Relation ".
    "SET moral = moral + SIGN(fame) ";
  if (RELATION_FAME_MIN_POINTS>0){
    $query1=$query1."WHERE fame > ".RELATION_FAME_MIN_POINTS;
  }
  $query2 =
    "UPDATE Relation ".
    "SET fame = 0";

  if (! $db->query($query1)) {
    echo "FAILED1.\n";
  }
  else {
    if (! $db->query($query2)) {
      echo "FAILED2.\n";
    }
    else {
      echo "SUCCESS.\n";
    }
    
  }
}
*/
echo "STARTING MORAL DECREASE ";
echo "MORAL DECREASE: (".date("d.m.Y H:i:s", time()).") Running...";
{
  $query =
    "UPDATE Player ".
    "SET fame = GREATEST(fame-  ".FAME_DECREASE_FACTOR." ,0)";

  if (! $db->query($query)) {
    echo "FAILED.\n";
  }
  else {
    echo "SUCCESS.\n";
  }
}

?>
