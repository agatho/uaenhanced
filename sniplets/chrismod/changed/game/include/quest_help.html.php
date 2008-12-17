<?
/*
 * effectWonderDetail.html.php - show active effects
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

function quest_getQuestHelp($playerID) {

// hm need to check this
  global $config,
         $params,
         $db;


  // open the template
  $template = @tmpl_open('./templates/' . $config->template_paths[$params->SESSION->user['template']] . '/quest_help.ihtml');


$data = array();

$data['QUESTHELP'] = array('description' => "Quests werden immer mit einer Bewegung begonnen und mit einer Bewegung abgeschlossen. " .
      " So k&ouml;nnt Ihr nur eine Quest " .
      " bekommen, wenn Ihr eine Questsiedlung besucht. Um eine Quest zu erhalten reicht es, mittels &quot;Rohstoffe bringen&quot; " .
      " dort eine Einheit vorbeizuschicken. Sie muß allerdings nicht zwingend eine Resource mitnehmen.<br><br>" .
      " Questsiedlungen sind  nicht gekennzeichnet und viele sind auch &uuml;berhaupt nicht als bewohnte Siedlungen zu erkennen. " .
      " Bei bestimmten Auftr&auml;gen k&ouml;nnen Siedlungen sichtbar werden, wo vorher Ein&ouml;den waren. In der Regel sind diese " .
      " dann nur f&uuml;r diejenigen sichtbar, die auch einen Auftrag f&uuml;r diese Siedlung bekommen haben.<br><br>" .
      " Manche Questsiedlungen sind ganz und gar unsichtbar und man kann sie nur durch Zufall entdecken, in dem man die Einöden absucht. " .
      " Auch hierf&uuml;r reicht irgendeine Bewegung aus. Entdeckte Questsiedlungen bleiben dann sichtbar.<br><br>" .
      " Es h&auml;ngen normalerweise mehrere Quests zusammen, d.h. nach einer abgeschlossenen Quest bekommt man oft eine n&auml;chste. " .
      " Questverl&auml;fe sind selten gradlinig, viele sind abh&auml;ngig von anderen Quests. Und ob andere Spieler diese gel&ouml;st haben " .
      " oder nicht, kann Deine Quests beeinflussen.<br><br>" .
      " Den Beginn eines Questweges kann grunds&auml;tzlich jeder Spieler bekommen, der eine Questsiedlung besuchen kann. Daf&uuml;r ist " .
      " allerdings je nach Entfernung mehr oder wengier Nahrung n&ouml;tig. Das ist beabsichtigt, da so mehrere Spieler gezwungen sind, " .
      " zusammenzuarbeiten. Auch k&ouml;nnen einige Quests von vielen Spieler erfolgreich abgeschlossen werden, andere, wie z.B. ein " .
      " bestimmtes Artefakt zu stehlen, kann nur einer gewinnen.<br><br>" .
      " Eine Quest kann f&uuml;nf Zust&auml;nde haben:<br>" .
      " <b>Aktiv:</b> Diese Quest(s) sind f&uuml;r Dich noch offen, das hei&szlig;t, weder Du noch jemand anders (bei einer QUest, die nur von " .
      " einem gel&ouml;st werden kann) hat sie bisher abgeschlossen.<br>" .
      " <b>Erfolgreich abgeschlossen:</b> Du hast die Quest gel&ouml;st.<br>" .
      " <b>Verloren:</b> Du hast diese Quest nicht l&ouml;sen k&ouml;nnen. M&ouml;gliche Gr&uuml;nde k&ouml;nnen sein, jemand anders ist dir " .
      " zuvorgekommen, du oder jemand anders hat etwas getan, wodurch diese Quest unl&ouml;sbar wurde (wie z.B. jemand hat dir ein " .
      " Quest-Artefakt geklaut, Du hast ein Quest-Artefakt eingweiht, was verboten war etc).<br>" .
      " Verlorene Quests k&ouml;nnen unter Umst&auml;nden wieder aufgenommen werden (wenn jemand dir ein Quest Artefakt geklaut hat, du " .
      " es aber zur&uuml;ck erobern konntest o.&auml;.).<br>" .
      " <b>Nicht mehr durchf&uuml;hrbar:</b> Die Quests hier k&ouml;nnen nicht mehr gel&ouml;st werden, da ein questrelevanter " .
      " Gegenstand verloren ist. Beispiel: Du hattest den Auftrag, eine Questeinheit in eine Siedlung zu bringen, aber durch einen Angriff " .
      " auf deine Siedlung, in der die EInheit stationiert war, wurde diese Einheit zerst&ouml;rt.<br>" .
      " Möglicherweise erscheinen hier f&uuml;r neue Spieler auch Quests, die andere Spieler schon gel&ouml;st haben.");

  tmpl_set($template, "/", $data );

  return tmpl_parse($template);

}

?>