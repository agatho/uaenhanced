<?php
include "util.inc.php";

include INC_DIR."config.inc.php";
include INC_DIR."db.inc.php";

$config = new Config();
$db     = new Db();

$DAYS = 7; // how long should the messages be kept?

/*
 * this version of deleteOutdatedMessages is a lot slower than the
 * older one-query-solution, but doesn't block the message table
 * that long.
 */

echo "STARTING deleteOutdatedMessages.php\n";

$sql=
  "SELECT messageID ".
  "FROM Message";

if (!($result=$db->query($sql))) {
  echo "Error getting messages";
  exit;
}

echo "START to check every message\n";

while($row = $result->nextRow()) {
  $sql = 
    "SELECT messageID, senderID, recipientDeleted, senderDeleted, ".
    "messageTime < (NOW() - INTERVAL $DAYS DAY) + 0 AS outdated ".
    "FROM Message ".
    "WHERE messageID = '{$row['messageID']}'";
    
  if (!($result2 = $db->query($sql)) || ! ($message = $result2->nextRow())) {
    echo "ERROR Getting the message {$row['messageID']}\n";
    continue;
  }

  if ($message['outdated']) {
    echo "DELETED outdated message {$message['messageID']}\n";
    delete($message['messageID']);
  }
  else if ($message['recipientDeleted'] && $message['senderID'] == 0) {
    echo "DELETED deleted system message {$message['messageID']}\n";
    delete($message['messageID']);
  }
  else if ($message['recipientDeleted'] && $message['senderDeleted']) {
    echo "DELETED deleted player2player message {$message['messageID']}\n";
    delete($message['messageID']);
  }

  $result2->free();
}


function delete($messageID) {
  global $db;
  
  $sql = "DELETE FROM Message WHERE messageID = '$messageID'";
  if (!$db->query($sql)) {
    echo "FAILED.\n";
  }
}

?>
