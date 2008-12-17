/*
 * message.h - generate ticker reports to players
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _MESSAGE_H_
#define _MESSAGE_H_

#include <mysql/mysql.h>


#include "artefact.h"
#include "calc_battle.h"
#include "cave.h"
#include "wonder.h"

#define MSG_CLASS_INFO		0
#define MSG_CLASS_VICTORY	2
#define MSG_CLASS_COMPLETED	4
#define MSG_CLASS_TRADE		6
#define MSG_CLASS_RETURN	7
#define MSG_CLASS_WONDER	9
#define MSG_CLASS_USER		10
#define MSG_CLASS_SPY_REPORT	11
#define MSG_CLASS_ARTEFACT	12
#define MSG_CLASS_DEFEAT	20
#define MSG_CLASS_UGA_AGGA	99
#define MSG_CLASS_ANNOUNCE	1001

#define PLAYER_SYSTEM		0

extern void reports_init (void);

extern void trade_report (MYSQL *database,
			  const struct Cave *cave1, const char *player1,
			  const struct Cave *cave2, const char *player2,
			  const int resources[], const int units[],
			  int artifact);

extern void return_report (MYSQL *database,
			   const struct Cave *cave1, const char *player1,
			   const struct Cave *cave2, const char *player2,
			   const int resources[], const int units[],
			   int artifact);

extern void battle_report (MYSQL *database,
			   const struct Cave *cave1, const char *player1,
			   const struct Cave *cave2, const char *player2,
			   const Battle *result, int artifact, int lost,
			   int change_owner, int takeover_multiplier,
			   const struct Relation *relation1,
			   const struct Relation *relation2, int fame);

extern void protected_report (MYSQL *database,
			      const struct Cave *cave1, const char *player1,
			      const struct Cave *cave2, const char *player2);

extern void wonder_report (MYSQL *database,
			   const WonderEvent  *event,
			   const struct Cave  *targetCave,
			   const reportEntity *values, int values_size);

extern int spy_report (MYSQL *database,
		       const struct Cave *cave1, const char *player1,
		       const struct Cave *cave2, const char *player2,
		       const int resources[], const int units[], int artifact);

extern void artefact_report (MYSQL *database, const struct Cave *cave,
			     const char *artefact_name);

// ADDED by chris--- for artefact destroying			     
extern void artefact_destr_report (MYSQL *database, const struct Cave *cave,
			     const char *artefact_name);
// ----------------------------------------------------------------------

extern void artefact_merging_report (MYSQL *database, const struct Cave *cave,
				     const struct Artefact *key_artefact,
				     const struct Artefact *lock_artefact,
				     const struct Artefact *result_artefact);

extern void wonder_prolonged_report (MYSQL *database, int casterID,
				     const struct Cave *targetCave);

#endif /* _MESSAGE_H_ */
