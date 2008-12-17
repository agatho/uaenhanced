/*
 * science_handler.c - process science events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "cave.h"
#include "event_handler.h"
#include "except.h"
#include "logging.h"
#include "memory.h"
#include "mysql_tools.h"
#include "ticker_defs.h"

/*
 * ADDED by chris--- for science changing stuff
 * This function changes special building to the new
 * building of this science
 */
void science_change_stuff (MYSQL *database, int player_id, int science_id)
{
  struct Player player;
  dstring_t *ds;
  
  /* get the player data */
  get_player_info(database, player_id, &player);

  // science faith, oh we dont need to process something for faith
//  if (science_id == 15 && player.science[15] == 1) {
    // ok 1st time, we need to change something
//  }
  
  // science darkness
  if (science_id == 16 && player.science[16] == 1) {
    // ok 1st time, we need to change something
    ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");
    dstring_append(ds, "building_fightingplace = building_trainingcenter, ");
    dstring_append(ds, "building_slave = building_worker, ");
    dstring_append(ds, "building_waldschrat = building_wood_shack, ");
    dstring_append(ds, "building_mining = building_melting, ");
    dstring_append(ds, "extern_orctower = extern_tower");
    dstring_append(ds, " WHERE playerID = %d", player_id);

    mysql_query_dstring(database, ds);
    
    ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");
    dstring_append(ds, "building_trainingcenter = 0, ");
    dstring_append(ds, "building_worker = 0, ");
    dstring_append(ds, "building_wood_shack = 0, ");
    dstring_append(ds, "building_melting = 0, ");
    dstring_append(ds, "extern_tower = 0");
    dstring_append(ds, " WHERE playerID = %d", player_id);

    mysql_query_dstring(database, ds);    
  }
  
  // science hex
  if (science_id == 17 && player.science[17] == 1) {
    // ok 1st time, we need to change something
    ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");
    dstring_append(ds, "building_waldlaeufer = building_wood_shack, ");
    dstring_append(ds, "building_metal = building_melting");
    dstring_append(ds, " WHERE playerID = %d", player_id);

    mysql_query_dstring(database, ds);
    
    ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");
    dstring_append(ds, "building_wood_shack = 0, ");
    dstring_append(ds, "building_melting = 0");
    dstring_append(ds, " WHERE playerID = %d", player_id);

    mysql_query_dstring(database, ds);  
  }


}


/*
 * Copy all sciences from the player table to all caves of a player.
 */
void science_update_caves (MYSQL *database, int player_id)
{
    struct Player player;
    dstring_t *ds;
    int type;

    /* get the player data */
    get_player_info(database, player_id, &player);

    ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

    for (type = 0; type < MAX_SCIENCE; ++type)
	dstring_append(ds, "%s%s = %d", type > 0 ? "," : "",
		scienceTypeList[type].dbFieldName, player.science[type]);

    dstring_append(ds, " WHERE playerID = %d", player_id);

    mysql_query_dstring(database, ds);
}

/*
 * This function is called to update a science entry in the database.
 * The science gets improved one level, result contains information
 * about which player the science belongs to and the science type.
 */
void science_handler (MYSQL *database, MYSQL_RES *result)
{
    MYSQL_ROW row;
    int scienceID;
    int playerID;

    debug(DEBUG_TICKER, "entering function science_handler()");

    row = mysql_fetch_row(result);
    if (!row) throw(SQL_EXCEPTION, "science_handler: no science event");

    /* get science and player id */
    scienceID = mysql_get_int_field(result, row, "scienceID");
    playerID  = mysql_get_int_field(result, row, "playerID");

    mysql_query_fmt(database, "UPDATE " DB_MAIN_TABLE_PLAYER " SET %s = %s + 1"
			      " WHERE playerID = %d",
		    scienceTypeList[scienceID].dbFieldName,
		    scienceTypeList[scienceID].dbFieldName,
		    playerID);

    science_update_caves(database, playerID);
    
    // ADDED by chris--- for science changing
    if (scienceID == 16 || scienceID == 17) science_change_stuff(database, playerID, scienceID);

    debug(DEBUG_TICKER, "leaving function science_handler()");
}
