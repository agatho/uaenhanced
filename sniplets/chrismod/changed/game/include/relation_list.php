<?php
/* ***** Keine ***** */
$relationList[0]['relationID']  = 0;
$relationList[0]['name']        = "Keine";
$relationList[0]['targetMinSize'] = "0";
$relationList[0]['dontLeaveTribe'] = "0";
$relationList[0]['storeTargetMembers'] = "0";
$relationList[0]['attackerReceivesFame'] = "0";
$relationList[0]['defenderReceivesFame'] = "1";
$relationList[0]['fameUpdate'] = "0";
$relationList[0]['description'] = "<p>Es gibt keinerlei Beziehungen zu einem anderen Clan. Jegliche bereits bestehende Beziehungen werden beendet.</p>";
$relationList[0]['transitions'] = array(1 => array('relationID' => 1,
                                              'time'    => 24),
                                        5 => array('relationID' => 5,
                                              'time'    => 24),
                                        6 => array('relationID' => 6,
                                              'time'    => 24),
                                        7 => array('relationID' => 7,
                                              'time'    => 24));

$relationList[0]['otherSideTo'] = -1;
$relationList[0]['attackerMultiplicator']        = 1;
$relationList[0]['defenderMultiplicator']        = 1;

/* ***** Ultimatum ***** */
$relationList[1]['relationID']  = 1;
$relationList[1]['name']        = "Ultimatum";
$relationList[1]['targetMinSize'] = "0";
$relationList[1]['dontLeaveTribe'] = "0";
$relationList[1]['storeTargetMembers'] = "0";
$relationList[1]['attackerReceivesFame'] = "0";
$relationList[1]['defenderReceivesFame'] = "0";
$relationList[1]['fameUpdate'] = "0";
$relationList[1]['description'] = "<p>Dem anderen Clan werden Bedingungen gestellt, deren Nichterf&uuml;llung zumeist im Krieg endet.</p>";
$relationList[1]['transitions'] = array(0 => array('relationID' => 0,
                                              'time'    => 0),
                                        2 => array('relationID' => 2,
                                              'time'    => 96),
                                        5 => array('relationID' => 5,
                                              'time'    => 24));
$relationList[1]['historyMessage']="Der Clan [TRIBE] stellt dem Clan [TARGET] ein Ultimatum.";
$relationList[1]['otherSideTo'] = -1;
$relationList[1]['attackerMultiplicator']        = 0.7;
$relationList[1]['defenderMultiplicator']        = 1.3;

/* ***** Krieg ***** */
$relationList[2]['relationID']  = 2;
$relationList[2]['name']        = "Krieg";
$relationList[2]['targetMinSize'] = "0";
$relationList[2]['dontLeaveTribe'] = "0";
$relationList[2]['storeTargetMembers'] = "0";
$relationList[2]['attackerReceivesFame'] = "1";
$relationList[2]['defenderReceivesFame'] = "1";
$relationList[2]['fameUpdate'] = "1";
$relationList[2]['description'] = "<p>Krieg zwischen den Clans.</p>";
$relationList[2]['transitions'] = array(3 => array('relationID' => 3,
                                              'time'    => 72));
$relationList[2]['historyMessage']="Der Clan [TRIBE] erkl&auml;rt dem Clan [TARGET] den Krieg.";
$relationList[2]['otherSideTo'] = 2;
$relationList[2]['attackerMultiplicator']        = 1.3;
$relationList[2]['defenderMultiplicator']        = 1.1;

/* ***** Kapitulation ***** */
$relationList[3]['relationID']  = 3;
$relationList[3]['name']        = "Kapitulation";
$relationList[3]['targetMinSize'] = "0";
$relationList[3]['dontLeaveTribe'] = "0";
$relationList[3]['storeTargetMembers'] = "0";
$relationList[3]['attackerReceivesFame'] = "0";
$relationList[3]['defenderReceivesFame'] = "0";
$relationList[3]['fameUpdate'] = "0";
$relationList[3]['description'] = "<p>Der unterlegende Clan hat oder wurde kapituliert.</p>";
$relationList[3]['transitions'] = array(0 => array('relationID' => 0,
                                              'time'    => 0));
$relationList[3]['historyMessage']="Der Clan [TRIBE] gesteht schm&auml;chlich seine Niederlage gegen&uuml;ber dem Clan [TARGET] ein.";
$relationList[3]['otherSideTo'] = 4;
$relationList[3]['attackerMultiplicator']        = 0.1;
$relationList[3]['defenderMultiplicator']        = 1;

/* ***** Besatzung ***** */
$relationList[4]['relationID']  = 4;
$relationList[4]['name']        = "Besatzung";
$relationList[4]['targetMinSize'] = "0";
$relationList[4]['dontLeaveTribe'] = "0";
$relationList[4]['storeTargetMembers'] = "0";
$relationList[4]['attackerReceivesFame'] = "0";
$relationList[4]['defenderReceivesFame'] = "0";
$relationList[4]['fameUpdate'] = "0";
$relationList[4]['description'] = "<p>Der unterlegende Clan ist besetzt.</p>";
$relationList[4]['transitions'] = array(0 => array('relationID' => 0,
                                              'time'    => 0),
                                        5 => array('relationID' => 5,
                                              'time'    => 24));
$relationList[4]['historyMessage']="Glorreicher Sieg des Clans [TARGET] &uuml;ber den Clan [TRIBE].";
$relationList[4]['otherSideTo'] = -1;
$relationList[4]['attackerMultiplicator']        = 0.7;
$relationList[4]['defenderMultiplicator']        = 1;

/* ***** Waffenstillstand ***** */
$relationList[5]['relationID']  = 5;
$relationList[5]['name']        = "Waffenstillstand";
$relationList[5]['targetMinSize'] = "0";
$relationList[5]['dontLeaveTribe'] = "0";
$relationList[5]['storeTargetMembers'] = "0";
$relationList[5]['attackerReceivesFame'] = "0";
$relationList[5]['defenderReceivesFame'] = "0";
$relationList[5]['fameUpdate'] = "0";
$relationList[5]['description'] = "<p>Die Clans bekriegen sich zur Zeit nicht. Das kann sich aber schlagartig &auml;ndern.</p>";
$relationList[5]['transitions'] = array(0 => array('relationID' => 0,
                                              'time'    => 0),
                                        1 => array('relationID' => 1,
                                              'time'    => 16),
                                        6 => array('relationID' => 6,
                                              'time'    => 24));
$relationList[5]['historyMessage']="Der Clan [TRIBE] schlie&szlig;t Waffenstillstand mit dem Clan [TARGET].";
$relationList[5]['otherSideTo'] = -1;
$relationList[5]['attackerMultiplicator']        = 0.7;
$relationList[5]['defenderMultiplicator']        = 1;

/* ***** Nichtangriffspakt ***** */
$relationList[6]['relationID']  = 6;
$relationList[6]['name']        = "Nichtangriffspakt";
$relationList[6]['targetMinSize'] = "0";
$relationList[6]['dontLeaveTribe'] = "0";
$relationList[6]['storeTargetMembers'] = "0";
$relationList[6]['attackerReceivesFame'] = "0";
$relationList[6]['defenderReceivesFame'] = "0";
$relationList[6]['fameUpdate'] = "0";
$relationList[6]['description'] = "<p>Die Clans versprechen sich gegenseitig sich nicht anzugreifen.</p>";
$relationList[6]['transitions'] = array(1 => array('relationID' => 1,
                                              'time'    => 32),
                                        5 => array('relationID' => 5,
                                              'time'    => 16),
                                        7 => array('relationID' => 7,
                                              'time'    => 24));
$relationList[6]['historyMessage']="Der Clan [TRIBE] schlie&szlig;t einen Nichtangriffspakt mit dem Clan [TARGET].";
$relationList[6]['otherSideTo'] = -1;
$relationList[6]['attackerMultiplicator']        = 0.5;
$relationList[6]['defenderMultiplicator']        = 0.9;

/* ***** B&uuml;ndnis ***** */
$relationList[7]['relationID']  = 7;
$relationList[7]['name']        = "B&uuml;ndnis";
$relationList[7]['targetMinSize'] = "0";
$relationList[7]['dontLeaveTribe'] = "0";
$relationList[7]['storeTargetMembers'] = "0";
$relationList[7]['attackerReceivesFame'] = "0";
$relationList[7]['defenderReceivesFame'] = "0";
$relationList[7]['fameUpdate'] = "0";
$relationList[7]['description'] = "<p>Gegen den Feind verb&uuml;ndet.</p>";
$relationList[7]['transitions'] = array(1 => array('relationID' => 1,
                                              'time'    => 72),
                                        5 => array('relationID' => 5,
                                              'time'    => 56),
                                        6 => array('relationID' => 6,
                                              'time'    => 40));
$relationList[7]['historyMessage']="Der Clan [TRIBE] schlie&szlig;t ein B&uuml;ndnis mit dem Clan [TARGET].";
$relationList[7]['otherSideTo'] = -1;
$relationList[7]['attackerMultiplicator']        = 0.2;
$relationList[7]['defenderMultiplicator']        = 0.8;
?>