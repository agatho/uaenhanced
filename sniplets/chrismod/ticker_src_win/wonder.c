/*
 * wonder.c - wonder event information and helping functions
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "wonder.h"
#include <mysql/mysql.h>
#include "mysql_tools.h"
#include <string.h>

WonderEvent get_wonder_event(MYSQL_RES *result, MYSQL_ROW row)
{
  WonderEvent event;
  memset (&event, 0, sizeof(event));

  event.event_wonderID = mysql_get_int_field(result, row, "event_wonderID");
  event.casterID = mysql_get_int_field(result, row, "casterID");
  event.sourceID = mysql_get_int_field(result, row, "sourceID");
  event.targetID = mysql_get_int_field(result, row, "targetID");
  event.wonderID = mysql_get_int_field(result, row, "wonderID");
  event.impactID = mysql_get_int_field(result, row, "impactID");

// inserted by chris--- (GOD-Stuff)
  event.end = make_time(mysql_get_string_field(result, row, "event_end"));
// ---------------

  return event;
}

int get_activeWonderID (MYSQL *database, int targetCaveID, int wonderID)
{
  MYSQL_RES* result = mysql_query_fmt(database,
				      "SELECT * FROM ActiveWonder "
				      "WHERE caveID = %d "
				      "AND wonderID = %d",
				      targetCaveID,
				      wonderID);
  MYSQL_ROW row = mysql_fetch_row(result);
  if (!row) return -1;

  return mysql_get_int_field(result, row, "activeWonderID");
}

