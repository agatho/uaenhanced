/*
 * unit_handler.c - process unit events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "event_handler.h"
#include "except.h"
#include "game_rules.h"
#include "logging.h"
#include "mysql_tools.h"
#include "ticker_defs.h"

/*
 * This function is called to update a unit's entry in the database.
 * The unit number is increased by quantity, result contains information
 * about which cave the units belong to and the unit's type and quantity.
 */
void unit_handler (MYSQL *database, MYSQL_RES *result)
{
    MYSQL_ROW row;
    int unitID;
    int caveID;
    int quantity;

    debug(DEBUG_TICKER, "entering function unit_handler()");

    row = mysql_fetch_row(result);
    if (!row) throw(SQL_EXCEPTION, "unit_handler: no unit event");

    /* get unit and cave id */
    unitID   = mysql_get_int_field(result, row, "unitID");
    caveID   = mysql_get_int_field(result, row, "caveID");

    /* get unit quantity */
    quantity = mysql_get_int_field(result, row, "quantity");
    
    debug(DEBUG_TICKER, "unit_handler: unitID: %d, caveID: %d, qantity: %d", unitID, caveID, quantity);
//    debug(DEBUG_TICKER, "unit_handler: Trying to write SQL: UPDATE cave SET %s = %s + %d WHERE caveID = %d",unitTypeList[unitID].dbFieldName, unitTypeList[unitID].dbFieldName, quantity, caveID );

    mysql_query_fmt(database, "UPDATE " DB_MAIN_TABLE_CAVE " SET %s = %s + %d"
			      " WHERE caveID = %d",
		    unitTypeList[unitID].dbFieldName,
		    unitTypeList[unitID].dbFieldName,
		    quantity, caveID);
//    debug(DEBUG_TICKER, "unit_handler: SQL written");
    debug(DEBUG_TICKER, "leaving function unit_handler()");
}
