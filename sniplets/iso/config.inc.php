<?php

define("DB_UGA_FIELDNAME", "science_uga");
define("DB_AGGA_FIELDNAME", "science_agga");

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

define("GOVERNOR",                    0x48);

/* ***** TIME CONSTANTS ***** ********************************************** */
define("BUILDING_TIME_BASE_FACTOR",      6);
define("DEFENSESYSTEM_TIME_BASE_FACTOR", 6);
define("SCIENCE_TIME_BASE_FACTOR",       6);
define("WONDER_TIME_BASE_FACTOR",        6);
define("MOVEMENT_TIME_BASE_FACTOR",      6);
define("GOVERNMENT_CHANGE_TIME_HOURS",   3);
define("TRIBE_BLOCKING_PERIOD_PLAYER",   10800);
define("TORE_DOWN_TIMEOUT",              20);   // minutes !

define("SESSION_MAX_LIFETIME",           2700); // seconds !

/* ***** MISC. ***** ******************************************************* */
define("RELATION_FORCE_FROM_ID"    ,            2);
define("RELATION_FORCE_TO_ID"      ,            3);
define("RELATION_FORCE_MORAL_THRESHOLD",      -10);
define("RELATION_FORCE_MEMBERS_LOST_ABSOLUT",   3);
define("RELATION_FORCE_MEMBERS_LOST_RELATIVE",  0.30);

define("MAX_SIMULTAN_BUILDED_UNITS", 30);

define("TRIBE_LEAVE_FAME_COST",     500);
define("FAME_DECREASE_FACTOR",        0.97);

define("GOD_ALLY",           "Saufalla");
define("CAVE_SIZE_DB_FIELD", "resource_population");

define("BOX_INCOMING", 0x01);
define("BOX_OUTGOING", 0x02);

define("DEFAULT_GFX_PATH", "http://www.uga-agga.de/game/gfx");
define("FORUM_PATH", "http://www.uga-agga.com/testspiel/");
define("HELP_PATH",  "http://agatho.game-host.org/wiki/");
define("RULES_PATH", "http://agatho.game-host.org/rules/");
define("LOGIN_PATH", "http://agatho.game-host.org/portal.php");

/* ***** MAP ***** ********************************************************* */
define("AVATAR_X",    200);
define("AVATAR_Y",    300);
define("MAP_X_RANGE", 3);
define("MAP_Y_RANGE", 2);

define("MAP_WIDTH",       7);
define("MAP_HEIGHT",      5);
define("MINIMAP_SCALING", 150);

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
$ua_movements[3] = new Movement(3, 1, 2,  5, false,  true,  'Angreifen');
$ua_movements[4] = new Movement(4, 1, 2,  5, true,  true,  'Spionieren');
$ua_movements[5] = new Movement(5, 1, 1, -1, false, false, 'Rückkehr');
$ua_movements[6] = new Movement(6, 5, 2,  5, false,  true,  'Übernahme');

/* ***** GOVERNOR ***** **************************************************** */
define("GOVERNOR_ACTIVATED", 1);
define("SHOW_MESSAGES",      1);
define("SECONDS_FOR_CREDIT", 1800);

/* ***** REXEXPS ***** ***************************************************** */
DEFINE("PLAYER_REGEXP_TAG",      '/^\w([\w .-]*\w|)$/');
DEFINE("PLAYER_REGEXP_TAG_EXPLANATION", "Buchstaben, Zahlen, Leerzeichen, Punkte, Bindestriche");

DEFINE("TRIBE_REGEXP_TAG",      '/^\w([\w.-]*\w|)$/');
DEFINE("TRIBE_REGEXP_TAG_EXPLANATION", "Buchstaben, Zahlen, Punkte, Bindestriche");

DEFINE("TRIBE_REGEXP_PASSWORD", '/^\w{6,}$/');
DEFINE("TRIBE_REGEXP_PASSWORD_EXPLANATION", "mind. 6 Buchstaben oder Zahlen");

/* ************************************************************************* */

class Config {

  var $RUN_TIMER = 0;

  var $LOG_ALL = 1;

  // DB Zugriff
  var $DB_HOST              = "localhost";
  var $DB_USER              = "game";
  var $DB_PWD               = "ugaagatho";
  var $DB_NAME              = "game";

  var $DB_LOGIN_HOST        = "localhost";
  var $DB_LOGIN_USER        = "login";
  var $DB_LOGIN_PWD         = "agatho";
  var $DB_LOGIN_NAME        = "login";

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

  var $template_paths  = array(1 => 'basic',
                               2 => 'ugasopera',
                               3 => 'slimfast');


  var $WONDER_TARGET_TEXT = array(
    "same"  => "Wirkungsh&ouml;hle",
    "own"   => "eigene H&ouml;hlen",
    "other" => "fremde H&ouml;hlen",
    "all"   => "jede H&ouml;hle"
  );

  var $messageClass = array (
           0 => "Information",
           2 => "Sieg!",
           4 => "Einheit ausgebildet",
           6 => "Handelsbericht",
           7 => "R&uuml;ckkehr",
           8 => "Stammesnachricht",
           9 => "Wunder",
          10 => "Benutzernachricht",
          11 => "Spionage",
          12 => "Artefakt",
          20 => "Niederlage!",
          99 => "Uga-Agga Team",
           // special message class: can't be deleted, everybody can see
        1001 => "<b>ANK&Uuml;NDIGUNG</b>");


        var $messageImage = array(2 => "battle_won.gif",
                                  6 => "trade_report.gif",
                                  20 => "battle_lost.gif");

        var $require_files = array(
          ALL                  => array("time.inc.php",
                                        "basic.lib.php"),
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
                                        "map.html.php"),
          MAP_DETAIL           => array("formula_parser.inc.php",
                                        "map.inc.php",
                                        "map.html.php"),
          PLAYER_DETAIL        => array("playerDetail.html.php"),
          TRIBE_DETAIL         => array("tribeDetail.html.php"),
          TRIBE_PLAYER_LIST    => array("tribePlayerList.html.php"),
          MOVEMENT             => array("formula_parser.inc.php",
                                        "unitaction.inc.php",
                                        "unitaction.html.php",
                                        "artefact.inc.php",
                                        "digest.inc.php",
                                        "movement.lib.php"),
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
          GOVERNOR                => array("governor.html.php"));
}
?>
