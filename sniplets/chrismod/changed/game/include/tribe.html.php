<?php

define("TRIBE_ACTION_JOIN",          1);
define("TRIBE_ACTION_CREATE",        2);
define("TRIBE_ACTION_LEAVE",         3);
define("TRIBE_ACTION_MESSAGE",       4);

function tribe_getContent($playerID, $tribe) {
  global
    $config,
    $params,
    $db,
    $no_resource_flag,
    $governmentList;

  $no_resource_flag = 1;

  // messages
  $messageText = array (
   -12 => "Der Clan befindet sich gerade im Krieg und darf daher im Moment".
          "keine neuen Mitglieder aufnehmen.",
   -11 => "Ihre Clanzugeh&ouml;rigkeit hat sich erst vor kurzem ".
          "ge&auml;ndert. Sie m&uuml;ssen noch warten, bis Sie wieder ".
          "etwas an Ihrer Clanzugeh&ouml;rigkeit &auml;ndern d&uuml;rfen.",
   -10 => "Ihr Clan befindet sich im Krieg. Sie d&uuml;rfen derzeit nicht ".
          "austreten.",
    -9 => "Die Nachricht konnte nicht eingetragen werden.",
    -8 => "Sie sind der Clananf&uuml;hrer und konnten nicht entfernt ".
          "werden.",
    -7 => "Das Passwort konnte nicht gesetzt werden!",
    -6 => "Der Clan konnte nicht angelegt werden.",
    -5 => "Es gibt schon einen Clan mit diesem K&uuml;rzel;",
    -4 => "Sie konnten nicht austreten. Vermutlich geh&ouml;ren Sie gar ".
          "keinem Clan an.",
    -3 => "Sie konnten dem Clan nicht beitreten. Vermutlich sind Sie schon ".
          "bei einem anderen Clan Mitglied.",
    -2 => "Passwort und Clank&uuml;rzel stimmen nicht &uuml;berein.",
    -1 => "Bei der Aktion ist ein unerwarteter Datenbankfehler aufgetreten!",
    01 => "Sie sind dem Clan beigetreten.",
    02 => "Sie haben den Clan verlassen.",
    03 => "Der Clan wurde erfolgreich angelegt.",
    04 => "Sie waren das letzte Mitglied, der Clan wurde aufgel&ouml;st",
    05 => "Die Nachricht wurde eingetragen",
    10 => "Dieser Clanname ist nicht erlaubt!");

  // proccess form data

  if ($params->POST->tribeAction)
  {
    switch ($params->POST->tribeAction) {
      case TRIBE_ACTION_JOIN:
	if ($params->POST->password &&       // insert necessary fields
	    $params->POST->tag)
	{
	  $messageID = tribe_processJoin($playerID,
					 $params->POST->tag,
					 $params->POST->password,
					 $db);
	}
	break;
      case TRIBE_ACTION_CREATE:
	if ($params->POST->password &&
	    $params->POST->tag)
	{
	  $messageID = tribe_processCreate($playerID,
					   $params->POST->tag,
					   $params->POST->password,
					   $db);
	}
	break;
      case TRIBE_ACTION_LEAVE:
	$messageID = tribe_processLeave($playerID,
					$tribe,
					$db);
	break;
      case TRIBE_ACTION_MESSAGE:
        if ($params->POST->messageText) {
          $messageID = tribe_processSendTribeMessage(
	                   $playerID,
                           $tribe,
                           $params->POST->messageText,
                           $db);
        }
        break;
    }

    if ($params->POST->tribeAction == TRIBE_ACTION_JOIN  ||
	$params->POST->tribeAction == TRIBE_ACTION_LEAVE ||
	$params->POST->tribeAction == TRIBE_ACTION_CREATE)
    {  // the tribe might have changed
      if (!page_refreshUserData()) {
        return "ERROR";
      }
      $tribe = $params->SESSION->user['tribe'];
    }

  }

// ----------------------------------------------------------------------------
// ------- SECTION FOR PLAYERS WITHOUT MEMBERSHIP -----------------------------

  if (! $tribe) {            // not a tribe member
    $template =
      @tmpl_open("./templates/" .
		 $config->template_paths[$params->SESSION->user['template']] .
		 "/tribe.ihtml");

    if ($messageID) {
      tmpl_set($template, "MESSAGE/message", $messageText[$messageID]);
    }

    // ------------------------------------------------------------------------
    // ----------- Join existing tribe ----------------------------------------

    tmpl_iterate($template, "FORM");

    $form = array(
      "heading"         => "Einem Clan beitreten",
      "modus_name"      => "modus",
      "modus_value"     => TRIBE,
      "action_name"     => "tribeAction",
      "action_value"    => TRIBE_ACTION_JOIN,

      "TAG/fieldname"   => "tag",
      "TAG/value"       => ($tribe ? $tribe : $params->POST->tag),
      "TAG/size"        => 8,
      "TAG/maxlength"   => 8,

      "PASSWORD/fieldname" => "password",
      "PASSWORD/value"     => $params->POST->password,
      "PASSWORD/size"      => 8,
      "PASSWORD/maxlength" => 15,

      "BUTTON/caption"  => "Beitreten"
      );

    tmpl_set($template, "FORM", $form);

    // ------------------------------------------------------------------------
    // ----------- Create new tribe -------------------------------------------

    tmpl_iterate($template, "FORM");

    // only change the different values for creation
    $form["heading"]        = "Einen neuen Clan gr&uuml;nden";
    $form["action_value"]   = TRIBE_ACTION_CREATE;
    $form["BUTTON/caption"] = "Neu gr&uuml;nden";

    tmpl_set($template, "FORM", $form);
  }


// ----------------------------------------------------------------------------
// ------- SECTION FOR TRIBE MEMBERS ------------- ----------------------------

  else {

    if (!($tribeData = tribe_getTribeByTag($tribe, $db))) {
      return "ERROR";
    }

    $template =
      @tmpl_open("./templates/" .
		 $config->template_paths[$params->SESSION->user['template']] .
		 "/tribeMember.ihtml");

    if ($messageID) {
      tmpl_set($template, "MESSAGE/message", $messageText[$messageID]);
    }

    if (tribe_isLeader($playerID, $tribe, $db)) {
      $adminData = array(
	"modus_name"        => "modus",
	"modus_value"       => TRIBE_ADMIN,
        "TRIBEMESSAGEFORM"  => array (
          "message_name"    => "messageText",
          "modus_name"      => "modus",
          "modus_value"     => TRIBE,
          "action_name"     => "tribeAction",
          "action_value"    => TRIBE_ACTION_MESSAGE
        )
      );
      tmpl_set($template, "ADMIN", $adminData);
    }

    $data = array(
      "tag"          => $tribe,
      "name"         => $tribeData['name'],
      "link_tribe"   => "modus=".TRIBE_DETAIL."&tribe=".urlencode(unhtmlentities($tribeData['tag'])),

      "MEMBERS/tag_name"      => "tag",
      "MEMBERS/tag_value"     => $tribe,
      "MEMBERS/modus_name"    => "modus",
      "MEMBERS/modus_value"   => TRIBE_PLAYER_LIST,

      "LEAVE/modus_name"      => "modus",
      "LEAVE/modus_value"     => TRIBE,
      "LEAVE/action_name"     => "tribeAction",
      "LEAVE/action_value"    => TRIBE_ACTION_LEAVE
      );

    if ($tribeData[leaderID]) {
      $leaderData = array (
	"LEADER/name"           => $tribeData[leaderName],
	"LEADER/leaderID_name"  => "detailID",
	"LEADER/leaderID_value" => $tribeData[leaderID],
	"LEADER/modus_name"     => "modus",
	"LEADER/modus_value"    => PLAYER_DETAIL
	);
    }
    else {
      $leaderData = array (
	"NOLEADER/message"      =>
	       "Ihr Clan hat zur Zeit keinen Anf&uuml;hrer."
	);
    }

    $leaderDeterminationData = array (
	"LEADERDETERMINATION/modus_name"     => "modus",
	"LEADERDETERMINATION/modus_value"    => TRIBE_LEADER_DETERMINATION
      );

    $governementData = array("GOVERNMENT/name" => $governmentList[$governmentData[governmentID]]);

    if ($messages=tribe_getTribeMessages($tribe, $db)) {
      foreach($messages AS $messageID => $messageData) {
	$message = array(
	  "time"          => $messageData[date],
	  "subject"       => $messageData[messageSubject],
	  "message"       => $messageData[messageText]);
	tmpl_iterate($template, "NORMAL/TRIBEMESSAGE");
	tmpl_set($template, "NORMAL/TRIBEMESSAGE", $message);

      }
    }

    $data = array_merge($data, $leaderData, $leaderDeterminationData,
			$governmentData);

    tmpl_set($template, "NORMAL", $data);
  }

  return tmpl_parse($template);
}

?>



