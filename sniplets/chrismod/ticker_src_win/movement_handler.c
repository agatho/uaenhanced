/*
 * movement_handler.c - process movement events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>
#include <string.h>
#include <time.h>


#include "quest_handler.h"

#include "artefact.h"
#include "calc_battle.h"
#include "cave.h"
#include "effect_list.h"
#include "event_handler.h"
#include "except.h"
#include "formula_parser.h"
#include "game_rules.h"
#include "logging.h"
#include "memory.h"
#include "message.h"
#include "mysql_tools.h"
#include "ticker_defs.h"
#include "ugatime.h"

/* movement constants */
#define ROHSTOFFE_BRINGEN	1
#define VERSCHIEBEN		2
#define ANGREIFEN		3
#define SPIONAGE		4
#define RUECKKEHR		5
#define TAKEOVER		6
#define QUESTVISIT      7

#if 0	/* unused */
/*
 * Get the corresponding relation factor from the relation table.
 */
static double relation_multiplicator (MYSQL *database, const char *tribe,
				      const char *tribe_target, int flag)
{
  MYSQL_RES *result = mysql_query_fmt(database,
	"SELECT attackerMultiplicator, defenderMultiplicator FROM Relation "
	"WHERE tribe = '%s' AND tribe_target = '%s'", tribe, tribe_target);
  MYSQL_ROW row = mysql_fetch_row(result);

  return row ? flag == FLAG_ATTACKER ? atof(row[0]) : atof(row[1]) : 1;
}
#endif

static int fame_calculate (Battle *battle)
{
  int    fame_base;
  double fame_factor;
  double fame;
  double q;

  // calculate a falue corresponding the overpower factor, but using
  // the starting values, instead of the values to begin of the second
  // battle round!

  if (battle->attackers_acc_hitpoints_units_before +
      battle->attackers_acc_hitpoints_defenseSystems_before == 0)
  {
    q = 1000;
  }
  else if (battle->defenders_acc_hitpoints_units_before +
	   battle->defenders_acc_hitpoints_defenseSystems_before == 0)
  {
    q = 0.001;
  }
  else
  {
    q = (double) (battle->defenders_acc_hitpoints_units_before
		+ battle->defenders_acc_hitpoints_defenseSystems_before) /
	(double) (battle->attackers_acc_hitpoints_units_before
		+ battle->attackers_acc_hitpoints_defenseSystems_before);
  }
  if (battle->winner == FLAG_DEFENDER) {
    fame_base =
      battle->attackers_acc_hitpoints_units_before +
      battle->attackers_acc_hitpoints_defenseSystems_before -
      battle->attackers_acc_hitpoints_units -
      battle->attackers_acc_hitpoints_defenseSystems ;
    if (q < 1/FAME_MAX_OVERPOWER_FACTOR) {
      q = 1/FAME_MAX_OVERPOWER_FACTOR;
    }
    fame_factor = FAME_FACTOR_DEFENDER / q;
  }

  /* Angreifer gewinnt */
  else {
    fame_base =
      battle->defenders_acc_hitpoints_units_before +
      battle->defenders_acc_hitpoints_defenseSystems_before -
      battle->defenders_acc_hitpoints_units -
      battle->defenders_acc_hitpoints_defenseSystems ;
    if (q > FAME_MAX_OVERPOWER_FACTOR) {
      q = FAME_MAX_OVERPOWER_FACTOR;
    }
    fame_factor = FAME_FACTOR_ATTACKER * q;
  }

  fame = fame_base * fame_factor;
  return fame > 0. && fame < 1. ? 1 : (int)fame;
}

/*
 * Calculate an effect factor from the value of a database field
 */
static double effect_factor (double factor)
{
  return factor < 0 ? 1 / (1 - factor) : 1 + factor;
}

/*
 * Initalize an Army structure for an army of the given cave.
 * The unit, resource and defense_system vectors are copied directly
 * into the Army structure (note: only defense_system may be NULL).
 */
static void army_setup (Army *army, const struct Cave *cave,
			const double religion_bonus[],
			const int takeover_multiplier,
			const int unit[], const int resource[],
			const int defense_system[])
{
  int type;

  /* cave and religion bonus */
  army->owner_caveID = cave->cave_id;
  army->religion = get_religion(cave);
  army->religion_bonus = religion_bonus[army->religion];

  debug(DEBUG_BATTLE, "religion: %d bonus: %g",
	army->religion, army->religion_bonus);

  for (type = 0; type < MAX_UNIT; ++type)
    army->units[type].amount_before = unit[type];
  for (type = 0; type < MAX_RESOURCE; ++type)
    army->resourcesBefore[type] = resource[type];

  if (defense_system)
    for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
      army->defenseSystems[type].amount_before = defense_system[type];

  /* fill effects */

// ADDED by chris--- for leadership
// ------------------------------------------------------------------------------
double leader_multiplier = 1;
int leader = 0;
int better_leader = 0;


if (cave->science[15] > 0) {
leader = cave->building[13];
better_leader = cave->building[27];
debug(DEBUG_TICKER, "leader is: %d, better_leader is %d",leader,better_leader);
}
if (cave->science[16] > 0) {
leader = cave->building[28];
better_leader = cave->building[29];
debug(DEBUG_TICKER, "leader is: %d, better_leader is %d",leader,better_leader);
}
if (cave->science[17] > 0) {
leader = cave->building[30];
better_leader = cave->building[31];
debug(DEBUG_TICKER, "leader is: %d, better_leader is %d",leader,better_leader);
}


leader_multiplier = (leader*0.05)+1 * ((better_leader*0.1)+1);

debug(DEBUG_TICKER, "leader_multiplier is: %f",leader_multiplier);

  
  army->effect_rangeattack_bonus  = cave->effect[14];
  /* XXX should this be effect_factor(... + takeover_multiplier)? */
  army->effect_rangeattack_factor = (effect_factor(cave->effect[15]) * leader_multiplier)
    + takeover_multiplier;
  army->effect_arealattack_bonus  = cave->effect[16];
  army->effect_arealattack_factor = (effect_factor(cave->effect[17]) * leader_multiplier)
    + takeover_multiplier;
  army->effect_attackrate_bonus   = cave->effect[18];
  army->effect_attackrate_factor  = (effect_factor(cave->effect[19]) * leader_multiplier)
    + takeover_multiplier;
  army->effect_defenserate_bonus  = cave->effect[20];
  army->effect_defenserate_factor = (effect_factor(cave->effect[21]) * leader_multiplier)
    + takeover_multiplier;
  army->effect_size_bonus         = cave->effect[22];
  army->effect_size_factor        = effect_factor(cave->effect[23])
    + takeover_multiplier;
  army->effect_ranged_damage_resistance_bonus  = cave->effect[26];
  army->effect_ranged_damage_resistance_factor = (effect_factor(cave->effect[27]) * leader_multiplier)
    + takeover_multiplier;

// -------------------------------------------------------------------------------------

//  army->effect_rangeattack_bonus  = cave->effect[14];
  /* XXX should this be effect_factor(... + takeover_multiplier)? */
/*
  army->effect_rangeattack_factor = effect_factor(cave->effect[15])
    + takeover_multiplier;
  army->effect_arealattack_bonus  = cave->effect[16];
  army->effect_arealattack_factor = effect_factor(cave->effect[17])
    + takeover_multiplier;
  army->effect_attackrate_bonus   = cave->effect[18];
  army->effect_attackrate_factor  = effect_factor(cave->effect[19])
    + takeover_multiplier;
  army->effect_defenserate_bonus  = cave->effect[20];
  army->effect_defenserate_factor = effect_factor(cave->effect[21])
    + takeover_multiplier;
  army->effect_size_bonus         = cave->effect[22];
  army->effect_size_factor        = effect_factor(cave->effect[23])
    + takeover_multiplier;
  army->effect_ranged_damage_resistance_bonus  = cave->effect[26];
  army->effect_ranged_damage_resistance_factor = effect_factor(cave->effect[27])
    + takeover_multiplier;
*/
}

static int get_takeover_multiplier (const struct Cave *cave)
{
  return 1 + cave->building[TAKEOVER_MULTIPLIER_BUILDING];
}

static void prepare_battle(MYSQL           *database,
			   Battle          *battle,
			   struct Player   *attacker,
			   struct Player   *defender,
			   struct Cave     *cave_attacker,
			   struct Cave     *cave_defender,
			   const double    *battle_bonus,
			   int             takeover_multiplier,
			   int             *units,
			   int             *resources,
			   int             *attacker_artefact_id,
			   int             *defender_artefact_id,
			   struct Relation *relation_from_attacker,
			   struct Relation *relation_from_defender)
{
  /* initialize defender army */
  army_setup(&battle->defenders[0], cave_defender, battle_bonus,
	     takeover_multiplier,
	     cave_defender->unit, cave_defender->resource,
	     cave_defender->defense_system);

  /* initialize attacker army */
  army_setup(&battle->attackers[0], cave_attacker, battle_bonus, 0,
	     units, resources, NULL);

  /* artifacts */
  debug(DEBUG_BATTLE, "artifacts in target cave: %d", cave_defender->artefacts);

  if (cave_defender->artefacts > 0) {
    MYSQL_RES *result =
      mysql_query_fmt(database, "SELECT artefactID FROM " DB_ARTEFACT
		      " WHERE caveID = %d LIMIT 0,1",
		      cave_defender->cave_id);
    MYSQL_ROW row = mysql_fetch_row(result);

    if (!row) throw(SQL_EXCEPTION, "prepare_battle: no artefact in cave");
    *defender_artefact_id = atoi(row[0]);   /* warum nur das erste Artefakt?? */
  }

  debug(DEBUG_BATTLE, "defender artifact: %d", *defender_artefact_id);
  debug(DEBUG_BATTLE, "attacker artifact: %d", *attacker_artefact_id);

  /* get the relation boni */
  battle->attackers[0].relationMultiplicator =
    relation_from_attacker->attackerMultiplicator;
  battle->defenders[0].relationMultiplicator =
    relation_from_defender->defenderMultiplicator;
}

int artefact_lost() {
  double r = (double)rand() / RAND_MAX;
  return r < ARTEFACT_LOST_PERCENTAGE;
}

int map_get_bounds(MYSQL * database,
                   int *minX, int *maxX, int *minY, int *maxY) {
  MYSQL_RES *result = mysql_query_fmt(database,
    "SELECT MIN(xCoord) AS minX, "
    "       MAX(xCoord) AS maxX, "
    "       MIN(yCoord) AS minY, "
    "       MAX(yCoord) AS maxY  "
    "FROM Cave");

  MYSQL_ROW row = mysql_fetch_row(result);
  if (!row)
    return 0;

  *minX = mysql_get_int_field(result, row, "minX");
  *maxX = mysql_get_int_field(result, row, "maxX");
  *minY = mysql_get_int_field(result, row, "minY");
  *maxY = mysql_get_int_field(result, row, "maxY");

  return 1;
}


int artefact_loose_to_cave(MYSQL* database, struct Cave *cave) {
  MYSQL_RES *result;
  MYSQL_ROW row;
  dstring_t *query;
  int x, y;
  int minX, minY, maxX, maxY, rangeX, rangeY;

  x = ((int) ( (ARTEFACT_LOST_RANGE*2.0+1.0)*rand() / (RAND_MAX+1.0) ) )
      - ARTEFACT_LOST_RANGE;  /* number between -ALR <= n <= ALR */
  y = ((int) ( (ARTEFACT_LOST_RANGE*2.0+1.0)*rand() / (RAND_MAX+1.0) ) )
      - ARTEFACT_LOST_RANGE;  /* number between -ALR <= n <= ALR */

  x += cave->xpos;
  y += cave->ypos;   /* these numbers may be out of range */

  if (! map_get_bounds(database, &minX, &maxX, &minY, &maxY)) {
    return 0;
  }
  rangeX = maxX - minX +1;
  rangeY = maxY - minY +1;

  x = ( (x-minX+rangeX) % (rangeX) ) + minX;
  y = ( (y-minY+rangeY) % (rangeY) ) + minY;

  query = dstring_new("SELECT caveID FROM " DB_MAIN_TABLE_CAVE
		      " WHERE xCoord = %d AND yCoord = %d", x, y);

  debug(DEBUG_TICKER, "%s", dstring_str(query));

  result = mysql_query_dstring(database, query);

  row = mysql_fetch_row(result);
  return row ? mysql_get_int_field(result, row, "caveID") : 0 ;
}

static void after_battle_change_artefact_ownership(
  MYSQL        *database,
  int          winner,
  int          *artefact,
  int          *artifact_id,
  int          *artefact_def,
  int          defender_cave_id,
  struct Cave  *defender_cave,
  int          *lostTo)
{
  *lostTo = 0;

  if (winner == FLAG_ATTACKER) {
    if (*artefact == 0 && *artefact_def > 0) {
      try {
	remove_effects_from_cave(database, *artefact_def);
	uninitiate_artefact(database, *artefact_def);
	remove_artefact_from_cave(database, *artefact_def);
	*artifact_id = *artefact_def;

	if ( artefact_lost() ) {
	  *lostTo = artefact_loose_to_cave(database, defender_cave);
	  if (*lostTo)
	    put_artefact_into_cave(database, *artefact_def, *lostTo);
	} else {
	  *artefact = *artefact_def;
	}
      } catch (SQL_EXCEPTION) {
	warning("%s", except_msg);
      } end_try;
    }
  } else if (*artefact > 0) {
    try {
      put_artefact_into_cave(database, *artefact, defender_cave_id);
      *artifact_id = *artefact;
      *artefact = 0;
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;
  }
}

static void update_fame(MYSQL      *database,
			int        playerID,
			const char *tribe,
			const char *tribe_target,
			int fame)
{
  /* add fame to relation between tribes */
  if (tribe && tribe_target) {
    debug(DEBUG_BATTLE, "fame: %+d for %s/%s", fame, tribe, tribe_target);

    mysql_query_fmt(database, "UPDATE " DB_MAIN_TABLE_RELATION
			      " SET fame = fame + %d"
			      " WHERE tribe = '%s' AND tribe_target = '%s'",
		    fame, tribe, tribe_target);
  }

  /* add fame for the given playerID in Player */
  debug(DEBUG_BATTLE, "fame: %+d for player %d", fame, playerID);

  mysql_query_fmt(database, "UPDATE " DB_MAIN_TABLE_PLAYER
			    " SET fame = fame + %d WHERE playerID = %d",
		  fame, playerID);
}

static void after_battle_defender_update(MYSQL           *database,
					 int             player_id,
					 const Battle    *battle,
					 int             cave_id,
					 struct Relation *relation,
					 int             fame)
{
  dstring_t *ds;
  int       update = 0;
  int       i;


  /* positive or negative fame? */
  fame *= battle->winner == FLAG_DEFENDER ? 1 : -1;

  /* construct defender update */
  ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

  /* which units need an update */
  debug(DEBUG_BATTLE, "preparing units update");
  for (i = 0; i < MAX_UNIT; ++i) {
    const struct Army_unit *unit = &battle->defenders[0].units[i];

    if (unit->amount_before != unit->amount_after) {
      dstring_append(ds, "%s%s = %d", update ? "," : "",
		     unitTypeList[i].dbFieldName, unit->amount_after);
      update = 1;
    }
  }

  /* which defense systems need an update */
  debug(DEBUG_BATTLE, "preparing defensesystems update");
  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    const struct Army_unit *defense_system =
      &battle->defenders[0].defenseSystems[i];

    if (defense_system->amount_before != defense_system->amount_after) {
      dstring_append(ds, "%s%s = %d", update ? "," : "",
		     defenseSystemTypeList[i].dbFieldName,
		     defense_system->amount_after);
      update = 1;
    }
  }

  /* which resources need an update */
  debug(DEBUG_BATTLE, "preparing resources update");
  for (i = 0; i < MAX_RESOURCE; ++i)
    if (battle->defenders[0].resourcesBefore[i] !=
	battle->defenders[0].resourcesAfter[i]) {
      dstring_append(ds, "%s%s = LEAST(%d, %s)", update ? "," : "",
		     resourceTypeList[i].dbFieldName,
		     battle->defenders[0].resourcesAfter[i],
		     parse_function(resourceTypeList[i].maxLevel));
      update = 1;
    }

  dstring_append(ds, " WHERE caveID = %d", cave_id);

  if (update) {
    debug(DEBUG_BATTLE, "%s", dstring_str(ds));

    mysql_query_dstring(database, ds);
  }

  /* Insert fame into relation */
  if (relation->defenderReceivesFame) {
    update_fame(database,
		player_id, relation->tribe, relation->tribe_target, fame);
  }

}

static void takeover_cave(MYSQL  *database,
		   int    cave_id,
		   int    attacker_id)
{
  /* change owner of cave */
  mysql_query_fmt(database, "UPDATE " DB_MAIN_TABLE_CAVE
			    " SET playerID = %d WHERE caveID = %d",
	          attacker_id, cave_id);

  /* delete research from event table*/
  mysql_query_fmt(database, "DELETE FROM Event_science WHERE caveID = %d",
		  cave_id);

  /* copy sciences from new owner to cave */
  science_update_caves(database, attacker_id);
}

static void after_battle_attacker_update(
  MYSQL        *database,
  int          player_id,
  const Battle *battle,
  int          source_caveID,
  int          target_caveID,
  const char   *speed_factor,
  const char   *return_start,
  const char   *return_end,
  int          artefact,
  struct Relation *relation,
  int             fame)
{
  int update = 0;
  int i;

  /* positive or negative fame? */
  fame *= battle->winner == FLAG_ATTACKER ? 1 : -1;

  /* construct attacker update */
  for (i = 0; i < MAX_UNIT; ++i)
    if (battle->attackers[0].units[i].amount_after > 0) {
      update = 1;
      break;
    }

  if (update) {
    dstring_t *ds;

    /* send remaining units back */
    ds = dstring_new("INSERT INTO " DB_MAIN_TABLE_MOVEMENT
		     " (caveID, target_caveID, source_caveID, movementID,"
		     " speedFactor, event_start, event_end, artefactID");

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, ",%s", resourceTypeList[i].dbFieldName);
    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%s", unitTypeList[i].dbFieldName);

    dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, %s, %s, %d",
		   source_caveID, source_caveID, target_caveID, RUECKKEHR,
		   speed_factor, return_start, return_end, artefact);

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, ",%d", battle->attackers[0].resourcesAfter[i]);
    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%d", battle->attackers[0].units[i].amount_after);

    dstring_append(ds, ")");

    debug(DEBUG_BATTLE, "%s", dstring_str(ds));

    mysql_query_dstring(database, ds);
  }

  /* Insert fame into relation */
  if (relation->attackerReceivesFame) {
    update_fame(database,
		player_id, relation->tribe, relation->tribe_target, fame);
  }
}

static void after_takeover_attacker_update(MYSQL           *database,
					   int             player_id,
					   const Battle    *battle,
					   int             target_caveID,
					   int             artefact,
					   struct Relation *relation,
					   int             fame)
{
  int update = 0;
  int i;

  /* positive or negative fame? */
  fame *= battle->winner == FLAG_ATTACKER ? 1 : -1;

  /* construct attacker update */
  for (i = 0; i < MAX_UNIT; ++i)
    if (battle->attackers[0].units[i].amount_after > 0) {
      update = 1;
      break;
    }

  if (update) {
    dstring_t *ds;

    /* put artefact into cave */
    if (artefact > 0)
      put_artefact_into_cave(database, artefact, target_caveID);

    /* put remaining units into target_cave */
    ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		     resourceTypeList[i].dbFieldName,
		     resourceTypeList[i].dbFieldName,
		     battle->attackers[0].resourcesAfter[i],
		     parse_function(resourceTypeList[i].maxLevel));

    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%s = %s + %d",
		     unitTypeList[i].dbFieldName,
		     unitTypeList[i].dbFieldName,
		     battle->attackers[0].units[i].amount_after);

    dstring_append(ds, " WHERE caveID = %d", target_caveID);

    debug(DEBUG_BATTLE, "%s", dstring_str(ds));

    mysql_query_dstring(database, ds);
  }

  /* Insert fame into relation */
  if (relation->attackerReceivesFame) {
    update_fame(database,
		player_id, relation->tribe, relation->tribe_target, fame);
  }
}


/*
 * This function is responsible for all the movement
 *
 * @params database  the function needs this link to the DB
 * @params result    current movement event (from DB)
 */
void movement_handler (MYSQL *database, MYSQL_RES *result)
{
  MYSQL_ROW row;
  int movementID;
  int event_movementID = 0; // ADDED by chris--- for quests
  int target_caveID;
  int source_caveID;
  const char *speed_factor;

  time_t event_start;
  time_t event_end;
  const char *return_start;
  char return_end[TIMESTAMP_LEN];

  struct Cave cave1;
  struct Cave cave2;
  struct Player player1;
  struct Player player2;
  struct Relation relation1;
  struct Relation relation2;

  int i;
  int units[MAX_UNIT];
  int resources[MAX_RESOURCE];
  int takeover_multiplier;
  int change_owner;
  int fame = 0;
  Battle *battle;
  dstring_t *ds;

  /* time related issues */
  const double *battle_bonus;

  /* artifacts */
  int artefact = 0;
  int artefact_def = 0;
  int artifact_id = 0;
  int lostTo = 0;

  debug(DEBUG_TICKER, "entering function movement_handler()");

  row = mysql_fetch_row(result);
  if (!row) throw(SQL_EXCEPTION, "movement_handler: no movement event");

  /* get movement id and target/source cave id */
  movementID    = mysql_get_int_field(result, row, "movementID");
  target_caveID = mysql_get_int_field(result, row, "target_caveID");
  source_caveID = mysql_get_int_field(result, row, "source_caveID");
  speed_factor  = mysql_get_string_field(result, row, "speedFactor");
  
  // ADDED by chris--- for quests
  event_movementID = mysql_get_int_field(result, row, "event_movementID");

  /* get event_start and event_end */
  event_start  = mysql_get_time_field(result, row, "event_start");
  return_start = mysql_get_string_field(result, row, "event_end");
  event_end    = make_time(return_start);
  make_timestamp(return_end, event_end + (event_end - event_start));

  /* get units, resources and artifact id */
  get_unit_list(result, row, units, resources);

  try {
    artefact = mysql_get_int_field(result, row, "artefactID");
  } catch (SQL_EXCEPTION) {
    warning("%s", except_msg);
  } end_try;

  /* TODO reduce number of queries */
  get_cave_info(database, source_caveID, &cave1);
  get_cave_info(database, target_caveID, &cave2);

  if (cave1.player_id)
    get_player_info(database, cave1.player_id, &player1);
  else	/* System */
    memset(&player1, 0, sizeof player1);

  if (cave2.player_id == cave1.player_id)
    player2 = player1;
  else if (cave2.player_id)
    get_player_info(database, cave2.player_id, &player2);
  else	/* System */
    memset(&player2, 0, sizeof player2);
    
//------------------------------------------------------------------------------
// GOD STUFF - inserted by chris---
//------------------------------------------------------------------------------

if (cave2.player_id > 0 ) {

char event_new_end[TIMESTAMP_LEN];
make_timestamp(event_new_end, event_end);

//  if (target_caveID == 1080 || target_caveID == 250)
  if (target_caveID == 1018)
{
    debug(DEBUG_TICKER, "god movement: from cave %d, to cave %d", source_caveID, target_caveID);

  if (movementID == 5) {
    debug(DEBUG_TICKER, "movementID is %d: Rückkehr", movementID);
  } else if (movementID > 2 && movementID != 4)
    {
//    if (source_caveID != 250 && source_caveID != 1080) {
    if (source_caveID != 1080) {
    debug(DEBUG_TICKER, "movementID is %d: ANGRIFF! Wird in Datenbank eingetragen", movementID);
    mysql_query_fmt(database,
			"INSERT INTO event_gods SET target_caveID = %d, source_caveID = %d, impact = %s, "
			"blocked = 0, event = 2, eventID = %d, playerID = %d",
			target_caveID, source_caveID, event_new_end, movementID, cave1.player_id);
     }
     } else debug(DEBUG_TICKER, "movementID is %d: unwichtig", movementID);
}
}

//------------------------------------------------------------------------------


//  debug(DEBUG_TICKER, "caveID = %d, movementID = %d", target_caveID, movementID);
	
// QUEST STUFF inserted by chris---
// ------------------------------------------------------------------

int questmovement = 0;
int questID = 0;

// Ok, we have a movement here. Now we check if this movement
// completes a quest
// 1st we need the active quests for this player

//if (movementID == 5) debug(DEBUG_TICKER, "Checking active quests for player %d", cave2.player_id);
//  else debug(DEBUG_TICKER, "Checking active quests for player %d", cave1.player_id);
i = 0;
int num_rows = 0;
//char timestamp[TIMESTAMP_LEN];

    ds = dstring_new("");

    if (movementID == 5) dstring_set(ds, "SELECT * FROM quests_active WHERE playerID = %d", cave2.player_id);
      else dstring_set(ds, "SELECT * FROM quests_active WHERE playerID = %d", cave1.player_id);
    result = mysql_query_dstring(database, ds);
    num_rows = mysql_num_rows(result);

    if (num_rows > 0) {
      
      int questarrayID[num_rows];
      for (i = 0; i < num_rows; i++) {
        // Getting the quests
        MYSQL_ROW row = mysql_fetch_row(result);
        questarrayID[i] = mysql_get_int_field(result, row, "questID");
        debug(DEBUG_TICKER, "questID is: %d", questarrayID[i]);
      }
      for (i = 0; i < num_rows; i++) {
        questID = questarrayID[i];
        if (movementID == 5) quest_check_win (database, questID, &cave2.player_id, event_movementID, return_start, &source_caveID);
          else quest_check_win (database, questID, &cave1.player_id, event_movementID, return_start, &target_caveID);
      } // end for
    } // end no active quests
      else {
      // maybe there are some quests the player should know about
      process_quest_visit(database , &target_caveID, &cave1.player_id);
    }
      
// MARKED FOR DELETION:
// Is it a quest movement?
//if (movementID > 10) questmovement = 1;
//if (movementID == 17) questmovement = 1;

//if (questmovement) movementID = movementID -10;

// Is it movement 7? If yes it is a quest visit and we need to process something
// if (movementID == 7) {
//  debug(DEBUG_TICKER, "movementID is 7, we need to enter process_quest_visit()");
//  process_quest_visit(database , &target_caveID, &cave1.player_id);
//}


// ------------------------------------------------------------------

  /**********************************************************************/
  /*** THE INFAMOUS GIANT SWITCH ****************************************/
  /**********************************************************************/

  switch (movementID) {

    /**********************************************************************/
    /*** ROHSTOFFE BRINGEN ************************************************/
    /**********************************************************************/
    case ROHSTOFFE_BRINGEN:

      /* put resources into cave */
      ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
	dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		resourceTypeList[i].dbFieldName,
		resourceTypeList[i].dbFieldName, resources[i],
		parse_function(resourceTypeList[i].maxLevel));

      dstring_append(ds, " WHERE caveID = %d", target_caveID);

      mysql_query_dstring(database, ds);

      if (artefact > 0)
	put_artefact_into_cave(database, artefact, target_caveID);

      /* record in takeover table */
      dstring_set(ds, "UPDATE " DB_CAVE_TAKEOVER " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
	dstring_append(ds, "%s%s = %s + %d", i > 0 ? "," : "",
		resourceTypeList[i].dbFieldName,
		resourceTypeList[i].dbFieldName, resources[i]);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
		     target_caveID, cave1.player_id);

      mysql_query_dstring(database, ds);

      /* send all units back */
      dstring_set(ds, "INSERT INTO " DB_MAIN_TABLE_MOVEMENT
		      " (caveID, target_caveID, source_caveID, movementID,"
		      " speedFactor, event_start, event_end");

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%s", unitTypeList[i].dbFieldName);

      dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, %s, %s",
	      source_caveID, source_caveID, target_caveID, RUECKKEHR,
	      speed_factor, return_start, return_end);

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%d", units[i]);

      dstring_append(ds, ")");

      mysql_query_dstring(database, ds);

      /* generate trade report and receipt for sender */
      trade_report(database, &cave1, player1.name, &cave2, player2.name,
		   resources, NULL, artefact);
		   
// ADDED by chris--- for quests -----------------------------------------------      
      
      /* checking if this was a quest cave */
      if (isCaveQuestCave(database, &target_caveID)) {
        debug(DEBUG_TICKER, "Quest Cave");
        
      /* is it invisible to normal players? */
        if (caveIsInvisible(database, &target_caveID)) {
          debug(DEBUG_TICKER, "Cave is invisible to normal players");
          
      /* is it invisible to this player? */
          if (caveIsInvisibleToPlayer(database, &target_caveID, &cave1.player_id)) {
            debug(DEBUG_TICKER, "Cave is invisible to this player");
            
      /* However, now it is not invisible to him anymore... */
            setCaveVisibleToPlayer(database, &target_caveID, &cave1.player_id);
            debug(DEBUG_TICKER, "Cave is now visible to this player");
          }
        }
      }
// -------------------------------------------------------------------------
		   
      break;

    /**********************************************************************/
    /*** EINHEITEN/ROHSTOFFE VERSCHIEBEN **********************************/
    /**********************************************************************/
    case VERSCHIEBEN:
    case RUECKKEHR:

      /* put resources into cave */
      ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
	dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		resourceTypeList[i].dbFieldName,
		resourceTypeList[i].dbFieldName, resources[i],
		parse_function(resourceTypeList[i].maxLevel));

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%s = %s + %d",
		 unitTypeList[i].dbFieldName,
		 unitTypeList[i].dbFieldName, units[i]);

      dstring_append(ds, " WHERE caveID = %d", target_caveID);

      mysql_query_dstring(database, ds);

      if (artefact > 0)
	put_artefact_into_cave(database, artefact, target_caveID);

      if (movementID == VERSCHIEBEN)
      {
	/* record in takeover table */
	dstring_set(ds, "UPDATE " DB_CAVE_TAKEOVER " SET ");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, "%s%s = %s + %d", i > 0 ? "," : "",
		  resourceTypeList[i].dbFieldName,
		  resourceTypeList[i].dbFieldName, resources[i]);

	dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
		       target_caveID, cave1.player_id);

	mysql_query_dstring(database, ds);

	/* generate trade report and receipt for sender */
	trade_report(database,&cave1, player1.name, &cave2, player2.name,
		     resources, units, artefact);
      }
      else
      {
	/* generate return report */
	return_report(database, &cave1, player1.name, &cave2, player2.name,
		      resources, units, artefact);
      }
      
// ADDED by chris--- for quests -----------------------------------------------      
      
      /* checking if this was a quest cave */
      if (isCaveQuestCave(database, &target_caveID)) {
        debug(DEBUG_TICKER, "Quest Cave");
        
      /* is it invisible to normal players? */
        if (caveIsInvisible(database, &target_caveID)) {
          debug(DEBUG_TICKER, "Cave is invisible to normal players");
          
      /* is it invisible to this player? */
          if (caveIsInvisibleToPlayer(database, &target_caveID, &cave1.player_id)) {
            debug(DEBUG_TICKER, "Cave is invisible to this player");
            
      /* However, now it is not invisible to him anymore... */
            setCaveVisibleToPlayer(database, &target_caveID, &cave1.player_id);
            debug(DEBUG_TICKER, "Cave is now visible to this player");
          }
        }
      }
// -------------------------------------------------------------------------
      
      break;

    /**********************************************************************/
    /*** ANGREIFEN ********************************************************/
    /**********************************************************************/
    case ANGREIFEN:

      /* beginner protection active in target cave? */
      if (cave_is_protected(&cave2))
      {
	/* send remaining units back */
	ds = dstring_new("INSERT INTO " DB_MAIN_TABLE_MOVEMENT
			 " (caveID, target_caveID, source_caveID, movementID,"
			 " speedFactor, event_start, event_end, artefactID");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%s", resourceTypeList[i].dbFieldName);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%s", unitTypeList[i].dbFieldName);

	dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, %s, %s, %d",
		       source_caveID, source_caveID, target_caveID, RUECKKEHR,
		       speed_factor, return_start, return_end, artefact);

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%d", resources[i]);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%d", units[i]);

	dstring_append(ds, ")");

	mysql_query_dstring(database, ds);

	/* create and send reports */
	protected_report(database, &cave1, player1.name, &cave2, player2.name);
	
// ADDED by chris--- for quests -----------------------------------------------      
      
      /* checking if this was a quest cave */
      if (isCaveQuestCave(database, &target_caveID)) {
        debug(DEBUG_TICKER, "Quest Cave");
        
      /* is it invisible to normal players? */
        if (caveIsInvisible(database, &target_caveID)) {
          debug(DEBUG_TICKER, "Cave is invisible to normal players");
          
      /* is it invisible to this player? */
          if (caveIsInvisibleToPlayer(database, &target_caveID, &cave1.player_id)) {
            debug(DEBUG_TICKER, "Cave is invisible to this player");
            
      /* However, now it is not invisible to him anymore... */
            setCaveVisibleToPlayer(database, &target_caveID, &cave1.player_id);
            debug(DEBUG_TICKER, "Cave is now visible to this player");
          }
        }
      }
// -------------------------------------------------------------------------
	
	break;
      }

      /* get relations between the two players' tribes */
      get_relation_info(database, player1.tribe, player2.tribe, &relation1);
      get_relation_info(database, player2.tribe, player1.tribe, &relation2);
      debug(DEBUG_BATTLE, "Relationtypes: %d and %d", relation1.relationType,
            relation2.relationType);

      battle = battle_create(1, 1);

      battle_bonus = get_battle_bonus();

      debug(DEBUG_BATTLE, "entering prepare_battle");
      /* prepare structs for battle, exceptions are uncaught! */
      prepare_battle(database,
		     battle,
		     &player1,
		     &player2,
		     &cave1, &cave2, battle_bonus, 0,
		     units, resources,
		     &artefact, &artefact_def,
		     &relation1, &relation2);

      /* calculate battle result */
      calcBattleResult(battle);

      /* change artifact ownership */
      debug(DEBUG_BATTLE, "entering change artefact");
      after_battle_change_artefact_ownership(
	database, battle->winner, &artefact, &artifact_id, &artefact_def,
	target_caveID, &cave2, &lostTo);

      /* attackers artifact (if any) is stored in variable artefact,
	 artifact_id is id of the artifact that changed owner (or 0) */

      /* calculate the fame */
      fame = fame_calculate(battle);

      debug(DEBUG_FAME, "DEBUG_FAME: Fame: %d, SizeAtt: %d, SizeDeff: %d,"
		        "Winner: %d", fame,
                        battle->attackers_acc_hitpoints_units_before +
	                battle->attackers_acc_hitpoints_defenseSystems_before,
                        battle->defenders_acc_hitpoints_units_before +
	                battle->defenders_acc_hitpoints_defenseSystems_before,
			battle->winner);

      /* construct attacker update */
      debug(DEBUG_BATTLE, "entering attacker update");
      after_battle_attacker_update(database, player1.player_id, battle,
				   source_caveID, target_caveID, speed_factor,
				   return_start, return_end, artefact,
				   &relation1, fame);

      /* defender update: exception still uncatched (better leave) */
      debug(DEBUG_BATTLE, "entering defender update");
      after_battle_defender_update(database, player2.player_id,
				   battle, target_caveID, &relation2, fame);

      /* create and send reports */
      battle_report(database, &cave1, player1.name, &cave2, player2.name,
		    battle, artifact_id, lostTo, 0, 0, &relation1, &relation2,
		    fame);

// INSERTED by chris--- for stats		    
// Update stats
    ds = dstring_new("UPDATE stats SET kampfberichte = kampfberichte + 1");
      mysql_query_dstring(database, ds);
// ------------------------------------------

      /* reset DB_CAVE_TAKEOVER */
      ds = dstring_new("UPDATE " DB_CAVE_TAKEOVER " SET status = 0");

      for (i = 0; i < MAX_RESOURCE; ++i)
	dstring_append(ds, ",%s = 0", resourceTypeList[i].dbFieldName);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
		  target_caveID, cave1.player_id);

      mysql_query_dstring(database, ds);

      /* cave takeover by battle */
      if (battle->winner == FLAG_ATTACKER &&
	  terrainTypeList[cave2.terrain].takeoverByCombat) {
	mysql_query_fmt(database, "UPDATE " DB_MAIN_TABLE_CAVE
				  " SET playerID = %d"
				  " WHERE caveID = %d",
			cave1.player_id, target_caveID);

	mysql_query_fmt(database, "DELETE FROM Event_science"
				  " WHERE caveID = %d",
			target_caveID);

	science_update_caves(database, cave1.player_id);
      }
      
// ADDED by chris--- for quests -----------------------------------------------      
      
      /* checking if this was a quest cave */
      if (isCaveQuestCave(database, &target_caveID)) {
        debug(DEBUG_TICKER, "Quest Cave");
        
      /* is it invisible to normal players? */
        if (caveIsInvisible(database, &target_caveID)) {
          debug(DEBUG_TICKER, "Cave is invisible to normal players");
          
      /* is it invisible to this player? */
          if (caveIsInvisibleToPlayer(database, &target_caveID, &cave1.player_id)) {
            debug(DEBUG_TICKER, "Cave is invisible to this player");
            
      /* However, now it is not invisible to him anymore... */
            setCaveVisibleToPlayer(database, &target_caveID, &cave1.player_id);
            debug(DEBUG_TICKER, "Cave is now visible to this player");
          }
        }
      }
// -------------------------------------------------------------------------      
      break;

    /**********************************************************************/
    /*** Spionieren *******************************************************/
    /**********************************************************************/
    case SPIONAGE:

      /* generate spy report */
      if (spy_report(database, &cave1, player1.name, &cave2, player2.name,
		     resources, units, artefact))
      {

// INSERTED by chris--- for stats		    
// Update stats
    ds = dstring_new("UPDATE stats SET spioberichte = spioberichte + 1");
      mysql_query_dstring(database, ds);
// ------------------------------------------

	/* send all units back */
	ds = dstring_new("INSERT INTO " DB_MAIN_TABLE_MOVEMENT
			 " (caveID, target_caveID, source_caveID, movementID,"
			 " speedFactor, event_start, event_end, artefactID");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%s", resourceTypeList[i].dbFieldName);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%s", unitTypeList[i].dbFieldName);

	dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, %s, %s, %d",
		source_caveID, source_caveID, target_caveID, RUECKKEHR,
		speed_factor, return_start, return_end, artefact);

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%d", resources[i]);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%d", units[i]);

	dstring_append(ds, ")");

	mysql_query_dstring(database, ds);
      }
      else
      {
	/* put resources into cave */
	ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		  resourceTypeList[i].dbFieldName,
		  resourceTypeList[i].dbFieldName, resources[i],
		  parse_function(resourceTypeList[i].maxLevel));

	dstring_append(ds, " WHERE caveID = %d", target_caveID);

	mysql_query_dstring(database, ds);

	if (artefact > 0)
	  put_artefact_into_cave(database, artefact, target_caveID);
      }
      
// ADDED by chris--- for quests -----------------------------------------------      
      
      /* checking if this was a quest cave */
      if (isCaveQuestCave(database, &target_caveID)) {
        debug(DEBUG_TICKER, "Quest Cave");
        
      /* is it invisible to normal players? */
        if (caveIsInvisible(database, &target_caveID)) {
          debug(DEBUG_TICKER, "Cave is invisible to normal players");
          
      /* is it invisible to this player? */
          if (caveIsInvisibleToPlayer(database, &target_caveID, &cave1.player_id)) {
            debug(DEBUG_TICKER, "Cave is invisible to this player");
            
      /* However, now it is not invisible to him anymore... */
            setCaveVisibleToPlayer(database, &target_caveID, &cave1.player_id);
            debug(DEBUG_TICKER, "Cave is now visible to this player");
          }
        }
      }
// -------------------------------------------------------------------------
      
      break;

    /**********************************************************************/
    /*** UEBERNEHMEN ******************************************************/
    /**********************************************************************/
    case TAKEOVER:

      /* secure or protected target gave? */
      if (cave2.secure || cave_is_protected(&cave2))
      {
	/* send remaining units back */
	ds = dstring_new("INSERT INTO " DB_MAIN_TABLE_MOVEMENT
			 " (caveID, target_caveID, source_caveID, movementID,"
			 " speedFactor, event_start, event_end, artefactID");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%s", resourceTypeList[i].dbFieldName);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%s", unitTypeList[i].dbFieldName);

	dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, %s, %s, %d",
		       source_caveID, source_caveID, target_caveID, RUECKKEHR,
		       speed_factor, return_start, return_end, artefact);

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%d", resources[i]);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%d", units[i]);

	dstring_append(ds, ")");

	mysql_query_dstring(database, ds);

	/* create and send reports */
	/* FIXME: use different message in report (protected -> secure) */
	protected_report(database, &cave1, player1.name, &cave2, player2.name);
	
// ADDED by chris--- for quests -----------------------------------------------      
      
      /* checking if this was a quest cave */
      if (isCaveQuestCave(database, &target_caveID)) {
        debug(DEBUG_TICKER, "Quest Cave");
        
      /* is it invisible to normal players? */
        if (caveIsInvisible(database, &target_caveID)) {
          debug(DEBUG_TICKER, "Cave is invisible to normal players");
          
      /* is it invisible to this player? */
          if (caveIsInvisibleToPlayer(database, &target_caveID, &cave1.player_id)) {
            debug(DEBUG_TICKER, "Cave is invisible to this player");
            
      /* However, now it is not invisible to him anymore... */
            setCaveVisibleToPlayer(database, &target_caveID, &cave1.player_id);
            debug(DEBUG_TICKER, "Cave is now visible to this player");
          }
        }
      }
// -------------------------------------------------------------------------
	
	break;
      }

      get_relation_info(database, player1.tribe, player2.tribe, &relation1);
      get_relation_info(database, player2.tribe, player1.tribe, &relation2);

      battle = battle_create(1, 1);

      battle_bonus = get_battle_bonus();
      takeover_multiplier = get_takeover_multiplier(&cave2);

      /* prepare structs for battle, exceptions are uncaught! */
      prepare_battle(database,
		     battle,
		     &player1,
		     &player2,
		     &cave1, &cave2, battle_bonus, takeover_multiplier,
		     units, resources,
		     &artefact, &artefact_def,
		     &relation1, &relation2);

      /* calculate battle result */
      calcBattleResult(battle);

      /* change artifact ownership */
      after_battle_change_artefact_ownership(
	database, battle->winner, &artefact, &artifact_id, &artefact_def,
	target_caveID, &cave2, &lostTo);

      /* attackers artifact (if any) is stored in variable artefact,
	 artifact_id is id of the artifact that changed owner (or 0) */

      /* defender update: exception still uncatched (better leave) */
      after_battle_defender_update(database, player2.player_id,
				   battle, target_caveID, &relation2, fame);

      /* attacker won:  put survivors into cave, change owner
       * attacker lost: send back survivors */
      change_owner =
	battle->winner == FLAG_ATTACKER && cave2.player_id != PLAYER_SYSTEM &&
	player1.max_caves > get_number_of_caves(database, player1.player_id);

      if (change_owner)
      {
      
// INSERTED by chris--- for stats		    
// Update stats
        ds = dstring_new("UPDATE stats SET takeover_success = takeover_success + 1");
      mysql_query_dstring(database, ds);
// ------------------------------------------
      
	debug(DEBUG_TAKEOVER, "change owner of cave %d to new owner %d",
	      target_caveID, cave1.player_id);
	takeover_cave(database, target_caveID, cave1.player_id);
	after_takeover_attacker_update(database, player1.player_id,
				       battle, target_caveID,
				       artefact, &relation1, fame);
      }
      else	/* send survivors back */
      {
	debug(DEBUG_TAKEOVER, "send back attacker's suvivors");
	after_battle_attacker_update(database, player1.player_id, battle,
				     source_caveID, target_caveID, speed_factor,
				     return_start, return_end, artefact,
				     &relation1, fame);
      }

      /* create and send reports */
      battle_report(database, &cave1, player1.name, &cave2, player2.name,
		    battle, artifact_id, lostTo, change_owner,
		    1 + takeover_multiplier, &relation1, &relation2, fame);
		    
// ADDED by chris--- for quests -----------------------------------------------      
      
      /* checking if this was a quest cave */
      if (isCaveQuestCave(database, &target_caveID)) {
        debug(DEBUG_TICKER, "Quest Cave");
        
      /* is it invisible to normal players? */
        if (caveIsInvisible(database, &target_caveID)) {
          debug(DEBUG_TICKER, "Cave is invisible to normal players");
          
      /* is it invisible to this player? */
          if (caveIsInvisibleToPlayer(database, &target_caveID, &cave1.player_id)) {
            debug(DEBUG_TICKER, "Cave is invisible to this player");
            
      /* However, now it is not invisible to him anymore... */
            setCaveVisibleToPlayer(database, &target_caveID, &cave1.player_id);
            debug(DEBUG_TICKER, "Cave is now visible to this player");
          }
        }
      }
// -------------------------------------------------------------------------
		    
      break;
      
    /**********************************************************************/
    /*** QUEST VISIT ******************************************************/
    /**********************************************************************/
    case QUESTVISIT:

      /* send all units back */
      ds = dstring_new("INSERT INTO " DB_MAIN_TABLE_MOVEMENT
		      " (caveID, target_caveID, source_caveID, movementID,"
		      " speedFactor, event_start, event_end");

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%s", unitTypeList[i].dbFieldName);

      dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, %s, %s",
	      source_caveID, source_caveID, target_caveID, RUECKKEHR,
	      speed_factor, return_start, return_end);

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%d", units[i]);

      dstring_append(ds, ")");

      mysql_query_dstring(database, ds);

      break;


    default:
      throw(BAD_ARGUMENT_EXCEPTION, "movement_handler: unknown movementID");
  }

  /**********************************************************************/
  /*** END OF THE INFAMOUS GIANT SWITCH *********************************/
  /**********************************************************************/

  debug(DEBUG_TICKER, "leaving function movement_handler()");
}
