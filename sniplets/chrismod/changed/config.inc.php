<?

define("BEGINNER_PROTECTION_HOURS", 96);
define("EASY_START", 1);                  // should display easy start option?
define("EASY_START_SELECTED", 1);         // selected by default?

define("REGEXP_PLAYER", '/^\w([\w .-]*\w|)$/');
define("REGEXP_PASSWORD", '/^\w{6,}$/');


/* *****      ***** ******************************************************** */

$cfg['LOGIN_MAX_USERS']          = 100;// there may be only that many accounts
$cfg['LOGIN_PWD_RESEND_TIMEOUT'] = 300; // wait some seconds before resending password

$cfg['SESSION_SAVE_PATH']      = "PATH";
$cfg['SESSION_SAVE_PATH_GAME'] = "PATH";

$cfg['USE_SEC_CODE']      = TRUE;
                          
$cfg['GAME_BASE'][0]      = "PATH";
                          
$cfg['GAME_URL'][0]       = "URI"; // the URI to the game
                          
$cfg['GAME_LOAD'][0]      = 100;

$cfg['FORBIDDEN_NAMES']   = array( "Agga",
                                   "Enzio",
                                   "Firak",
                                   "Gharlane",
                                   "Kirkalot",
                                   "Paffi",
                                   "Nicknehm",
                                   "Prophet",
                                   "Sirat",
                                   "Slavomir",
                                   "Skirk",
                                   "Trubatsch",
                                   "Uga" );

$cfg['PORTAL_URL']       = "portal.php";
$cfg['LOGIN_ACTIVATION_URL'] = "PATH"; // url to the account activation
$cfg['RULES_URL'] = "PATH";  // url to the rules
$cfg['GAME_RULES'] = "PATH";

$cfg['DB_LOGIN']['HOST'] = "***"; 
$cfg['DB_LOGIN']['USER'] = "***";
$cfg['DB_LOGIN']['PWD']  = "***";
$cfg['DB_LOGIN']['NAME'] = "***";

$cfg['DB_GAME']['HOST'] = "***";
$cfg['DB_GAME']['USER'] = "***";
$cfg['DB_GAME']['PWD']  = "***";
$cfg['DB_GAME']['NAME'] = "***";

$cfg['WELCOME_CLASS']   = 99;                  
$cfg['WELCOME_SUBJECT'] = "Herzlich Willkommen bei Uga-Agga!";                  
$cfg['WELCOME_TEXT']    = "Herzlich Willkommen bei der 2. Runde von Uga-Agga@chris!<br><br>" .
			  "Dieses Spiel basiert auf der Uga-Agga-Engine (www.uga-agga.de) " .
			  "und wurde hier zu Testzwecken installiert. Es kann sein, dass noch nicht " .
			  "alles funktioniert und noch Bugs vorhanden sind.<br>" .
			  "Bitte meldet mir auftretende Probleme!<br>" .
                          "Naja, ist nur ein Test, also,<br><br>".
			  "Viel Spaß!";

$cfg['SecureCaveCredits'] = 3;

$cfg['START_SETTINGS'] = array(
  'resource_population' => 1,
  'resource_food'       => 40,
  'resource_wood'       => 40,
  'resource_stone'      => 40,
  'building_worker'     => 3,
  'unit_boxer'          => 1,
  'science_elements'    => 1,
  'science_writing'     => 1,
  'extern_wall'         => 1);
    


/* ***** MODI ***** ******************************************************** */
define("WELCOME_HOME",                 0x01);
define("PASSWORD_CHECK",               0x02);
define("PASSWORD_FORGOTTEN",           0x03);
define("CREATE_ACCOUNT",               0x04);
define("ACTIVATE_ACCOUNT",             0x05);
define("MANAGE_ACCOUNT",               0x06);
define("DIE_TAL_ZEITUNG",              0x07);
define("SHOW_AGB",                     0x08);
define("SHOW_IMPRESSUM",               0x09);
define("POLL_SHOW",                    0x0A);
define("POLL_SHOW_ALL",                0x0B);
define("POLL_VOTE",                    0x0C);
define("POLL_RESULT",                  0x0D);
define("CHAT",                         0x0E);
define("RGISTER_EMAIL",                0x0F);
define("NEWS_ARCHIVE",                 0x10);
define("FANSITES",                     0x11);
define("LINKS",                        0x12);

define("VACATION",		       0x13); // ADDED by chris--- for urlaubsmod


?>
