<?php

/***************************************************************************/
/* TERRAIN EFFECTS                                                         */
/***************************************************************************/

// effect for plaines
$effect[0][0] = "effect_food_factor = '0.05'";
$effectname[0][0] = "Nahrungsfaktor";
$effect[0][1] = "effect_population_factor = '0.05'";
$effectname[0][1] = "Bev&ouml;lkerungsfaktor";

// effect for forrest
$effect[1][0] = "effect_wood_factor = '0.05'";
$effectname[1][0] = "Holzfaktor";
$effect[1][1] = "effect_food_factor = '0.05'";
$effectname[1][1] = "Nahrungsfaktor";

// effect for swamp
$effect[2][0] = "effect_sulfur_factor = '0.08'";
$effectname[2][0] = "Schwefelfaktor";
$effect[2][1] = "effect_wood_factor = '0.02'";
$effectname[2][1] = "Holzfaktor";

// effect for mountain
$effect[3][0] = "effect_stone_factor = '0.07'";
$effectname[3][0] = "Steinfaktor";
$effect[3][1] = "effect_metal_factor = '0.03'";
$effectname[3][1] = "Metallfaktor";

/* ************************************************************************* */

define("DB_UGA_FIELDNAME", "science_faith");
define("DB_AGGA_FIELDNAME", "science_darkness");

/* ***** MODI ***** ******************************************************** */
define("CAVE_DETAIL",                 0x01);
define("ALL_CAVE_DETAIL",             0x02);

define("MAP",                         0x03);
define("MAP_DETAIL",                  0x04);
define("PLAYER_DETAIL",               0x05);
define("TRIBE_DETAIL",                0x06);

define("MOVEMENT",                    0x07);

define("UNIT_BUILDER",                0x08);
define("UNIT_PROPERTIES",             0x09);

define("DEFENSESYSTEM",               0x0A);
define("DEFENSESYSTEM_DETAIL",        0x0B);
define("DEFENSESYSTEM_BREAK_DOWN",    0x0C);

define("IMPROVEMENT_DETAIL",          0x0D);
define("IMPROVEMENT_BUILDING_DETAIL", 0x0E);
define("IMPROVEMENT_BREAK_DOWN",      0x0F);

define("SCIENCE",                     0x10);
define("SCIENCE_DETAIL",              0x11);

define("MESSAGES",                    0x12);
define("MESSAGESDETAIL",              0x13);
define("NEW_MESSAGE",                 0x14);
define("NEW_MESSAGE_RESPONSE",        0x15);

define("LOGOUT",                      0x16);

define("USER_PROFILE",                0x18);
define("DELETE_ACCOUNT",              0x19);

define("TAKEOVER",                    0x1A);
define("TAKEOVER_CHANGE",             0x1B);
define("CAVE_GIVE_UP_CONFIRM",        0x1C);

define("RANKING",                     0x1D);
define("RANKING_TRIBE",               0x1E);

define("EASY_DIGEST",                 0x1F);

define("ARTEFACT_DETAIL",             0x20);
define("ARTEFACT_LIST",               0x21);

define("NO_CAVE_LEFT",                0x24);
define("NOT_MY_CAVE",                 0x25);

define("TRIBE_PLAYER_LIST",           0x30);
define("TRIBE",                       0x31);
define("TRIBE_DELETE",                0x32);
define("TRIBE_ADMIN",                 0x33);
define("TRIBE_RELATION_LIST",         0x34);
define("TRIBE_HISTORY",               0x36);
define("TRIBE_LEADER_DETERMINATION",  0x38);

define("WONDER",                      0x40);
define("WONDER_DETAIL",               0x41);

define("EFFECTWONDER_DETAIL",         0x42);
define("END_PROTECTION_CONFIRM",      0x43);

define("QUESTIONNAIRE",               0x44);
define("QUESTIONNAIRE_PRESENTS",      0x45);

define("HERO_DETAIL",                 0x46);

define("AWARD_DETAIL",                0x47);

define("QUEST",			      0x48); // ADDED by chris--- for quests
define("QUEST_DETAIL",		      0x49);
define("QUEST_HELP",		      0x50);

define("TICKER_ENTRY",		      0x60); // ADDED by chris--- for ticker
define("TICKER_ARCHIVE",	      0x61);
define("NEW_TICKER_MESSAGE_RESPONSE", 0x62);

define("STATS",			      0x70); // ADDED by chris--- for stats

define("MESSAGE_BOOK",		      0x80); // ADDED by chris--- for adressbook
define("MESSAGE_BOOK_DELETE",	      0x81);
define("MESSAGE_BOOK_ADD",	      0x82);

define("CAVE_BOOK",		      0x85); // ADDED by chris--- for cave_book
define("CAVE_BOOK_DELETE",	      0x86);
define("CAVE_BOOK_ADD",		      0x87);

/* ***** TIME CONSTANTS ***** ********************************************** */
define("BUILDING_TIME_BASE_FACTOR",      10);
define("DEFENSESYSTEM_TIME_BASE_FACTOR", 10);
define("SCIENCE_TIME_BASE_FACTOR",       10);
define("WONDER_TIME_BASE_FACTOR",        10);
define("MOVEMENT_TIME_BASE_FACTOR",      10);



//define("BUILDING_TIME_BASE_FACTOR",      60);
//define("DEFENSESYSTEM_TIME_BASE_FACTOR", 60);
//define("SCIENCE_TIME_BASE_FACTOR",       60);
//define("WONDER_TIME_BASE_FACTOR",        60);
//define("MOVEMENT_TIME_BASE_FACTOR",      60);
define("GOVERNMENT_CHANGE_TIME_HOURS",   24);
define("TRIBE_BLOCKING_PERIOD_PLAYER",   86400);
define("TORE_DOWN_TIMEOUT",              120);   // minutes !

define("SESSION_MAX_LIFETIME",           2700); // seconds !

define("TICKER_BLOCK_TIME",		 15); // minutes ! ADDED by chris--- for ticker
define("TICKER_MAX_CHARS",		 200);
define("TICKER_MESSAGE_AMOUNT",		 8); // how many of the last messages to be displayed

/* ***** MISC. ***** ******************************************************* */
define("RELATION_FORCE_FROM_ID"    ,            2);
define("RELATION_FORCE_TO_ID"      ,            3);
define("RELATION_FORCE_MORAL_THRESHOLD",      -10);
define("RELATION_FORCE_MEMBERS_LOST_ABSOLUT",   3);
define("RELATION_FORCE_MEMBERS_LOST_RELATIVE",  0.30);

//define("MAX_SIMULTAN_BUILDED_UNITS", 10);
define("MAX_SIMULTAN_BUILDED_UNITS", 40);

define("TRIBE_LEAVE_FAME_COST",     500);
define("FAME_DECREASE_FACTOR",        0.97);

define("GOD_ALLY",           "Astaroth");
define("CAVE_SIZE_DB_FIELD", "resource_population");

define("BOX_INCOMING", 0x01);
define("BOX_OUTGOING", 0x02);

define("FARMSCHUTZ_ACTIVE", 1); // ADDED by chris--- for farmschutz

define("DEFAULT_GFX_PATH", "PATH");
define("FORUM_PATH", "PATH");
define("HELP_PATH",  "PATH");
define("RULES_PATH", "PATH");
define("LOGIN_PATH", "../portal.php");

/* ***** MAP ***** ********************************************************* */
define("AVATAR_X",    200);
define("AVATAR_Y",    300);
define("MAP_X_RANGE", 3);
define("MAP_Y_RANGE", 2);

define("MAP_WIDTH",       7);
define("MAP_HEIGHT",      5);
define("MINIMAP_SCALING", 450);

/* ***** MOVEMENTS ***** *************************************************** */
class Movement{

  var $id;
  var $speedfactor;
  var $foodfactor;
  var $mayBeInvisible;
  var $playerMayChoose;
  var $description;

  function Movement($id, $speedfactor, $foodfactor, $returnID,
                    $mayBeInvisible, $playerMayChoose, $description){

    $this->id              = $id;
    $this->speedfactor     = $speedfactor;
    $this->foodfactor      = $foodfactor;
    $this->returnID        = $returnID;
    $this->mayBeInvisible  = $mayBeInvisible;
    $this->playerMayChoose = $playerMayChoose;
    $this->description     = htmlentities($description);
  }
}

$ua_movements = array();
$ua_movements[1] = new Movement(1, 1, 2,  5, false, true,  'Rohstoffe bringen');
$ua_movements[2] = new Movement(2, 1, 1,  5, false, true,  'Einheiten/Rohstoffe verschieben');
$ua_movements[3] = new Movement(3, 1, 2,  5, true,  true,  'Angreifen');
$ua_movements[4] = new Movement(4, 1, 2,  5, true,  true,  'Spionieren');
$ua_movements[5] = new Movement(5, 1, 1, -1, false, false, 'Rückkehr');
$ua_movements[6] = new Movement(6, 5, 2,  5, true,  true,  'Übernahme');


/* ***** BOTS ***** ******************************************************** */
define("SHOW_MESSAGES",      1);
define("SECONDS_FOR_CREDIT", 1800);

/* ************************************************************************ */

class Config {

  var $RUN_TIMER = 0;

  var $LOG_ALL = 1;

  // DB Zugriff
  var $DB_HOST              = "***";
  var $DB_USER              = "***";
  var $DB_PWD               = "***";
  var $DB_NAME              = "***";

  var $DB_LOGIN_HOST        = "***";
  var $DB_LOGIN_USER        = "***";
  var $DB_LOGIN_PWD         = "***";
  var $DB_LOGIN_NAME        = "***";

  var $DBERROR_URL          = "dberror.html.php";

  var $GAME_START_URL       = "ugastart.php";

  var $ERROR403_URL         = "error403.html.php";
  var $ERROR_TIMEOUT_URL    = "errortimeout.html.php";

  var $WWW_REQUEST_TIMEOUT            = .100;
  var $IMPROVEMENT_PAY_BACK_DIVISOR   = 2;
  var $DEFENSESYSTEM_PAY_BACK_DIVISOR = 2;

  var $rememberModusInclude = array(CAVE_DETAIL, ALL_CAVE_DETAIL, MAP, MOVEMENT,
    UNIT_BUILDER, DEFENSESYSTEM, IMPROVEMENT_DETAIL, SCIENCE, WONDER);

  // if one does not own any cave, one can only switch to the following modi
  var $noCaveModusInclude = array(ARTEFACT_DETAIL,      ARTEFACT_LIST,
                                  NO_CAVE_LEFT,         DELETE_ACCOUNT,
                                  LOGOUT,               MAP,
                                  MAP_DETAIL,           MESSAGES,
                                  MESSAGESDETAIL,       NEW_MESSAGE,
                                  NEW_MESSAGE_RESPONSE, PLAYER_DETAIL,
                                  RANKING,              RANKING_TRIBE,
                                  TRIBE_DETAIL,         TRIBE_HISTORY,
                                  TRIBE_PLAYER_LIST,    TRIBE_RELATION_LIST,
                                  USER_PROFILE);

  // only this modi will be logged
  var $logModusInclude = array(MOVEMENT, DELETE_ACCOUNT, CAVE_GIVE_UP_CONFIRM,
                               ARTEFACT_DETAIL, TRIBE_DELETE, DEFENSESYSTEM,
                               TAKEOVER, TAKEOVER_CHANGE);

  var $template_paths  = array(1 => 'basic');
                               //2 => 'justText',
                               //3 => 'slimfast');


  var $WONDER_TARGET_TEXT = array(
    "same"  => "Wirkungssiedlung",
    "own"   => "eigene Siedlungen",
    "other" => "fremde Siedlungen",
    "all"   => "jede Siedlung"
  );

  var $messageClass = array (
           0 => "Information",
           2 => "Sieg!",
           4 => "Einheit ausgebildet",
           6 => "Handelsbericht",
           7 => "R&uuml;ckkehr",
           8 => "Clannachricht",
           9 => "Zauber",
          10 => "Benutzernachricht",
          11 => "Spionage",
          12 => "Artefakt",
          20 => "Niederlage!",
	  30 => "Quest", // ADDED by chris--- for Quests
          99 => "Team",
           // special message class: can't be deleted, everybody can see
        1001 => "<b>ANK&Uuml;NDIGUNG</b>");


        var $messageImage = array(2 => "battle_won.gif",
                                  6 => "trade_report.gif",
                                  20 => "battle_lost.gif");

        var $require_files = array(
          ALL                  => array("time.inc.php",
                                        "basic.lib.php",
					"ticker.inc.php"),
          0                    => array("formula_parser.inc.php"),
          ARTEFACT_DETAIL      => array("formula_parser.inc.php",
                                        "artefact.html.php",
                                        "artefact.inc.php"),
          ARTEFACT_LIST        => array("formula_parser.inc.php",
                                        "artefact.html.php",
                                        "artefact.inc.php"),
          EASY_DIGEST          => array("artefact.inc.php",
                                        "formula_parser.inc.php",
                                        "digest.html.php",
                                        "digest.inc.php",
                                        "movement.lib.php"),
          RANKING              => array("ranking.html.php",
                                        "ranking.inc.php"),
          RANKING_TRIBE        => array("ranking.html.php",
                                        "ranking.inc.php"),
          CAVE_DETAIL          => array("cave_report.html.php",
                                        "formula_parser.inc.php"),
          CAVE_GIVE_UP_CONFIRM => array("formula_parser.inc.php",
                                        "caveChangeConfirm.html.php"),
          END_PROTECTION_CONFIRM => array("formula_parser.inc.php",
                                          "endBeginnerProtectionConfirm.html.php"),
          ALL_CAVE_DETAIL      => array("cave_report.html.php",
                                        "formula_parser.inc.php"),
          MAP                  => array("formula_parser.inc.php",
                                        "map.inc.php",
                                        "map.html.php",
					"cave_book.inc.php", // ADDED by chris--- for cavebook
					"quest.inc.php"), // ADDED by chris--- for Quests
          MAP_DETAIL           => array("formula_parser.inc.php",
                                        "map.inc.php",
                                        "map.html.php",
					"cave_book.inc.php", // ADDAD by chris--- for cavebook
					"quest.inc.php"), // ADDED by chris--- for Quests
          PLAYER_DETAIL        => array("playerDetail.html.php"),
          TRIBE_DETAIL         => array("tribeDetail.html.php"),
          TRIBE_PLAYER_LIST    => array("tribePlayerList.html.php"),
          MOVEMENT             => array("formula_parser.inc.php",
					"cave_book.inc.php", // ADDED by chris--- for cavebook
                                        "unitaction.inc.php",
                                        "unitaction.html.php",
                                        "artefact.inc.php",
                                        "digest.inc.php",
                                        "movement.lib.php",
					"quest.inc.php"), // ADDED by chris--- for Quests
          UNIT_BUILDER         => array("formula_parser.inc.php",
                                        "unitbuild.html.php",
                                        "unitbuild.inc.php"),
          UNIT_PROPERTIES      => array("formula_parser.inc.php",
                                        "unit_properties.html.php",
                                        "unitbuild.inc.php"),
          DEFENSESYSTEM        => array("formula_parser.inc.php",
                                        "fortification.html.php",
                                        "fortification.inc.php"),
          DEFENSESYSTEM_DETAIL => array("formula_parser.inc.php",
                                        "fortification_detail.html.php",
                                        "fortification.inc.php"),
          DEFENSESYSTEM_BREAK_DOWN    => array("formula_parser.inc.php",
                                               "fortificationDeleteConfirm.html.php",
                                               "fortification.inc.php"),
          IMPROVEMENT_DETAIL          => array("formula_parser.inc.php",
                                               "improvement.html.php",
                                               "improvement.inc.php"),
          IMPROVEMENT_BUILDING_DETAIL => array("formula_parser.inc.php",
                                               "improvement_building_detail.html.php",
                                               "improvement.inc.php"),
          IMPROVEMENT_BREAK_DOWN      => array("formula_parser.inc.php",
                                               "improvementDeleteConfirm.html.php",
                                               "improvement.inc.php"),
          EFFECTWONDER_DETAIL         => array("wonder.rules.php",
                                               "formula_parser.inc.php",
                                               "effectWonderDetail.html.php",
                                               "wonder.inc.php"),
          WONDER                      => array("wonder.rules.php",
                                               "formula_parser.inc.php",
                                               "wonder.html.php",
                                               "wonder.inc.php",
						"cave_book.inc.php", // ADDED by chris--- for cavebook
                                               "message.inc.php"),
          WONDER_DETAIL               => array("wonder.rules.php",
                                               "formula_parser.inc.php",
                                               "wonderDetail.html.php",
                                               "wonder.inc.php"),
          SCIENCE              => array("formula_parser.inc.php",
                                        "science.inc.php",
                                        "science.html.php"),
          TAKEOVER             => array("formula_parser.inc.php",
                                        "takeover.html.php",
                                        "takeover.inc.php"),
          TAKEOVER_CHANGE      => array("formula_parser.inc.php",
                                        "takeover.html.php",
                                        "takeover.inc.php"),
          SCIENCE_DETAIL       => array("formula_parser.inc.php",
                                        "science.inc.php",
                                        "science_detail.html.php"),
          MESSAGES             => array("message.html.php",
                                        "message.inc.php"),
          MESSAGESDETAIL       => array("message.html.php",
                                        "message.inc.php"),
          NEW_MESSAGE          => array("message.html.php",
                                        "message.inc.php"),
          NEW_MESSAGE_RESPONSE => array("message.html.php",
                                        "message.inc.php"),

          LOGOUT               => array(),
          USER_PROFILE         => array("profile.html.php",
                                        "profile.inc.php"),
          TRIBE                => array("tribe.html.php",
                                        "tribes.inc.php",
                                        "message.inc.php",
                                        "government.rules.php",
                                        "relation_list.php"),
          TRIBE_ADMIN          => array("tribeAdmin.html.php",
                                        "tribes.inc.php",
                                        "message.inc.php",
                                        "relation_list.php",
                                        "government.rules.php"),
          TRIBE_RELATION_LIST  => array("tribeRelationList.html.php",
                                        "tribes.inc.php",
                                        "relation_list.php"),
          TRIBE_HISTORY        => array("tribeHistory.html.php",
                                        "tribes.inc.php"),
          TRIBE_DELETE         => array("tribeDelete.html.php",
                                        "tribes.inc.php",
                                        "relation_list.php",
                                        "message.inc.php"),
          DELETE_ACCOUNT       => array("delete.html.php",
                                        "profile.inc.php"),
          TRIBE_LEADER_DETERMINATION  => array("tribeLeaderDetermination.html.php",
                                               "government.rules.php",
                                               "tribes.inc.php"),
          QUESTIONNAIRE           => array("questionnaire.html.php"),
          QUESTIONNAIRE_PRESENTS  => array("questionnaire.html.php",
                                           "formula_parser.inc.php"),
          HERO_DETAIL             => array("hero.html.php"),
          AWARD_DETAIL            => array("award.html.php"),

// ADDED by chris--- for ticker
          TICKER_ENTRY            => array("ticker.inc.php",
					   "ticker_entry.html.php"),
          TICKER_ARCHIVE          => array("ticker.inc.php",
					   "ticker_archive.html.php"),
          NEW_TICKER_MESSAGE_RESPONSE => array("ticker.inc.php",
					   "ticker_entry.html.php"),

// ADDED by chris--- for stats
          STATS		          => array("stats.inc.php",
					   "stats.html.php",
					   "formula_parser.inc.php"),

// ADDED by chris--- for adressbook
	  MESSAGE_BOOK		  => array("message.inc.php",
					   "message_book.html.php"),
	  MESSAGE_BOOK_DELETE	  => array("message.inc.php",
					   "message_book.html.php"),
	  MESSAGE_BOOK_ADD	  => array("message.inc.php",
					   "message_book.html.php"),

// ADDED by chris--- for cavebook
	  CAVE_BOOK		  => array("cave_book.inc.php",
					   "cave_book.html.php"),
	  CAVE_BOOK_DELETE	  => array("cave_book.inc.php",
					   "cave_book.html.php"),
	  CAVE_BOOK_ADD	  	  => array("cave_book.inc.php",
					   "cave_book.html.php"),

// ADDED by chris--- for quests
	  QUEST			  => array("quest.html.php",
					   "quest.inc.php"),
	  QUEST_DETAIL		  => array("quest_details.html.php",
					   "quest.inc.php"),
	  QUEST_HELP		  => array("quest_help.html.php"));
}
?>
