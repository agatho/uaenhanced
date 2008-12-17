/*
 * quest_handler.h - event handler functions
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _QUEST_HANDLER_H_
#define _QUEST_HANDLER_H_

#include <mysql/mysql.h>
#include "logging.h"
#include "game_rules.h"

extern void quest_check_win (MYSQL *database, int questID, int *playerID, int *event_movementID, char *timestamp, int *target_caveID);

extern void process_quest_visit (MYSQL *database, int *target_caveID, int *playerID);

extern int isCaveQuestCave (MYSQL *database, int *target_caveID);

extern int caveIsInvisibleToPlayer(MYSQL *database, int *target_caveID, int *playerID);

extern int setCaveVisibleToPlayer(MYSQL *database, int *target_caveID, int *playerID);


#endif /* _QUEST_HANDLER_H_ */
