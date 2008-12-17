/*
 * resource_ticker.c - automatic resource production
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdio.h>
#include <stdlib.h>

#include "except.h"
#include "formula_parser.h"
#include "game_rules.h"
#include "logging.h"
#include "memory.h"
#include "mysql_tools.h"
#include "resource_ticker.h"
#include "ticker_defs.h"
#include "ugatime.h"

/* configuration parameters */

const char *ticker_state;
long tick_interval;

/* static variables */

static time_t last_tick;

/*
 * Initialize resource events. Last event is taken from state file
 * or current time (if there is no state file).
 */
void tick_init (void)
{
    FILE *file = fopen(ticker_state, "r");
    long timeval = time(NULL);

    if (file)
    {
	fscanf(file, "%ld", &timeval);
	fclose(file);
    }

    /* init with stored tick, round to multiple of interval */
    last_tick = timeval / tick_interval * tick_interval;
    tick_log();
}

/*
 * Write last resource event timestamp to the state file.
 */
void tick_log (void)
{
    FILE *file = fopen_check(ticker_state, "w");

    fprintf(file, "%ld\n", (long) last_tick);
    if (fclose(file)) perror(ticker_state);
}

/*
 * Return the timestamp of the next resource event.
 */
time_t tick_next_event (void)
{
    return last_tick + tick_interval;
}

/*
 * Advance to the next resource event, return timestamp.
 */
time_t tick_advance (void)
{
    return last_tick += tick_interval;
}

/*
 * Perform resource update on the cave table.
 */
void resource_ticker (MYSQL *database, time_t timeval)
{
    const struct ugatime *uga_time = get_ugatime(timeval);
    double uga_bonus  = get_bonus(RELIGION_UGA,  uga_time)->production;
    double agga_bonus = get_bonus(RELIGION_AGGA, uga_time)->production;
    dstring_t *ds;
    int i;

    /* create start of the SQL statement */
    ds = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

    /* update each resource delta */
    for (i = 0; i < MAX_RESOURCE; ++i)
    {
	/* the function for the actual resource */
	const char *function =
	    parse_function(resourceTypeList[i].productionFunction);

	/* update the delta and value for this resource */
	dstring_append(ds, "%s%s_delta = (%s) * "
			   "IF((%s) <= 0, 1, 1 + SIGN(%s)*%g + SIGN(%s)*%g),"
			   "%s = GREATEST(LEAST(%s + %s_delta, %s), 0)",
		i > 0 ? "," : "",
		resourceTypeList[i].dbFieldName, function, function,
		DB_RELIGION_UGA_FIELD, uga_bonus,
		DB_RELIGION_AGGA_FIELD, agga_bonus,
		resourceTypeList[i].dbFieldName,
		resourceTypeList[i].dbFieldName,
		resourceTypeList[i].dbFieldName,
		parse_function(resourceTypeList[i].maxLevel));
    }

    /* end of the SQL statement */
//    dstring_append(ds, " WHERE playerID != 0");

// ADDED by chris--- for urlaub
    dstring_append(ds, " WHERE playerID != 0 AND urlaub = 0");


    debug(DEBUG_TICKER, "%s", dstring_str(ds));

    mysql_query_dstring(database, ds);
}
