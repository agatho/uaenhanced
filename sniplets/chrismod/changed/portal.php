<?
require_once("config.inc.php");
require_once("db.inc.php");
require_once("params.inc.php");

require_once("session.inc.php");

require_once("modules.php");
require_once("module_login.php");
require_once("module_news.php");
require_once("module_poll.php");
require_once("module_uga_agga_time.php");
require_once("module_fansites.php");
require_once("module_links.php");


/***** INITIALIZE GLOBALS ******************************************/
// get link to DB 'login'
$db_login = new DB($cfg['DB_LOGIN']['HOST'],
                   $cfg['DB_LOGIN']['USER'],
                   $cfg['DB_LOGIN']['PWD'],
                   $cfg['DB_LOGIN']['NAME']); 
if (!$db_login){
  echo "Wir sind derzeit nicht erreichbar.";
  die();
}

// get cleaned POST, GET and SESSION parameters
$params	=	new	Params();

/***** SESSION *****************************************************/

session_start_session();

/***** SET COOKIE *****************************************************/


// Check to see if pollid cookie actually exists
if (isset($_COOKIE["UAPOLLID"])){
  
  // Get value from cookie
  $uapollid = $_COOKIE["UAPOLLID"];

// cookie doesn't exist ...
} else {
  // set cookie with value
  setcookie("UAPOLLID", "" . md5(uniqid(rand())), time() + 60*60*24*90);
}

/***** CHECK PARAMETERS ********************************************/

/** the content of the box in the middle is controlled by the
 *  GET/POST variable 'modus'. so get this variable, check it
 *  or let it default...
 */
if (!isset($params->modus))
  $modus = WELCOME_HOME;
else
  $modus = (int) $params->modus;

/***** MODUS SWITCH ************************************************/
$quick_menu_items = array();
$modules_left     = array();
$modules_right    = array();
$main_content     = array();

switch($modus){

// ADDED by chris--- for urlaubsmod
// ---------------------------------------------------------------------------------------
  case VACATION: array_push($main_content,
                                  module_getGeneric("Urlaubsmodus",
                                    login_vacation($db_login,
                                                      $params->username,
                                                      $params->password)));
                       break;
// ---------------------------------------------------------------------------------------
                       
  case PASSWORD_CHECK: array_push($main_content,
                                  module_getGeneric("Login Versuch",
                                    login_checkUserPassword($db_login,
                                                      $params->username,
                                                      $params->password,
                                                      $params->security_code)));
                       break;

  case REGISTER_EMAIL: array_push($main_content,
                                  module_getGeneric("Email registrieren",
                                    register_registerEmail($db_login,
							   $params->email)));
                       break;
  
  case PASSWORD_FORGOTTEN:
                       array_push($main_content,
                                  module_getGeneric("Passwort vergessen",
                                                 login_resendPassword($db_login,
                                                         $params->username)));
                       break;
  
  case CREATE_ACCOUNT: array_push($main_content,
                                  module_getGeneric("Account anlegen",
                                    login_createAccount($db_login)));
                       break;
  
  case ACTIVATE_ACCOUNT:
                       array_push($main_content,
                                  module_getGeneric("Account aktivieren",
                                    login_activateAccount($db_login, $params->username, $params->password)));
                       break;
  
  case MANAGE_ACCOUNT: break;
  
  case DIE_TAL_ZEITUNG:
                       array_push($main_content,
                                  module_getGeneric("Die Tal Zeitung",
                                    module_showTalZeitung()));
                       break;

  case SHOW_AGB:       array_push($main_content,
                                  module_getGeneric("Nutzungsbedingungen",
                                    module_showAGB()));
                       break;

  case SHOW_IMPRESSUM: array_push($main_content,
                                  module_getGeneric("Impressum",
                                    module_showImpressum()));
                       break;
  
  // ----- POLLS --------------------------------------------------------------
/*
  case POLL_SHOW:      array_push($main_content,
                                  module_getGeneric("Umfrage anschauen",
                                    poll_showPoll($db_login, $params->pollID)));
                       break;

  case POLL_SHOW_ALL:  array_push($main_content,
                                  module_getGeneric("Vergangene Umfragen",
                                    poll_showAll($db_login)));
                       break;

  case POLL_VOTE:      // vote if reasonable, then show result
                       poll_vote($db_login, $params->pollID, $params->voteID);
                       // note: there must not be a break statement here
                       
  case POLL_RESULT:    array_push($main_content,
                                  module_getGeneric("Umfragenergebnis anschauen",
                                    poll_showResult($db_login, $params->pollID)));
                       break;
*/
  
  // ----- MISC. INFORMATIONS -------------------------------------------------
  case CHAT:           array_push($main_content, 
                                  module_getGeneric("Unser Chatserver",
                                    module_chat()));
                       break;

  case NEWS_ARCHIVE:   array_push($main_content, module_getNewsArchive($db_login));
                       break;

  case FANSITES:       array_push($main_content, module_getFansites());
                       break;

  case LINKS:          array_push($main_content, module_getLinks());
                       break;                       
  
  // ----- DEFAULT ------------------------------------------------------------
  default:           
  case WELCOME_HOME:   array_push($main_content, module_getWelcomeMessage());
                       array_push($main_content, module_getNews($db_login));
                       break;

}

$modules_left = array(array('caption' => "Hauptmen&uuml;",
                            'content' => module_getMainMenu()),
//                      array('caption' => "Umfrage",
//                            'content' => module_getPoll($db_login)),
                      array('caption' => "Information",
                            'content' => module_getInformationMenu())
//				,
//		          array('caption' => "MMOG Charts",
//			          'content' => module_getVote()),
//	                array('caption' => "powered by",
//		                'content' => module_poweredBy())
				);

$modules_right = array(
			array('caption' => "Login",
                             'content' => module_getLogin()),
                       array('caption' => "Email registrieren",
                             'content' => module_getRegisterEmail()),
                       array('caption' => "Benutzerstatistik",
                             'content' => module_getLoginStats()),
                       array('caption' => "Wer ist online?",
                             'content' => module_getLoggedIn()),
                       array('caption' => "Uga Agga Zeit",
                             'content' => module_getUgaAggaTime()));

if (sizeof($quick_menu_items) == 0){
  $quick_menu_items = array(array('link'        => "portal.php",
                                  'description' => "Home",
                                  'separator'   => "|"),
//                            array('link'        => "http://www.uga-agga.de/shop/default.php?language=german" ,
//                                  'description' => "UA Laden",
//                                  'separator'   => "|"),
//                            array('link'        => "http://www.talzeitung.de/",
//                                  'description' => "Die Tal-Zeitung",
//                                  'TARGET'      => array('target' => "_blank"),
//                                  'separator'   => "|"),
                              array('link'        => $cfg['RULES_URL'],
                                    'description' => "Regeln",
                                    'TARGET'      => array('target' => "_blank"),
                                    'separator'   => "|"),
                            array('link'        => "simulator/simulator.php",
                                  'description' => "Kampfulator",
                                  'TARGET'      => array('target' => "_blank"),
                                  'separator'   => "|"),
                            array('link'        => "../comawiki/",
                                  'description' => "Wiki",
                                  'TARGET'      => array('target' => "_blank"),
                                  'separator'   => "|"),
                            array('link'        => "http://57060.rapidforum.com/",
                                  'description' => "Zum Forum",
                                  'TARGET'      => array('target' => "_blank")));

}

/***** GENERATE CODE ***********************************************/

if ($cfg['USE_SEC_CODE'])
  session_generate_code();

/***** FILL TEMPLATE ***********************************************/
$template = @tmpl_open('./templates/base.ihtml');

tmpl_set($template, "MENU/ITEM", $quick_menu_items);
tmpl_set($template, "BOX_LEFT",  $modules_left);
tmpl_set($template, "BOX_RIGHT", $modules_right);

if (is_array($main_content))
  foreach ($main_content as $key => $value){
    tmpl_iterate($template, 'CONTENT');
    tmpl_set($template, 'CONTENT/content', $value);
  }
else
  tmpl_set($template, 'CONTENT/content', $main_content);

echo tmpl_parse($template);
?>
