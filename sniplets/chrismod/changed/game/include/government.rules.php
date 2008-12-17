<?php

/* ***** Macht des St&auml;rkeren ***** */
$leaderDeterminationList[1]['leaderDeterminationID']  = "1";
$leaderDeterminationList[1]['name']        = "Macht des St&auml;rkeren";
$leaderDeterminationList[1]['description'] = "<p>Clanf&uuml;hrer wird der st&auml;rkste Spieler. Solange, bis ein anderer st&auml;rker wird</p>";

/* ***** Mehrheitsentscheid ***** */
$leaderDeterminationList[2]['leaderDeterminationID']  = "2";
$leaderDeterminationList[2]['name']        = "Mehrheitsentscheid";
$leaderDeterminationList[2]['description'] = "<p>Um Clanf&uuml;hrer zu werden, m&uuml;ssen mehr als 50% der Clanangeh&ouml;rigen f&uuml;r einen stimmen.</p>";

/* ***** Anarchie ***** */
$governmentList[1]['governmentID']  = "1";
$governmentList[1]['name']        = "Anarchie";
$governmentList[1]['resref']      = "";
$governmentList[1]['leaderDeterminationID']      = "1";
$governmentList[1]['description'] = "<p>Die Einheiten sind immer kapmfbereit, die Kampfwerte leicht erh&ouml;ht. Durch das Training haben die Clanangeh&ouml;rigen allerdings weniger Zeit, was sich auf die Produktion niederschl&auml;gt.</p>";
$governmentList[1]['effects']     = array();

/* ***** Demokratie ***** */
$governmentList[2]['governmentID']  = "2";
$governmentList[2]['name']        = "Demokratie";
$governmentList[2]['resref']      = "";
$governmentList[2]['leaderDeterminationID']      = "2";
$governmentList[2]['description'] = "<p>Der Clanf&uuml;hrer wird per Mehrheitsentscheid bestimmt, hat dann aber alle Macht, bis er abgew&auml;hlt wird. Die Kampfwerte leiden etwas, die Produktion floriert daf&uuml;r.</p>";
$governmentList[2]['effects']     = array();

?>