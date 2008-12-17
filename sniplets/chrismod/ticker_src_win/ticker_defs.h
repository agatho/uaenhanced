/*
 * ticker_defs.h - general definitions for the ticker
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _TICKER_DEFS_H_
#define _TICKER_DEFS_H_

#define DB_ARTEFACT		"Artefact"
#define DB_CAVE_TAKEOVER	"Cave_takeover"
#define DB_MAIN_TABLE_CAVE	"Cave"
#define DB_MAIN_TABLE_MOVEMENT	"Event_movement"
#define DB_MAIN_TABLE_PLAYER	"Player"
#define DB_MAIN_TABLE_RELATION  "Relation"

#define DB_EVENT_TIME_FIELD	"event_end"
#define DB_EVENT_BLOCK_FIELD	"blocked"

#define DB_RELIGION_UGA_FIELD	"science_faith" // CHANGED by chris---
#define DB_RELIGION_AGGA_FIELD	"science_darkness" // CHANGED by chris---
#define ID_SCIENCE_UGA		15
#define ID_SCIENCE_AGGA		16

#define TAKEOVER_MULTIPLIER_BUILDING 14

#define ARTEFACT_LOST_PERCENTAGE 0.2
#define ARTEFACT_LOST_RANGE	2

#define FAME_FACTOR_ATTACKER	0.10
#define FAME_FACTOR_DEFENDER	0.05
#define FAME_MAX_OVERPOWER_FACTOR 4

#define WONDER_TIME_BASE_FACTOR	60

#define RELATION_TYPE_NONE	0

#define GOD_ALLY "Astaroth" // ADDED by chris---

#endif /* _TICKER_DEFS_H_ */
