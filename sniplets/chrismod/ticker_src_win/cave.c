/*
 * cave.c - cave and player information
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>
#include <string.h>

#include "cave.h"
#include "except.h"
#include "logging.h"
#include "memory.h"
#include "mysql_tools.h"
#include "ticker_defs.h"
#include "ugatime.h"

/*
 * Retrieve the unit and/or resource list from a row of the result set.
 * If unit or resource is NULL, the corresponding values are not stored.
 */
void get_unit_list (MYSQL_RES *result, MYSQL_ROW row,
		    int unit[], int resource[])
{
  int type;

  memset(unit, 0, MAX_UNIT * sizeof (int));
  memset(resource, 0, MAX_RESOURCE * sizeof (int));

  if (unit)
    for (type = 0; type < MAX_UNIT; ++type)
      try {
	unit[type] =
	  mysql_get_int_field(result, row, unitTypeList[type].dbFieldName);
      } catch (SQL_EXCEPTION) {
	warning("%s", except_msg);
      } end_try;

  if (resource)
    for (type = 0; type < MAX_RESOURCE; ++type)
      try {
	resource[type] =
	  mysql_get_int_field(result, row, resourceTypeList[type].dbFieldName);
      } catch (SQL_EXCEPTION) {
	warning("%s", except_msg);
      } end_try;
}

/*
 * Retrieve cave table information for the given cave id.
 */
void get_cave_info (MYSQL *database, int cave_id, struct Cave *cave)
{
  MYSQL_RES *result = mysql_query_fmt(database,
	"SELECT * FROM " DB_MAIN_TABLE_CAVE " WHERE caveID = %d", cave_id);
  MYSQL_ROW row = mysql_fetch_row(result);
  int type;

  if (!row) throwf(SQL_EXCEPTION, "cave %d not found", cave_id);

  memset(cave, 0, sizeof *cave);
  cave->cave_id = cave_id;
  cave->xpos = mysql_get_int_field(result, row, "xCoord");
  cave->ypos = mysql_get_int_field(result, row, "yCoord");
  cave->name = mysql_get_string_field(result, row, "name");
  cave->player_id = mysql_get_int_field(result, row, "playerID");
  cave->terrain = mysql_get_int_field(result, row, "terrain");
  cave->takeoverable = mysql_get_int_field(result, row, "takeoverable");
  cave->artefacts = mysql_get_int_field(result, row, "artefacts");
// DELETED by chris---: damn monster
//  cave->monster_id = mysql_get_int_field(result, row, "monsterID");
  cave->secure = mysql_get_int_field(result, row, "secureCave");
  cave->protect_end = mysql_get_time_field(result, row, "protection_end");

  get_unit_list(result, row, cave->unit, cave->resource);

  for (type = 0; type < MAX_BUILDING; ++type)
    try {
      cave->building[type] =
	mysql_get_int_field(result, row, buildingTypeList[type].dbFieldName);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;

  for (type = 0; type < MAX_SCIENCE; ++type)
    try {
      cave->science[type] =
	mysql_get_int_field(result, row, scienceTypeList[type].dbFieldName);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;

  for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
    try {
      cave->defense_system[type] =
	mysql_get_int_field(result, row,
			    defenseSystemTypeList[type].dbFieldName);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;

  for (type = 0; type < MAX_EFFECT; ++type)
    try {
      cave->effect[type] =
	mysql_get_double_field(result, row, effectTypeList[type].dbFieldName);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;
}

/*
 * Retrieve the owner (player id) of the given gave.
 */
int get_cave_owner (MYSQL *database, int cave_id)
{
  MYSQL_RES *result = mysql_query_fmt(database,
	"SELECT playerID FROM " DB_MAIN_TABLE_CAVE
	" WHERE caveID = %d", cave_id);
  MYSQL_ROW row = mysql_fetch_row(result);

  if (!row) throw(SQL_EXCEPTION, "no player id found");

  return atoi(row[0]);
}

/*
 * Retrieve player table information for the given player id.
 */
void get_player_info (MYSQL *database, int player_id, struct Player *player)
{
  MYSQL_RES *result = mysql_query_fmt(database,
	"SELECT * FROM " DB_MAIN_TABLE_PLAYER
	" WHERE playerID = %d", player_id);
  MYSQL_ROW row = mysql_fetch_row(result);
  int type;

  if (!row) throwf(SQL_EXCEPTION, "player %d not found", player_id);

  memset(player, 0, sizeof *player);
  player->player_id = player_id;
  player->name = mysql_get_string_field(result, row, "name");
  player->tribe = mysql_get_string_field(result, row, "tribe");
  player->max_caves = mysql_get_int_field(result, row, "takeover_max_caves");

  for (type = 0; type < MAX_SCIENCE; ++type)
    try {
      player->science[type] =
	mysql_get_int_field(result, row, scienceTypeList[type].dbFieldName);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;
}

/*
 * Retrieve relation table information for the given tribe and tribe_target.
 */
int get_relation_info (MYSQL *database, const char* tribe,
			const char* tribe_target, struct Relation *relation)
{

  MYSQL_RES *result = NULL;
  MYSQL_ROW row;

  debug(DEBUG_BATTLE, "Start getting relation");

  if (tribe && tribe_target) {
    debug(DEBUG_BATTLE, "get relation for tribes %s %s", tribe, tribe_target);
    result = mysql_query_fmt(database,
	"SELECT * FROM " DB_MAIN_TABLE_RELATION
	" WHERE tribe = '%s' AND tribe_target = '%s'", tribe, tribe_target);

    row = mysql_fetch_row(result);
    debug(DEBUG_BATTLE, "fetched relation");
  }
  memset(relation, 0, sizeof *relation);

  if (!result || !row) {
    debug(DEBUG_BATTLE, "filling dummy relation");
    relation->relation_id = 0;
    relation->tribe = tribe;
    relation->tribe_target = tribe_target;
    relation->relationType = RELATION_TYPE_NONE;

    // FIXME: these values should be read from relation types
    relation->defenderMultiplicator = 1.0;
    relation->attackerMultiplicator = 1.0;
    relation->defenderReceivesFame = 0;
    relation->attackerReceivesFame = 0;

    return 0; // no relation entry
  }

  relation->relation_id = mysql_get_int_field(result, row, "relationID");
  relation->tribe = tribe;
  relation->tribe_target = tribe_target;
  relation->relationType = mysql_get_int_field(result, row, "relationType");
  relation->defenderMultiplicator =
    mysql_get_double_field(result, row, "defenderMultiplicator");
  relation->attackerMultiplicator =
    mysql_get_double_field(result, row, "attackerMultiplicator");
  relation->defenderReceivesFame =
    mysql_get_int_field(result, row, "defenderReceivesFame");
  relation->attackerReceivesFame =
    mysql_get_int_field(result, row, "attackerReceivesFame");

  return 1;
}

/*
 * Retrieve the number of caves owned by player_id.
 */
int get_number_of_caves (MYSQL *database, int player_id)
{
  MYSQL_RES *result = mysql_query_fmt(database,
	"SELECT COUNT(caveID) AS n FROM " DB_MAIN_TABLE_CAVE
	" WHERE playerID = %d", player_id);
  MYSQL_ROW row = mysql_fetch_row(result);

  if (!row) throwf(SQL_EXCEPTION, "no caves for player %d", player_id);

  return atoi(row[0]);
}

/*
 * Get the religion of the cave's owner (or former owner if abandoned).
 */
int get_religion (const struct Cave *cave)
{
  return cave->science[ID_SCIENCE_AGGA] > 0 ? RELIGION_AGGA :
	 cave->science[ID_SCIENCE_UGA]  > 0 ? RELIGION_UGA  : RELIGION_NONE;
}

/*
 * Return whether the given cave is protected or not.
 */
int cave_is_protected (const struct Cave *cave)
{
  return cave->protect_end > time(NULL);
}

/*
 * Retrieve monster table information for the given monster id.
 */
void get_monster_info (MYSQL *database, int monster_id, struct Monster *monster)
{
// DELETED by chris---: damn monster
/*
  MYSQL_RES *result = mysql_query_fmt(database,
	"SELECT * FROM Monster WHERE monsterID = %d", monster_id);
  MYSQL_ROW row = mysql_fetch_row(result);
  int type;

  if (!row) throwf(SQL_EXCEPTION, "monster %d not found", monster_id);

  monster->monster_id = monster_id;
  monster->name = mysql_get_string_field(result, row, "name");
  monster->attack = mysql_get_int_field(result, row, "angriff");
  monster->defense = mysql_get_int_field(result, row, "verteidigung");
  monster->mental = mysql_get_int_field(result, row, "mental");
  monster->strength = mysql_get_int_field(result, row, "koerperkraft");
  monster->exp_value = mysql_get_int_field(result, row, "erfahrung");
  monster->attributes = mysql_get_string_field(result, row, "eigenschaft");
*/
}
