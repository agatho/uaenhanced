<?php
require_once("./include/page.inc.php");
require_once("./include/db.functions.php");
require_once("./include/time.inc.php");
require_once("./include/basic.lib.php");
require_once("./include/config.inc.php");

page_start();

/* ***** INPUT VAR CHECKS ************************************************** */

// get the caveID out of the Session var 'caveID', which is ONLY set in the ugastart.php
$caveID = $params->SESSION->caveID;

$meineHoehlen = getCaves($params->SESSION->user['playerID']);

/* ************************************************************************* */
$menuicons = array();
$menuicons[] = array('INGAME' => array('modus' => CAVE_DETAIL,
                                       'file'  => 'cave_detail',
                                       'text'  => 'Bericht über diese Siedlung'));
$menuicons[] = array('INGAME' => array('modus' => ARTEFACT_LIST,
                                       'file'  => 'artefacts',
                                       'text'  => 'Artefakt Liste'));
$menuicons[] = array('INGAME' => array('modus' => TRIBE,
                                       'file'  => 'my_tribe',
                                       'text'  => 'Mein Clan'));
$menuicons[] = array('INGAME' => array('modus' => EFFECTWONDER_DETAIL,
                                       'file'  => 'active_effects',
                                       'text'  => 'Aktive Effekte und Zauber'));
$menuicons[] = array('INGAME' => array('modus' => USER_PROFILE,
                                       'file'  => 'profile',
                                       'text'  => 'Profil'));
$menuicons[] = array('INGAME' => array('modus' => QUESTIONNAIRE,
                                       'file'  => 'questionnaire',
                                       'text'  => 'Fragebogen'));
$menuicons[] = array('EXTERN' => array('link'  => HELP_PATH,
                                       'file'  => 'help',
                                       'text'  => 'Hilfe'));

$menuicons[] = array('INGAME' => array('modus'  => CAVE_BOOK,
                                       'file'  => 'cavebook',
                                       'text'  => 'Siedlungsliste'));
$menuicons[] = array('INGAME' => array('modus'  => STATS,
                                       'file'  => 'stats',
                                       'text'  => 'Statistiken'));


$menupoints = array();
$menupoints[] = array('INGAME' => array('modus' => EASY_DIGEST,
                                        'file'  => 'digest.gif',
                                        'text'  => 'Terminkalender'));

$menupoints[] = array('INGAME' => array('modus' => ALL_CAVE_DETAIL,
                                        'file'  => 'uebersicht.gif',
                                        'text'  => 'Alle Siedlungen'));

$menupoints[] = array('INGAME' => array('modus' => MESSAGES,
                                        'file'  => 'nachrichten.gif',
                                        'text'  => 'Nachrichten'));

$menupoints[] = array('INGAME' => array('modus' => MAP,
                                        'file'  => 'karte.gif',
                                        'text'  => 'Karte'));

$menupoints[] = array('INGAME' => array('modus' => MOVEMENT,
                                        'file'  => 'bewegung.gif',
                                        'text'  => 'Bewegung'));

$menupoints[] = array('INGAME' => array('modus' => UNIT_BUILDER,
                                        'file'  => 'einheiten_bauen.gif',
                                        'text'  => 'Einheiten'));

$menupoints[] = array('INGAME' => array('modus' => DEFENSESYSTEM,
                                        'file'  => 'verteidigungsanlagen.gif',
                                        'text'  => 'Verteidigung'));

$menupoints[] = array('INGAME' => array('modus' => IMPROVEMENT_DETAIL,
                                        'file'  => 'erweiterungen_bauen.gif',
                                        'text'  => 'Erweiterungen'));

$menupoints[] = array('INGAME' => array('modus' => SCIENCE,
                                        'file'  => 'entdeckungen.gif',
                                        'text'  => 'Entdeckungen'));

$menupoints[] = array('INGAME' => array('modus' => WONDER,
                                        'file'  => 'wunder.gif',
                                        'text'  => 'Zauber'));

$menupoints[] = array('INGAME' => array('modus' => TAKEOVER,
                                        'file'  => 'auktion.gif',
                                        'text'  => 'Missionieren'));
/*
$menupoints[] = array('INGAME' => array('modus' => QUEST,
                                        'file'  => 'quests.gif',
                                        'text'  => 'Quests'));
*/
$menupoints[] = array('INGAME' => array('modus' => RANKING,
                                        'file'  => 'punktzahlen.gif',
                                        'text'  => 'Punktzahl'));
/*
$menupoints[] = array('INGAME' => array('modus' => HERO_DETAIL,
                                        'file'  => 'held.gif',
                                        'text'  => 'Mein Held'));
*/
$menupoints[] = array('EXTERN' => array('link'   => FORUM_PATH,
                                        'file'   => 'forum.gif',
                                        'text'   => 'Zum Forum'));

$menupoints[] = array('INGAME' => array('modus' => LOGOUT,
                                        'file'  => 'logout.gif',
                                        'text'  => 'Logout'));

// +++++ select +++++
$select = array();
foreach($meineHoehlen as $key => $value){
  $selected = ($caveID == $key ? "selected" : "");
  $select[] = array('value'    => $key,
                    'selected' => $selected,
                    'text'     => lib_shorten_html($value['name'], 17));
}

// +++++ time/timepic +++++
$now = getUgaAggaTime(time());
if ( ($now['hour'] >= HOURS_PER_DAY/4) && ($now['hour'] < 3*HOURS_PER_DAY/4)){
  //tags (6-17)
  $timePic = "oben-day-" . (ceil(($now['hour'] - HOURS_PER_DAY/4 + 1) /  (HOURS_PER_DAY / 8)) - 1);
} else {
  //nachts (18-5)
  $timePic = "oben-night-" . $now['moon'];
}
$time = $now['day'] . ". Tag des " . getMonthName($now['month']) . "-Monats im Jahre " . $now['year'] . " um " . $now['hour'] . " Uhr. Mondphase: ". $now['moon'] .".";

/* ***** TEMPLATE PARSEN ***************************************** */
$template = @tmpl_open('templates/' .  $config->template_paths[$params->SESSION->user['template']] . '/menu.ihtml');


tmpl_set($template, '/', array('timePic'  => $timePic,
                               'time'     => $time,
                               'SELECT'   => $select,
                               'MENUICON' => $menuicons));


for ($i = 0; $i < sizeof($menupoints); ++$i){
  tmpl_iterate($template, 'MENUPOINT');
  tmpl_set($template,     'MENUPOINT', $menupoints[$i]);
}

$gfx = $params->SESSION->nogfx ? DEFAULT_GFX_PATH : $params->SESSION->user['gfxpath'];
echo str_replace ('%gfx%', $gfx, tmpl_parse($template));

page_end();
?>
