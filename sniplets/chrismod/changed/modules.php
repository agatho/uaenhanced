<?

/** This function...
 *
 *  @return
 */
function module_getMainMenu(){
  global $cfg;
  
  $template = @tmpl_open('./templates/module_main_menu.ihtml');
  tmpl_set($template, 'ITEM', array(array('link'        => "portal.php",
                                          'description' => "Home"),
//                                    array('link'        => "http://www.uga-agga.de/shop/default.php?language=german",
//                                          'description' => "Uga-Agga Laden"),
//                                    array('link'        => "http://www.talzeitung.de/",
//                                          'description' => "Die Tal-Zeitung",
//                                          'TARGET'      => array('target' => "_blank")),
                                      array('link'        => $cfg['RULES_URL'],
                                            'description' => "Regeln",
                                            'TARGET'      => array('target' => "_blank")),
                                      array('link'        => "simulator/simulator.php",
                                            'description' => "Kampfulator",
                                            'TARGET'      => array('target' => "_blank")),
                                      array('link'        => "../comawiki/",
                                            'description' => "Wiki",
                                            'TARGET'      => array('target' => "_blank")),

                                      array('link'        => "http://57060.rapidforum.com/",
                                            'description' => "Zum Forum",
                                            'TARGET'      => array('target' => "_blank")),
//                                    array('link'        => "portal.php?modus=" . FANSITES,
//                                          'description' => "Fansites"),
                                    array('link'        => "portal.php?modus=" . LINKS,
                                          'description' => "Links/Downloads"),
//							,
//                                    array('link'        => "portal.php?modus=" . CHAT,
//                                          'description' => "Chat")
						));
  return tmpl_parse($template);
}

function module_getInformationMenu(){
  global $cfg;
  
  $template = @tmpl_open('./templates/module_main_menu.ihtml');
  tmpl_set($template, 'ITEM', array(array('link'        => "portal.php?modus=" . NEWS_ARCHIVE,
                                          'description' => "Nachrichtenarchiv"),
                                      array('link'        => "portal.php?modus=" . SHOW_AGB,
                                            'description' => "Nutzungsbedingungen")
//							,
//                                    array('link'        => "portal.php?modus=" . SHOW_IMPRESSUM,
//                                          'description' => "Kontakt"),
//                                    array('link'        => "portal.php?modus=" . SHOW_IMPRESSUM,
//                                          'description' => "Impressum")
							));
  return tmpl_parse($template);
}

function module_poweredBy() {
  return 
   "<a href=\"http://www.uos.de/\" target=\"_blank\"><img src=\"templates/images/uni_klein.gif\" border=\"0\"></a>";
}

function module_getLoginStats() {
  global $cfg, $db_login;

  $query =
    "SELECT COUNT(user) AS count ".
    "FROM Login";

  if (!($r = $db_login->query($query))) {
     return "Nicht verf&uuml;gbar";
  }

  $row = $r->nextRow();

  return "<table border=0><tr><td align=\"right\">$row[count]</td><td>Spieler</td></tr>".
         "<tr><td align=\"right\">".max(0,$cfg[LOGIN_MAX_USERS] - $row[count])."</td><td>freie Accounts</td></tr></table>";
} 
  


/** This function returns the content for the "how many people are logged in"
 *  module.
 */
/*
function module_getLoggedIn(){
  global $cfg;

  if ($dir = opendir($cfg['SESSION_SAVE_PATH_GAME'])){
    while (($file = readdir($dir)) !== false){
      if (ereg ('^sess_', $file))
        $local++;
    } 
    closedir($dir);
  }

  $target = 0;
  for($i=0; $i < count($cfg['GAME_LOAD']); $i++) {
    $target +=  $cfg[GAME_LOAD][$i];
  }

  $estimate = $target * ($local / $cfg[GAME_LOAD][0]);

  return "Im Moment sind " . ceil($estimate) . " Benutzer eingelogged.";
}
*/

/** This function returns the content for the "how many people are logged in"
 *  module.
 */
/*
function module_getLoggedIn(){
  global $cfg;

  if ($dir = opendir("f:/WWW/UASessions/portal/")){
    while (($file = readdir($dir)) !== false){
      if (ereg ('^sess_', $file))
        $local++;
    } 
    closedir($dir);
  }
  
  $target = 0;
  for($i=0; $i < count($cfg['GAME_LOAD']); $i++) {
    $target +  $cfg[GAME_LOAD][$i];
  }
  
  $estimate = $local;

  return "Im Moment sind " . ceil($estimate) . " Benutzer eingelogged.";
}
*/

/** This function returns the content for the "how many people are logged in"
 *  module.
 */

function module_getLoggedIn(){
  global $cfg;

  // get link to DB 'game'
  $db_game = new DB($cfg['DB_GAME']['HOST'],
                    $cfg['DB_GAME']['USER'],
                    $cfg['DB_GAME']['PWD'],
                    $cfg['DB_GAME']['NAME']); 

$now = time()-10*60;

$timestamp = date("YmdHis",$now);

  $query = "SELECT count( * ) AS anzahl FROM session WHERE lastAction > ".$timestamp;

  if (!($result = $db_game->query($query))) {
    return "Error";
  }

  $row = $result->nextRow(MYSQL_ASSOC);

$anzahl = $row[anzahl];
if ($anzahl < 1) $anzahl = "keine";

  return "Im Moment sind " . $anzahl . " Benutzer eingelogged.";

}



/** This function...
 *
 *  @return
 */
function module_getGeneric($caption, $content){
  $template = @tmpl_open('./templates/module_generic_box.ihtml');
  tmpl_set($template, array('caption' => $caption, 'content' => $content));
  return tmpl_parse($template);
}

function module_getWelcomeMessage(){
  $template = @tmpl_open('./templates/module_generic_box.ihtml');
  tmpl_set($template, array('caption' => "Willkommen", 'content' =>

  "...zur zweiten Runde des Uga-Agga-Mods. Es gibt noch keinen Namen " . 
  "und es fehlt immer noch an allem (haupts&auml;chlich an der Grafik) aber es funktioniert " . 
  "schon super. Von Zeit zu Zeit werden neue Funktionen, Einheiten etc eingebaut, wodurch " .
  "schonmal pannen passieren k&ouml;nnen oder der Ticker mal h&auml;ngen bleibt.<br>" .
  "Es sind noch nicht alle baubaren Einheiten, Erweiterungen etc richtig implementiert, " .
  "schaut bitte immer in die Beschreibungen, dort steht das dann drin.<br>" .
  "Auch ist dieser Server mein privater Rechner, der an einer DSL-Flat h&auml;ngt, " .
  "gro&szlig;e Geschwindigkeiten d&uuml;rft ihr also nicht erwarten.<br>" .
  "Der Tick rennt superschnell, so l&auml;&szlig;t sich besser testen, also nicht wundern. " .
  "Sollten euch Bugs auffallen, meldet euch bitte im Forum.<br>" .
  "Wie das mit der Anmeldung geht wi&szlig;t ihr ja.<br><br>" .
  "<b>Viel Spa&szlig;!</b><br><br>" .
  "P.S.: Dieses Spiel ist total unabh&auml;ngig von dem original Uga-Agga. Dieses Spiel dient <i>nicht</i> dazu, " .
  "eure Pa&szlig;w&ouml;rter auszuspionieren! Um ganz sicher zu gehen solltet ihr hier ein komplett anderes " .
  "Pa&szlig;wort benutzen."));

  return tmpl_parse($template);
}

function module_showAGB(){
  $template = @tmpl_open('./templates/module_agb.ihtml');
  return tmpl_parse($template);
}

function module_showImpressum(){
  $template = @tmpl_open('./templates/module_impressum.ihtml');
  return tmpl_parse($template);
}

function module_showTalZeitung(){
  $template = @tmpl_open('./templates/module_tal_zeitung.ihtml');
  return tmpl_parse($template);
}

function module_getVote(){
  $template = @tmpl_open('./templates/module_vote.ihtml');
  return tmpl_parse($template);
}

/** This function 
 */
function module_chat(){
  $template = @tmpl_open('./templates/module_chat.ihtml');
  return tmpl_parse($template);
}
?>
