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

#include "artefact.h"
#include "calc_battle.h"
#include "cave.h"
#include "database.h"
#include "event_handler.h"
#include "except.h"
#include "function.h"
#include "logging.h"
#include "memory.h"
#include "message.h"
#include "ticker.h"
#include "ugatime.h"
#include "game_rules.h"

/* movement constants */
#define ROHSTOFFE_BRINGEN	1
#define VERSCHIEBEN		2
#define ANGREIFEN		3
#define SPIONAGE		4
#define RUECKKEHR		5
#define TAKEOVER		6

/* artefact constants */
#define ARTEFACT_LOST_CHANCE	0.2
#define ARTEFACT_LOST_RANGE	2

/* resource constants */
/* save resources at delivery for takeover (x of 100) */
#define TAKEOVER_RESOURCE_SAVE_PERCENTAGE	75

/* FARMEN = KEINE RESSIS */
/* farming constants */
#define NO_FARMING		0
#define FARMING			1

/* Mit diesen Flag kann man das Resi klauen nur w�hrend des Krieges erlauben*/
/* 0 bedeutet es geht immer*/
#define STEALOUTSIDEWAR		1

#define FAME_MAX_OVERPOWER_FACTOR 4

#define drand()		(rand() / (RAND_MAX+1.0))

static int isTakeoverableCave(db_t *database, int caveID) {
    db_result_t *result;

    result = db_query(database,
	    "SELECT * FROM Cave WHERE playerID=0 AND caveID = %d AND takeoverable=1;",
	    caveID);
    if(db_result_next_row(result))
	return 1;
    return 0;
}

//return 0 falls nicht
static int isVerschiebenAllowed(db_t *database,
                        struct Player *sender,
                        struct Player *reciever,
                        struct Relation *attToDef){
    //einfache F�lle zuerst
    //beide spieler im selben stamm
    if(strcasecmp(sender->tribe, reciever->tribe) == 0)
        return 1;
    //wenn beide im vorkrieg sind dann m�sste das in der relation ja schon stehen
    if(attToDef->relationType == RELATION_TYPE_PRE_WAR)
        return 1;
    //wenn beide im krieg sind dann m�sste das in der relation ja schon stehen
    if(attToDef->relationType == RELATION_TYPE_WAR)
        return 1;
    //Im Kriegsbuendniss ist es auch m�glich
    if(attToDef->relationType == RELATION_TYPE_WAR_TREATMENT)
        return 1;

    if(reciever->player_id==0)
       return 1;

    //nun noch das schwierigere
    //ist einer von beiden im krieg
    db_result_t *result;
    result = db_query(database,
            "SELECT * FROM Relation WHERE relationType = %d AND tribe like '%s'",
            RELATION_TYPE_PRE_WAR, sender->tribe);
    if(db_result_next_row(result))
        return 0;
    result = db_query(database,
            "SELECT * FROM Relation WHERE relationType = %d AND tribe like '%s'",
            RELATION_TYPE_PRE_WAR, reciever->tribe);
    if(db_result_next_row(result))
        return 0;
    result = db_query(database,
            "SELECT * FROM Relation WHERE relationType = %d AND tribe like '%s'",
            RELATION_TYPE_WAR, sender->tribe);
    if(db_result_next_row(result))
        return 0;
    result = db_query(database,
            "SELECT * FROM Relation WHERE relationType = %d AND tribe like '%s'",
            RELATION_TYPE_WAR, reciever->tribe);
    if(db_result_next_row(result))
        return 0;

  return 1;
}

static int check_farming (db_t *database,
			  int artefacts,
			  struct Player *attacker,
			  struct Player *defender,
			  struct Relation *attToDef)
{
  if(!STEALOUTSIDEWAR)
      return 0;
  db_result_t *result;

  /*
   * Wann ist es kein Farmen?
   * sie haben eine Beziehung || es gab ein artefakt
   * zu holen || verteidiger hat keinen stamm
   */
  if ( (attToDef->relationType == RELATION_TYPE_WAR)
       || (attToDef->relationType == RELATION_TYPE_PRE_WAR)
          || (defender->tribe == NULL)
	  || (strcmp(defender->tribe,"multi")==0)
	  || (strcmp(defender->tribe, attacker->tribe)==0)
	      || (strlen(defender->tribe) == 0)
          || (artefacts > 0)
          || (defender->player_id) == PLAYER_SYSTEM) {

      return NO_FARMING;
  }

  /* Sind es Missionierungsgegner? */
  result = db_query(database,
	"SELECT c.caveID FROM Cave_takeover c ,Cave_takeover k  "
	"WHERE c.caveID = k.caveID AND c.playerID = %d AND k.playerID = %d "
	"AND k.status > 0 AND c.status > 0",
	defender->player_id, attacker->player_id);

  return db_result_next_row(result) ? NO_FARMING : FARMING;
}

/*
 * Calculate an effect factor from the value of a database field
 */
static float effect_factor (float factor)
{
  return factor < 0 ? 1 / (1 - factor) : 1 + factor;
}

/*
 * Initalize an Army structure for an army of the given cave.
 * The unit, resource and defense_system vectors are copied directly
 * into the Army structure (note: only defense_system may be NULL).
 */
static void army_setup (Army *army, const struct Cave *cave,
			const float religion_bonus[],
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
  /* XXX should this be effect_factor(... + takeover_multiplier)? */
  army->effect_rangeattack_factor = effect_factor(cave->effect[14])
    + takeover_multiplier;
  army->effect_arealattack_factor = effect_factor(cave->effect[15])
    + takeover_multiplier;
  army->effect_attackrate_factor  = effect_factor(cave->effect[16])
    + takeover_multiplier;
  army->effect_defenserate_factor = effect_factor(cave->effect[17])
    + takeover_multiplier;
  army->effect_size_factor        = effect_factor(cave->effect[18])
    + takeover_multiplier;
  army->effect_ranged_damage_resistance_factor = effect_factor(cave->effect[19])
    + takeover_multiplier;
}

static void increaseFarming(db_t *database,
			    int player_id){
   if (player_id != 0){
    debug(DEBUG_BATTLE, "increaseFarming for %d", player_id);
    db_query(database, "UPDATE " DB_TABLE_PLAYER " SET fame = fame + 1 WHERE playerID = %d", player_id);
  }
 
}
static void bodycount_update(db_t *database,
                    int player_id,
                    int count){
  if (player_id != 0){
    debug(DEBUG_BATTLE, "bodycount: %d for %d", count, player_id);
    db_query(database, "UPDATE " DB_TABLE_PLAYER " SET body_count = body_count + %d WHERE playerID = %d", count, player_id);
  }
}

static int bodycount_calculate(const Battle *battle, int schalter){
  int count = 0;
  int i = 0;
  if(schalter != FLAG_ATTACKER){
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->attackers[0].units[i];
      count += unit->amount_before - unit->amount_after;
    }
  }else{
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->defenders[0].units[i];
      count += unit->amount_before - unit->amount_after;
    }
  }
  return count;
}

static int bodycount_va_calculate(const Battle *battle){
  int count = 0;
  int i = 0;
  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    const struct Army_unit *defense = &battle->defenders[0].defenseSystems[i];
    count += defense->amount_before - defense->amount_after;
  }
  return count;
}

static void war_points_update(db_t *database,
                    const char * attacker_tribe,
                    const char * defender_tribe,
                    int att_count,
                    int def_count){
    debug(DEBUG_BATTLE, "warpoints: %d for %s and %d for %s", att_count, attacker_tribe, def_count, defender_tribe);
    db_query(database, "UPDATE Relation SET fame = fame + %d WHERE tribe like '%s' AND tribe_target like '%s'", att_count, attacker_tribe, defender_tribe);
    db_query(database, "UPDATE Relation SET fame = fame + %d WHERE tribe like '%s' AND tribe_target like '%s'", def_count, defender_tribe, attacker_tribe);
}
static void war_points_update_verschieben(db_t *database,
                    const char * attacker_tribe,
                    const char * defender_tribe,
                    int count){
    debug(DEBUG_BATTLE, "warpoints: %d for %s against %s", count, attacker_tribe, defender_tribe);
    db_query(database, "UPDATE Relation SET fame = fame + %d WHERE tribe like '%s' AND tribe_target like '%s'", count, attacker_tribe, defender_tribe);
}


static int war_points_calculate(const Battle *battle, int schalter){
  int count = 0;
  int i = 0;
  if(schalter == FLAG_ATTACKER){
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->defenders[0].units[i];
      count += (unit->amount_before - unit->amount_after) * ((struct Unit *)unit_type[i])->warpoints;
    }
    for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
      const struct Army_unit *defense = &battle->defenders[0].defenseSystems[i];
      count += (defense->amount_before - defense->amount_after)
               *((struct DefenseSystem *)defense_system_type[i])->warpoints;
    }
  }else{
    for (i = 0; i < MAX_UNIT; ++i) {
      const struct Army_unit *unit = &battle->attackers[0].units[i];
      count += (unit->amount_before - unit->amount_after) * ((struct Unit *)unit_type[i])->warpoints;
    }
  }
  return count;
}
static int get_takeover_multiplier (const struct Cave *cave)
{
#ifdef TAKEOVER_MULTIPLIER_BUILDING
  return 1 + cave->building[TAKEOVER_MULTIPLIER_BUILDING];
#else
  return 1;
#endif
}

static void prepare_battle(db_t *database,
			   Battle          *battle,
			   struct Player   *attacker,
			   struct Player   *defender,
			   struct Cave     *cave_attacker,
			   struct Cave     *cave_defender,
			   const float     *battle_bonus,
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

  /* artefacts */
  debug(DEBUG_BATTLE, "artefacts in target cave: %d", cave_defender->artefacts);

  if (cave_defender->artefacts > 0) {
    db_result_t *result =
      db_query(database, "SELECT artefactID FROM " DB_TABLE_ARTEFACT
			 " WHERE caveID = %d LIMIT 0,1",
	       cave_defender->cave_id);

    if (!db_result_next_row(result))
      throw(SQL_EXCEPTION, "prepare_battle: no artefact in cave");

    /* warum nur das erste Artefakt?? */
    *defender_artefact_id = db_result_get_int_at(result, 0);
  }

  debug(DEBUG_BATTLE, "defender artefact: %d", *defender_artefact_id);
  debug(DEBUG_BATTLE, "attacker artefact: %d", *attacker_artefact_id);

  /* get the relation boni */
  battle->attackers[0].relationMultiplicator =
    relation_from_attacker->attackerMultiplicator;
  battle->defenders[0].relationMultiplicator =
    relation_from_defender->defenderMultiplicator;
}

static int artefact_lost (void)
{
  return drand() < ARTEFACT_LOST_CHANCE;
}

static int map_get_bounds (db_t *database,
			   int *minX, int *maxX, int *minY, int *maxY)
{
  db_result_t *result = db_query(database,
    "SELECT MIN(xCoord) AS minX, "
    "       MAX(xCoord) AS maxX, "
    "       MIN(yCoord) AS minY, "
    "       MAX(yCoord) AS maxY  "
    "FROM Cave");

  if (!db_result_next_row(result)) return 0;

  *minX = db_result_get_int(result, "minX");
  *maxX = db_result_get_int(result, "maxX");
  *minY = db_result_get_int(result, "minY");
  *maxY = db_result_get_int(result, "maxY");
  return 1;
}

static int artefact_loose_to_cave (db_t *database, struct Cave *cave)
{
  db_result_t *result;
  dstring_t *query;
  int x, y;
  int minX, minY, maxX, maxY, rangeX, rangeY;

  /* number between -ALR <= n <= ALR */
  x = (int) ((ARTEFACT_LOST_RANGE * 2 + 1) * drand()) - ARTEFACT_LOST_RANGE;
  y = (int) ((ARTEFACT_LOST_RANGE * 2 + 1) * drand()) - ARTEFACT_LOST_RANGE;

  x += cave->xpos;
  y += cave->ypos;   /* these numbers may be out of range */

  if (! map_get_bounds(database, &minX, &maxX, &minY, &maxY)) {
    return 0;
  }
  rangeX = maxX - minX +1;
  rangeY = maxY - minY +1;

  x = ( (x-minX+rangeX) % (rangeX) ) + minX;
  y = ( (y-minY+rangeY) % (rangeY) ) + minY;

  query = dstring_new("SELECT caveID FROM " DB_TABLE_CAVE
		      " WHERE xCoord = %d AND yCoord = %d", x, y);

  debug(DEBUG_SQL, "%s", dstring_str(query));

  result = db_query_dstring(database, query);

  return db_result_next_row(result) ? db_result_get_int(result, "caveID") : 0;
}

static void after_battle_change_artefact_ownership (
  db_t *database,
  int          winner,
  int          *artefact,
  int          *artefact_id,
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
	*artefact_id = *artefact_def;

	if (artefact_lost()) {
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
      *artefact_id = *artefact;
      *artefact = 0;
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;
  }
}

static void after_battle_defender_update(db_t *database,
					 int             player_id,
					 const Battle    *battle,
					 int             cave_id,
					 struct Relation *relation)
{
  dstring_t *ds;
  int       update = 0;
  int       i;

  /* construct defender update */
  ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

  /* which units need an update */
  debug(DEBUG_BATTLE, "preparing units update");
  for (i = 0; i < MAX_UNIT; ++i) {
    const struct Army_unit *unit = &battle->defenders[0].units[i];

    if (unit->amount_before != unit->amount_after) {
      dstring_append(ds, "%s%s = %d", update ? "," : "",
		     unit_type[i]->dbFieldName, unit->amount_after);
      update = 1;
    }
  }

  /* which defense systems need an update */
  debug(DEBUG_BATTLE, "preparing defensesystems update");
  for (i = 0; i < MAX_DEFENSESYSTEM; ++i) {
    const struct Army_unit *defense_system =
      &battle->defenders[0].defenseSystems[i];

//    if ((relation->relationType == RELATION_TYPE_WAR) || (((struct DefenseSystem *)defense_system_type[i])->warpoints == 0 )) {
      if (defense_system->amount_before != defense_system->amount_after) {
        dstring_append(ds, "%s%s = %d", update ? "," : "",
  		     defense_system_type[i]->dbFieldName,
  		     defense_system->amount_after);
      update = 1;
      }
//   }  
  }

  /* which resources need an update */
  debug(DEBUG_BATTLE, "preparing resources update");
  for (i = 0; i < MAX_RESOURCE; ++i)
    if (battle->defenders[0].resourcesBefore[i] !=
      battle->defenders[0].resourcesAfter[i]) {
      dstring_append(ds, "%s%s = LEAST(%d, %s)", update ? "," : "",
		     resource_type[i]->dbFieldName,
		     battle->defenders[0].resourcesAfter[i],
		     function_to_sql(resource_type[i]->maxLevel));
      update = 1;
    }
  dstring_append(ds, " WHERE caveID = %d", cave_id);

  if (update) {
    debug(DEBUG_SQL, "%s", dstring_str(ds));
    db_query_dstring(database, ds);
  }

}

static void takeover_cave(db_t *database,
		   int    cave_id,
		   int    attacker_id,
		   const char   *return_start)
{
  /* change owner of cave */
  db_query(database, "UPDATE " DB_TABLE_CAVE " SET playerID = %d"
		     " WHERE caveID = %d", attacker_id, cave_id);

  dstring_t *ds;
  ds = dstring_new("UPDATE Event_movement SET target_caveID = source_caveID, ");
  dstring_append(ds, "end = addtime('%s',timediff('%s',start)), ",return_start,return_start);
  dstring_append(ds, "start='%s', ",return_start);
  dstring_append(ds, "movementID = 5 where caveID = %d and caveID = source_caveID",cave_id);
  debug(DEBUG_SQL, "Torben %s", dstring_str(ds));
  db_query_dstring(database, ds);


  /* delete research from event table*/
  db_query(database, "DELETE FROM Event_science WHERE caveID = %d", cave_id);

  /* copy sciences from new owner to cave */
  science_update_caves(database, attacker_id);
}

static void after_battle_attacker_update (
  db_t *database,
  int          player_id,
  const Battle *battle,
  int          source_caveID,
  int          target_caveID,
  const char   *speed_factor,
  const char   *return_start,
  const char   *return_end,
  int          artefact,
  struct Relation *relation
  )
{
  int update = 0;
  int i;

  /* construct attacker update */
  for (i = 0; i < MAX_UNIT; ++i)
    if (battle->attackers[0].units[i].amount_after > 0) {
      update = 1;
      break;
    }

  if (update) {
    dstring_t *ds;

    /* send remaining units back */
    ds = dstring_new("INSERT INTO Event_movement"
		     " (caveID, target_caveID, source_caveID, movementID,"
		     " speedFactor, start, end, artefactID");

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, ",%s", resource_type[i]->dbFieldName);
    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

    dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d",
		   source_caveID, source_caveID, target_caveID, RUECKKEHR,
		   speed_factor, return_start, return_end, artefact);

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, ",%d", battle->attackers[0].resourcesAfter[i]);
    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%d", battle->attackers[0].units[i].amount_after);

    dstring_append(ds, ")");

    debug(DEBUG_SQL, "%s", dstring_str(ds));
    db_query_dstring(database, ds);
  }
}

static void after_takeover_attacker_update(db_t *database,
					   int             player_id,
					   const Battle    *battle,
					   int             target_caveID,
					   int             artefact,
					   struct Relation *relation)
{
  int update = 0;
  int i;

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
    ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

    for (i = 0; i < MAX_RESOURCE; ++i)
      dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		     resource_type[i]->dbFieldName,
		     resource_type[i]->dbFieldName,
		     battle->attackers[0].resourcesAfter[i],
		     function_to_sql(resource_type[i]->maxLevel));

    for (i = 0; i < MAX_UNIT; ++i)
      dstring_append(ds, ",%s = %s + %d",
		     unit_type[i]->dbFieldName,
		     unit_type[i]->dbFieldName,
		     battle->attackers[0].units[i].amount_after);

    dstring_append(ds, " WHERE caveID = %d", target_caveID);

    debug(DEBUG_SQL, "%s", dstring_str(ds));
    db_query_dstring(database, ds);
  }
}


/*
 * This function is responsible for all the movement.
 *
 * @params database  the function needs this link to the DB
 * @params result    current movement event (from DB)
 */
void movement_handler (db_t *database, db_result_t *result)
{
  int movementID;
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
  int isFarming = 0;
  
  Battle *battle;
  dstring_t *ds;
  double spy_result;

  /* time related issues */
  const float *battle_bonus;

  /* artefacts */
  int artefact = 0;
  int artefact_def = 0;
  int artefact_id = 0;
  int lostTo = 0;

  int body_count = 0;
  int attacker_lose = 0;
  int defender_lose = 0;
  int defender_va_lose = 0;

  int war_points_attacker = 0;
  int war_points_defender = 0;
  int war_points_sender = 0;
  int war_points_show = 0;

	int  takeover = 0;
  debug(DEBUG_TICKER, "entering function movement_handler()");

  /* get movement id and target/source cave id */
  movementID    = db_result_get_int(result, "movementID");
  target_caveID = db_result_get_int(result, "target_caveID");
  source_caveID = db_result_get_int(result, "source_caveID");
  speed_factor  = db_result_get_string(result, "speedFactor");

  /* get event_start and event_end */
  event_start  = db_result_get_gmtime(result, "start");
  return_start = db_result_get_string(result, "end");
  event_end    = make_time_gm(return_start);
  make_timestamp_gm(return_end, event_end + (event_end - event_start));

  /* get resources, units and artefact id */
  get_resource_list(result, resources);
  get_unit_list(result, units);
  artefact = db_result_get_int(result, "artefactID");

  /* TODO reduce number of queries */
  get_cave_info(database, source_caveID, &cave1);
  get_cave_info(database, target_caveID, &cave2);

  if (cave1.player_id)
    get_player_info(database, cave1.player_id, &player1);
  else{	/* System */
    memset(&player1, 0, sizeof player1);
    player1.tribe = "";
  }
  if (cave2.player_id == cave1.player_id)
    player2 = player1;
  else if (cave2.player_id)
    get_player_info(database, cave2.player_id, &player2);
  else{	/* System */
    memset(&player2, 0, sizeof player2);
    player2.tribe = "";
  }
  debug(DEBUG_TICKER, "caveID = %d, movementID = %d",
	target_caveID, movementID);

  /**********************************************************************/
  /*** THE INFAMOUS GIANT SWITCH ****************************************/
  /**********************************************************************/

  switch (movementID) {

    /**********************************************************************/
    /*** ROHSTOFFE BRINGEN ************************************************/
    /**********************************************************************/
    case ROHSTOFFE_BRINGEN:

      /* record in takeover table */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE_TAKEOVER " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
	dstring_append(ds, "%s%s = %s + %d", i > 0 ? "," : "",
		resource_type[i]->dbFieldName,
		resource_type[i]->dbFieldName, resources[i]);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
		     target_caveID, cave1.player_id);

      db_query_dstring(database, ds);
			if(db_affected_rows(database)!=0){
							takeover=1;
			}
	/* put resources into cave */
	dstring_set(ds, "UPDATE " DB_TABLE_CAVE " SET ");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		  resource_type[i]->dbFieldName,
		  resource_type[i]->dbFieldName, 
			(takeover==1)?resources[i] * TAKEOVER_RESOURCE_SAVE_PERCENTAGE / 100:resources[i],
		  function_to_sql(resource_type[i]->maxLevel));

	dstring_append(ds, " WHERE caveID = %d", target_caveID);

	db_query_dstring(database, ds);

      if (artefact > 0)
	put_artefact_into_cave(database, artefact, target_caveID);

      /* send all units back */
      dstring_set(ds, "INSERT INTO Event_movement"
		      " (caveID, target_caveID, source_caveID, movementID,"
		      " speedFactor, start, end");

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

      dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s'",
	      source_caveID, source_caveID, target_caveID, RUECKKEHR,
	      speed_factor, return_start, return_end);

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%d", units[i]);

      dstring_append(ds, ")");

      db_query_dstring(database, ds);

      /* generate trade report and receipt for sender */
      trade_report(database, &cave1, &player1, &cave2, &player2,
		   resources, NULL, artefact);
      break;

    /**********************************************************************/
    /*** EINHEITEN/ROHSTOFFE VERSCHIEBEN **********************************/
    /**********************************************************************/
    case VERSCHIEBEN:
      get_relation_info(database, player1.tribe, player2.tribe, &relation1);
      /*�berpr�fen ob sender und versender eine kriegsbeziehung haben */
      if(!(isVerschiebenAllowed(database, &player1, &player2, &relation1) ||
	      isTakeoverableCave(database, target_caveID))){
        //bewegung umdrehen//
	    /* send remaining units back */
	    ds = dstring_new("INSERT INTO Event_movement"
		  " (caveID, target_caveID, source_caveID, movementID,"
		  " speedFactor, start, end, artefactID");

	    for (i = 0; i < MAX_RESOURCE; ++i)
	      dstring_append(ds, ",%s", resource_type[i]->dbFieldName);
	    for (i = 0; i < MAX_UNIT; ++i)
	      dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

	    dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d",
		  source_caveID, source_caveID, target_caveID, RUECKKEHR,
		  speed_factor, return_start, return_end, artefact);

	    for (i = 0; i < MAX_RESOURCE; ++i)
	      dstring_append(ds, ",%d", resources[i]);
	    for (i = 0; i < MAX_UNIT; ++i)
	      dstring_append(ds, ",%d", units[i]);

	    dstring_append(ds, ")");

	    db_query_dstring(database, ds);
	    break;
      }
      /* record in takeover table */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE_TAKEOVER " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
	    dstring_append(ds, "%s%s = %s + %d", i > 0 ? "," : "",
		  resource_type[i]->dbFieldName,
		  resource_type[i]->dbFieldName, resources[i]);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
	      target_caveID, cave1.player_id);

      db_query_dstring(database, ds);
      if(db_affected_rows(database)!=0){
              takeover=1;
      }

      /* put resources and units into cave */
      dstring_set(ds, "UPDATE " DB_TABLE_CAVE " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
        dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
                       resource_type[i]->dbFieldName,
                       resource_type[i]->dbFieldName, 
                      (takeover==1)?resources[i] * TAKEOVER_RESOURCE_SAVE_PERCENTAGE / 100:resources[i],
                      function_to_sql(resource_type[i]->maxLevel));
      for (i = 0; i < MAX_UNIT; ++i){
        war_points_sender += ((struct Unit *)unit_type[i])->warpoints * units[i];
        dstring_append(ds, ",%s = %s + %d",
	               unit_type[i]->dbFieldName,
	               unit_type[i]->dbFieldName, units[i]);
        }
        if(relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR){
          war_points_update_verschieben(database, player1.tribe, player2.tribe, -1* war_points_sender);
        }
        dstring_append(ds, " WHERE caveID = %d", target_caveID);

	    db_query_dstring(database, ds);

      if (artefact > 0)
	put_artefact_into_cave(database, artefact, target_caveID);

      /* generate trade report and receipt for sender */
      trade_report(database, &cave1, &player1, &cave2, &player2,
		   resources, units, artefact);
      break;

    /**********************************************************************/
    /*** RUECKKEHR ********************************************************/
    /**********************************************************************/
    case RUECKKEHR:

      /* put resources into cave */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

      for (i = 0; i < MAX_RESOURCE; ++i)
	dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		resource_type[i]->dbFieldName,
		resource_type[i]->dbFieldName, resources[i],
		function_to_sql(resource_type[i]->maxLevel));

      for (i = 0; i < MAX_UNIT; ++i)
	dstring_append(ds, ",%s = %s + %d",
		 unit_type[i]->dbFieldName,
		 unit_type[i]->dbFieldName, units[i]);

      dstring_append(ds, " WHERE caveID = %d", target_caveID);

      db_query_dstring(database, ds);

      if (artefact > 0)
	put_artefact_into_cave(database, artefact, target_caveID);

      /* generate return report */
      return_report(database, &cave1, &player1, &cave2, &player2,
		    resources, units, artefact);
      break;

    /**********************************************************************/
    /*** ANGREIFEN ********************************************************/
    /**********************************************************************/
    case ANGREIFEN:

      /* beginner protection active in target cave? */
      if (cave_is_protected(&cave2))
      {
	   debug(DEBUG_BATTLE, "Is protected Cave");
	/* send remaining units back */
	ds = dstring_new("INSERT INTO Event_movement"
			 " (caveID, target_caveID, source_caveID, movementID,"
			 " speedFactor, start, end, artefactID");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%s", resource_type[i]->dbFieldName);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

	dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d",
		       source_caveID, source_caveID, target_caveID, RUECKKEHR,
		       speed_factor, return_start, return_end, artefact);

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%d", resources[i]);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%d", units[i]);

	dstring_append(ds, ")");

	db_query_dstring(database, ds);
     debug(DEBUG_BATTLE,"End Handle Protected Cave attack");
	/* create and send reports */
	protected_report(database, &cave1, &player1, &cave2, &player2);
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

      /* calculate the fame */
      /* Calculatin is diferent if the battle was just pure farming*/
      isFarming = check_farming(database, cave2.artefacts, &player1,
			      &player2, &relation1);
      if( relation1.relationType == RELATION_TYPE_WAR){
	battle->isWar = 1;
      }

      /* calculate battle result */
      calcBattleResult(battle, &cave2, 0);

      /* change artefact ownership */
      debug(DEBUG_BATTLE, "entering change artefact");
      after_battle_change_artefact_ownership(
	database, battle->winner, &artefact, &artefact_id, &artefact_def,
	target_caveID, &cave2, &lostTo);

      /* attackers artefact (if any) is stored in variable artefact,
	 artefact_id is id of the artefact that changed owner (or 0) */

      /* no relation -> attacker get negative fame*/
      debug(DEBUG_BATTLE, "Relation Type %d",relation1.relationType);

      /* construct attacker update */
      debug(DEBUG_BATTLE, "entering attacker update");
      after_battle_attacker_update(database, player1.player_id, battle,
				   source_caveID, target_caveID, speed_factor,
				   return_start, return_end, artefact,
				   &relation1);

      /* defender update: exception still uncaught (better leave) */
      debug(DEBUG_BATTLE, "entering defender update");
      after_battle_defender_update(database, player2.player_id,
				   battle, target_caveID, &relation2);

 
      /* Farming update */
      if(isFarming){
        increaseFarming(database, player1.player_id);
      }

      /* reset DB_TABLE_CAVE_TAKEOVER */
      ds = dstring_new("UPDATE " DB_TABLE_CAVE_TAKEOVER " SET status = 0");

      for (i = 0; i < MAX_RESOURCE; ++i)
	dstring_append(ds, ",%s = 0", resource_type[i]->dbFieldName);

      dstring_append(ds, " WHERE caveID = %d AND playerID = %d",
		  target_caveID, cave1.player_id);

      db_query_dstring(database, ds);

      /* cave takeover by battle */
      if (battle->winner == FLAG_ATTACKER &&
	  ((struct Terrain *)terrain_type[cave2.terrain])->takeoverByCombat) {
	db_query(database, "UPDATE " DB_TABLE_CAVE " SET playerID = %d"
			   " WHERE caveID = %d",
		 cave1.player_id, target_caveID);

	db_query(database, "DELETE FROM Event_science WHERE caveID = %d",
		 target_caveID);

	science_update_caves(database, cave1.player_id);
      }
     //bodycount calculate
      attacker_lose = bodycount_calculate(battle, FLAG_DEFENDER);
      defender_lose = bodycount_calculate(battle, FLAG_ATTACKER);
      defender_va_lose = bodycount_va_calculate(battle);

      bodycount_update( database, player1.player_id, defender_lose);
      bodycount_update( database, player2.player_id, attacker_lose);
      if(relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR){
	war_points_show = 1;
        war_points_attacker = (defender_lose>10||defender_va_lose>5?war_points_calculate(battle,FLAG_ATTACKER):0);
        war_points_defender = (attacker_lose>10?war_points_calculate(battle,FLAG_DEFENDER):0);

        war_points_update(database, player1.tribe, player2.tribe, war_points_attacker, war_points_defender);
      }

      /* create and send reports */
      battle_report(database, &cave1, &player1, &cave2, &player2, battle,
		    artefact_id, lostTo, 0, 0, &relation1, &relation2,
		    war_points_show, war_points_attacker, war_points_defender);
      break;

    /**********************************************************************/
    /*** Spionieren *******************************************************/
    /**********************************************************************/
    case SPIONAGE:

      /* generate spy report */
      spy_result = spy_report(database, &cave1, &player1, &cave2, &player2,
			      resources, units, artefact);

      if (spy_result == 1)
      {
	/* send all units back */
	ds = dstring_new("INSERT INTO Event_movement"
			 " (caveID, target_caveID, source_caveID, movementID,"
			 " speedFactor, start, end, artefactID");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%s", resource_type[i]->dbFieldName);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

	dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d",
		source_caveID, source_caveID, target_caveID, RUECKKEHR,
		speed_factor, return_start, return_end, artefact);

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%d", resources[i]);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%d", units[i]);

	dstring_append(ds, ")");

	db_query_dstring(database, ds);
      }
      else
      {
	/* send remaining units back */
	int count = 0;

	ds = dstring_new("INSERT INTO Event_movement"
			 " (caveID, target_caveID, source_caveID, movementID,"
			 " speedFactor, start, end, artefactID");

	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

	dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d",
		source_caveID, source_caveID, target_caveID, RUECKKEHR,
		speed_factor, return_start, return_end, artefact);

	for (i = 0; i < MAX_UNIT; ++i)
	{
	  int num = units[i] * spy_result;

	  dstring_append(ds, ",%d", num);
	  count += num;
      body_count += units[i] - num;
	}

	dstring_append(ds, ")");

	if (count)
	  db_query_dstring(database, ds);

	/* put resources into cave */
	ds = dstring_new("UPDATE " DB_TABLE_CAVE " SET ");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, "%s%s = LEAST(%s + %d, %s)", i > 0 ? "," : "",
		  resource_type[i]->dbFieldName,
		  resource_type[i]->dbFieldName, resources[i],
		  function_to_sql(resource_type[i]->maxLevel));

	dstring_append(ds, " WHERE caveID = %d", target_caveID);

	db_query_dstring(database, ds);

	if (artefact > 0)
	  put_artefact_into_cave(database, artefact, target_caveID);
      }
      bodycount_update(database, player2.player_id, body_count);
      break;

    /**********************************************************************/
    /*** UEBERNEHMEN ******************************************************/
    /**********************************************************************/
    case TAKEOVER:

      /* secure or protected target gave? */
      if (cave2.secure || cave_is_protected(&cave2))
      {
	/* send remaining units back */
	ds = dstring_new("INSERT INTO Event_movement"
			 " (caveID, target_caveID, source_caveID, movementID,"
			 " speedFactor, start, end, artefactID");

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%s", resource_type[i]->dbFieldName);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%s", unit_type[i]->dbFieldName);

	dstring_append(ds, ") VALUES (%d, %d, %d, %d, %s, '%s', '%s', %d",
		       source_caveID, source_caveID, target_caveID, RUECKKEHR,
		       speed_factor, return_start, return_end, artefact);

	for (i = 0; i < MAX_RESOURCE; ++i)
	  dstring_append(ds, ",%d", resources[i]);
	for (i = 0; i < MAX_UNIT; ++i)
	  dstring_append(ds, ",%d", units[i]);

	dstring_append(ds, ")");

	db_query_dstring(database, ds);

	/* create and send reports */
	/* FIXME use different message in report (protected -> secure) */
	protected_report(database, &cave1, &player1, &cave2, &player2);
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
      /*bei ner �bernahme kein resi klau m�glich*/
      calcBattleResult(battle, &cave2, 1);

      /* change artefact ownership */
      after_battle_change_artefact_ownership(
        database, battle->winner, &artefact, &artefact_id, &artefact_def,
	    target_caveID, &cave2, &lostTo);

      /* attackers artefact (if any) is stored in variable artefact,
	     artefact_id is id of the artefact that changed owner (or 0) */

      /* defender update: exception still uncaught (better leave) */
      after_battle_defender_update(database, player2.player_id,
				   battle, target_caveID, &relation2);


     int war1 = get_tribe_at_war(database,player1.tribe);
     int war2 = get_tribe_at_war(database,player2.tribe);

      /* attacker won:  put survivors into cave, change owner
       * attacker lost: send back survivors */
      change_owner =
          battle->winner == FLAG_ATTACKER && cave2.player_id != PLAYER_SYSTEM &&
          player1.max_caves > get_number_of_caves(database, player1.player_id) &&
          ((relation1.relationType == RELATION_TYPE_WAR &&
 	    relation2.relationType == RELATION_TYPE_WAR) ||
	    (!war1 && !war2) ||
           (strcasecmp(player1.tribe, player2.tribe) == 0)); // Spieler im selben stamm
     //bodycount calculate
      attacker_lose = bodycount_calculate(battle, FLAG_DEFENDER);
      defender_lose = bodycount_calculate(battle, FLAG_ATTACKER);
      defender_va_lose = bodycount_va_calculate(battle);

      bodycount_update( database, player1.player_id, defender_lose);
      bodycount_update( database, player2.player_id, attacker_lose);
      if(relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR){
        war_points_show = 1;
        war_points_attacker = (defender_lose>10||defender_va_lose>5?war_points_calculate(battle,FLAG_ATTACKER):0);
        war_points_defender = (attacker_lose>10?war_points_calculate(battle,FLAG_DEFENDER):0);

      }

      if (change_owner){
        debug(DEBUG_TAKEOVER, "change owner of cave %d to new owner %d",
	          target_caveID, cave1.player_id);
        takeover_cave(database, target_caveID, cave1.player_id,return_start);
        after_takeover_attacker_update(database, player1.player_id,
		                               battle, target_caveID,
                                       artefact, &relation1);
        if(relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR){
          war_points_attacker += WAR_POINTS_FOR_TAKEOVER;
        }
      } else { /* send survivors back */
        debug(DEBUG_TAKEOVER, "send back attacker's suvivors");
        after_battle_attacker_update(database, player1.player_id, battle,
				     source_caveID, target_caveID, speed_factor,
				     return_start, return_end, artefact,
				     &relation1);
      }
      if(relation1.relationType == RELATION_TYPE_PRE_WAR || relation1.relationType == RELATION_TYPE_WAR){
        war_points_update(database, player1.tribe, player2.tribe, war_points_attacker, war_points_defender);
      }

      /* create and send reports */
      battle_report(database, &cave1, &player1, &cave2, &player2, battle,
		    artefact_id, lostTo, change_owner, 1 + takeover_multiplier,
		    &relation1, &relation2,war_points_show, war_points_attacker, war_points_defender);
     //bodycount calculate


      bodycount_update( database, player1.player_id, defender_lose);
      bodycount_update( database, player2.player_id, attacker_lose);
      break;

    default:
      throw(BAD_ARGUMENT_EXCEPTION, "movement_handler: unknown movementID");
  }

  /**********************************************************************/
  /*** END OF THE INFAMOUS GIANT SWITCH *********************************/
  /**********************************************************************/

  debug(DEBUG_TICKER, "leaving function movement_handler()");
}
