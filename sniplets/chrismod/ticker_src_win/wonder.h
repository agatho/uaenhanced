/*
 * wonder.h - wonder event information
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _WONDER_H_
#define _WONDER_H_

#include <mysql/mysql.h>


typedef struct WonderEvent {
  int event_wonderID;
  int casterID;
  int sourceID;
  int targetID;
  int wonderID;
  int impactID;

// inserted by chris---  (GOD-Stuff)
  time_t end;
//---------------  

} WonderEvent;

typedef struct reportEntity {
  char*  name;
  double value;
  int    decimals;
} reportEntity;

/*
 * Retrieve the wonder event struct from a row of suitable result set.
 */
extern WonderEvent get_wonder_event (MYSQL_RES *result, MYSQL_ROW row);

/*
 * returns the activeWonderID, if a matching wonder is active. returns
 * value <= 0 otherwise
 */
extern int get_activeWonderID (MYSQL *database, int
			       targetCaveID, int wonderID);

#endif /* _WONDER_H_ */
