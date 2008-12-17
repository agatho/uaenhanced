/*
 * wonder_handler.c - process wonder events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <stdlib.h>

#include "ticker_defs.h"
#include "formula_parser.h"
#include "wonder.h"
#include "wonder_rules.h"
#include "event_handler.h"
#include "except.h"
#include "logging.h"
#include "memory.h"
#include "mysql_tools.h"
#include "cave.h"
#include "message.h"

#ifndef MAX
#define MAX(x, y)	((x)>(y)?(x):(y))
#endif

#ifndef MIN
#define MIN(x, y)	((x)<(y)?(x):(y))
#endif

/*
 * ADDED by chris---: gauss function for wonders
*/
float create_gauss(void)
{
float r = (900+(float) (200.0*rand()/(RAND_MAX+1.0)))/1000;
return r;
}

/*
 * Calculate the deltas of the entities according to the impact
 */
static void wonder_calculate_deltas(const WonderGameEntityType* impact,
				    const int* entities,
				    double* deltas,
				    int size,
				    int allFlag)
{
  int i;
  

  for (i=0; i < size; i++) {

// ADDED by chris--- for wonder gauss
  float gauss = 1;
    if (impact[allFlag?0:i].type == 2) gauss = create_gauss();
// -----------------------------------------------------------------------

    deltas[i] =
      impact[allFlag?0:i].absolute + impact[allFlag?0:i].relative *
      entities[i]*gauss; // gauss ADDED by chris---

    if (impact[allFlag?0:i].maxDelta > 0.) {
      deltas[i] =
        MIN(deltas[i],
  	    impact[allFlag?0:i].maxDelta);
    }
  }
}

static void get_report_entities(reportEntity entities[],
				const double effectDeltas[],
				const double resourceDeltas[],
				const double unitDeltas[],
				const double buildingDeltas[],
				const double scienceDeltas[],
				const double defenseSystemDeltas[]
				)
{
  int i;
  int c=0;   // entities count
  if (effectDeltas) {
    for (i=0; i < MAX_EFFECT; i++) {
      if (effectDeltas[i] != 0) {
	entities[c].name       = effectTypeList[i].name;
	entities[c].value      = effectDeltas[i];
	entities[c++].decimals = 2;
      }
    }
  }
  if (resourceDeltas) {
    for (i=0; i < MAX_RESOURCE; i++) {
      if (resourceDeltas[i] != 0) {
	entities[c].name       = resourceTypeList[i].name;
	entities[c].value      = resourceDeltas[i];
	entities[c++].decimals = 0;
      }
    }
  }
  if (unitDeltas) {
    for (i=0; i < MAX_UNIT; i++) {
      if (unitDeltas[i] != 0) {
	entities[c].name       = unitTypeList[i].name;
	entities[c].value      = unitDeltas[i];
	entities[c++].decimals = 0;
      }
    }
  }
  if (buildingDeltas) {
    for (i=0; i < MAX_BUILDING; i++) {
      if (buildingDeltas[i] != 0) {
	entities[c].name       = buildingTypeList[i].name;
	entities[c].value      = buildingDeltas[i];
	entities[c++].decimals = 0;
      }
    }
  }
  if (scienceDeltas) {
    for (i=0; i < MAX_SCIENCE; i++) {
      if (scienceDeltas[i] != 0) {
	entities[c].name       = scienceTypeList[i].name;
	entities[c].value      = scienceDeltas[i];
	entities[c++].decimals = 0;
      }
    }
  }
  if (defenseSystemDeltas) {
    for (i=0; i < MAX_DEFENSESYSTEM; i++) {
      if (defenseSystemDeltas[i] != 0) {
	entities[c].name       = defenseSystemTypeList[i].name;
	entities[c].value      = defenseSystemDeltas[i];
	entities[c++].decimals = 0;
      }
    }
  }
}


/*
 * Function is called to instantiate a wonder's impact.
 * At the moment it calls a php-program, that does the whole work.
 */
void wonder_handler (MYSQL *database, MYSQL_RES *result)
{
    MYSQL_ROW row;
    WonderEvent event;
    const WonderType* wonderInfo;
    const WonderImpactType* impactInfo;
    struct Cave targetCaveInfo;
    struct Cave caveAfter;
    double duration;
    double effectDeltas[MAX_EFFECT];
    double resourceDeltas[MAX_RESOURCE];
    double unitDeltas[MAX_UNIT];
    double buildingDeltas[MAX_BUILDING];
    double scienceDeltas[MAX_SCIENCE];
    double defenseSystemDeltas[MAX_DEFENSESYSTEM];
    double realEffectDeltas[MAX_EFFECT];
    double realResourceDeltas[MAX_RESOURCE];
    double realUnitDeltas[MAX_UNIT];
    double realBuildingDeltas[MAX_BUILDING];
    double realScienceDeltas[MAX_SCIENCE];
    double realDefenseSystemDeltas[MAX_DEFENSESYSTEM];
    reportEntity *reportChanged = NULL;
    int    i, count, wonderCount, stolenCount;
    dstring_t *update;
    dstring_t *wonder;
    dstring_t *stolen;
    dstring_t *ds;

    debug(DEBUG_TICKER, "entering function wonder_handler()");

    //
    // clear all arrays
    //
    memset(effectDeltas, 0, sizeof(effectDeltas));
    memset(realEffectDeltas, 0, sizeof(effectDeltas));
    memset(unitDeltas, 0, sizeof(unitDeltas));
    memset(realUnitDeltas, 0, sizeof(unitDeltas));
    memset(buildingDeltas, 0, sizeof(buildingDeltas));
    memset(realBuildingDeltas, 0, sizeof(buildingDeltas));
    memset(scienceDeltas, 0, sizeof(scienceDeltas));
    memset(realScienceDeltas, 0, sizeof(scienceDeltas));
    memset(resourceDeltas, 0, sizeof(resourceDeltas));
    memset(realResourceDeltas, 0, sizeof(resourceDeltas));
    memset(defenseSystemDeltas, 0, sizeof(defenseSystemDeltas));
    memset(realDefenseSystemDeltas, 0, sizeof(defenseSystemDeltas));

    //
    // get information about the event
    //
    row = mysql_fetch_row(result);
    if (!row) throw(SQL_EXCEPTION, "wonder_handler: no wonder event");
    event = get_wonder_event(result, row);

    debug(DEBUG_WONDER, "process wonderID %d, impactID %d",
          event.wonderID, event.impactID);
    wonderInfo = &wonderList[event.wonderID];
    impactInfo = &wonderInfo->impacts[event.impactID];
    duration   = impactInfo->duration * WONDER_TIME_BASE_FACTOR;

    // get data of the target cave
    get_cave_info(database, event.targetID, &targetCaveInfo);
    
//------------------------------------------------------------------------------
// GOD STUFF - inserted by chris---
//------------------------------------------------------------------------------

ds = dstring_new("");

if (targetCaveInfo.player_id > 0 ) {

char event_new_end[TIMESTAMP_LEN];
make_timestamp(event_new_end, event.end);

//  if (targetCaveInfo.cave_id == 1080 || targetCaveInfo.cave_id == 250)
  if (targetCaveInfo.cave_id == 1018)
{
  debug(DEBUG_WONDER, "Wonder to a god!");
  
// is it a bad wonder?
/*
  if (event.wonderID == 3 || event.wonderID == 4 || event.wonderID == 5 || event.wonderID == 8 || event.wonderID == 9
    || event.wonderID == 11 || event.wonderID == 13 || event.wonderID == 22 || event.wonderID == 23 || event.wonderID == 24
    || event.wonderID == 25 || event.wonderID == 26 || event.wonderID == 29 || event.wonderID == 30 || event.wonderID == 33
    || event.wonderID == 34 || event.wonderID == 35 || event.wonderID == 38 || event.wonderID == 40 || event.wonderID == 41
    || event.wonderID == 43 || event.wonderID == 44 || event.wonderID == 45 || event.wonderID == 46 || event.wonderID == 47
    || event.wonderID == 48 || event.wonderID == 49 || event.wonderID == 50 || event.wonderID == 51 || event.wonderID == 54
    || event.wonderID == 55 || event.wonderID == 58 || event.wonderID == 59 || event.wonderID == 60 || event.wonderID == 61
    || event.wonderID == 62 || event.wonderID == 63 || event.wonderID == 64 || event.wonderID == 65 || event.wonderID == 66)
*/
  if (event.wonderID == 3 || event.wonderID == 4 || event.wonderID == 5 || event.wonderID == 10 || event.wonderID == 11 || event.wonderID == 12)
    {
      debug(DEBUG_WONDER, "This is a bad wonder: wonderID %d, we need to enter this in god db", event.wonderID);

      mysql_query_fmt(database,
			"INSERT INTO event_gods SET target_caveID = %d, source_caveID = %d, impact = %s, "
			"blocked = 0, event = 1, eventID = %d, playerID = %d",
			event.targetID, event.sourceID, event_new_end, event.wonderID, event.casterID);
     } else {
       debug(DEBUG_WONDER, "This is a good wonder: wonderID %d, nothing to do", event.wonderID);
     }
}
}

//------------------------------------------------------------------------------

    //
    // Check whether this wonder is already effecting the target cave
    //
    if (wonderInfo->impactSize <= 1) {
      int activeWonderID = get_activeWonderID(database,
					      targetCaveInfo.cave_id,
					      wonderInfo->wonderID);
      if (activeWonderID > 0) {
	mysql_query_fmt(database,
			"UPDATE ActiveWonder "
			"SET end = (end + INTERVAL %f SECOND)+0 "
			"WHERE activeWonderID = %d",
			duration,
			activeWonderID);
	wonder_prolonged_report(database, event.casterID, &targetCaveInfo);
	
// INSERTED by chris--- for stats		    
// Update stats
    ds = dstring_new("UPDATE stats SET wunderberichte = wunderberichte + 1");
      mysql_query_dstring(database, ds);
// ------------------------------------------

        // leave handler
        debug(DEBUG_TICKER, "leaving function wonder_handler()");
	return ;
      }
    }

    //
    // Calculate the impact deltas and construct update query
    //
    update = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");

    count = 0;
    if (impactInfo->effects) {
      for (i=0; i < MAX_EFFECT; i++) {
	effectDeltas[i] =
	  impactInfo->effects[impactInfo->effectsAll?0:i].absolute;
      }
      for (i=0; i < MAX_EFFECT; ++i) {
	if (effectDeltas[i] != 0.) {
	  dstring_append(update,
			 "%s%s = %s + %f",
			 count > 0 ? ", " : "",
			 effectTypeList[i].dbFieldName,
			 effectTypeList[i].dbFieldName,
			 effectDeltas[i]);
	  ++count;
	}
      }
    }
    if (impactInfo->resources) {
      wonder_calculate_deltas(impactInfo->resources,
			      targetCaveInfo.resource,
			      resourceDeltas,
			      MAX_RESOURCE,
			      impactInfo->resourcesAll);
      for (i=0; i < MAX_RESOURCE; ++i) {
	if (resourceDeltas[i] != 0.) {
	  dstring_append(update,
			 "%s%s = GREATEST(0, LEAST(%s + %f, %s))",
			 count > 0 ? ", " : "",
			 resourceTypeList[i].dbFieldName,
			 resourceTypeList[i].dbFieldName,
			 resourceDeltas[i],
			 parse_function(resourceTypeList[i].maxLevel));
	  ++count;
	}
      }
    }
    if (impactInfo->units) {
      wonder_calculate_deltas(impactInfo->units,
			      targetCaveInfo.unit,
			      unitDeltas,
			      MAX_UNIT,
			      impactInfo->unitsAll);
      for (i=0; i < MAX_UNIT; ++i) {
	if (unitDeltas[i] != 0.) {
	  dstring_append(update,
			 "%s%s = GREATEST(0, %s + %f)",
			 count > 0 ? ", " : "",
			 unitTypeList[i].dbFieldName,
			 unitTypeList[i].dbFieldName,
			 unitDeltas[i]);
	  ++count;
	}
      }
    }
    if (impactInfo->sciences) {
      wonder_calculate_deltas(impactInfo->sciences,
			      targetCaveInfo.science,
			      scienceDeltas,
			      MAX_SCIENCE,
			      impactInfo->sciencesAll);
      for (i=0; i < MAX_SCIENCE; ++i) {
	if (scienceDeltas[i] != 0.) {
	  dstring_append(update,
			 "%s%s = GREATEST(0, %s + %f)",
			 count > 0 ? ", " : "",
			 scienceTypeList[i].dbFieldName,
			 scienceTypeList[i].dbFieldName,
			 scienceDeltas[i]);
	  ++count;
	}
      }
    }
    if (impactInfo->buildings) {
      wonder_calculate_deltas(impactInfo->buildings,
			      targetCaveInfo.building,
			      buildingDeltas,
			      MAX_BUILDING,
			      impactInfo->buildingsAll);
      for (i=0; i < MAX_BUILDING; ++i) {
	if (buildingDeltas[i] != 0.) {
	  dstring_append(update,
			 "%s%s = GREATEST(0, %s + %f)",
			 count > 0 ? ", " : "",
			 buildingTypeList[i].dbFieldName,
			 buildingTypeList[i].dbFieldName,
			 buildingDeltas[i]);
	  ++count;
	}
      }
    }
    if (impactInfo->defenseSystems) {
      wonder_calculate_deltas(impactInfo->defenseSystems,
			      targetCaveInfo.defense_system,
			      defenseSystemDeltas,
			      MAX_DEFENSESYSTEM,
			      impactInfo->defenseSystemsAll);
      for (i=0; i < MAX_DEFENSESYSTEM; ++i) {
	if (defenseSystemDeltas[i] != 0.) {
	  dstring_append(update,
			 "%s%s = GREATEST(0, %s + %f)",
			 count > 0 ? ", " : "",
			 defenseSystemTypeList[i].dbFieldName,
			 defenseSystemTypeList[i].dbFieldName,
			 defenseSystemDeltas[i]);
	  ++count;
	}
      }
    }

    //
    // update the cave
    //
    dstring_append(update," WHERE caveID = %d", event.targetID);
    debug(DEBUG_TICKER, "%s", dstring_str(update));
    if (count>0) {
      mysql_query_dstring(database, update);
    }

    //
    // get new Data of Cave
    //
    get_cave_info(database, event.targetID, &caveAfter);

    //
    // calculate the real deltas and create update queries for active
    // wonders and stolen entities update
    //
    wonder = dstring_new("INSERT INTO ActiveWonder SET wonderID = '%d', "
			 "impactID = '%d', casterID = '%d', "
			 "playerID = '%d', "
			 "caveID = '%d', "
			 "end = (NOW() + INTERVAL %f SECOND)+0 ",
			 event.wonderID,
			 event.impactID,
			 event.casterID,
			 caveAfter.player_id,
			 event.targetID,
			 duration);
    stolen = dstring_new("UPDATE " DB_MAIN_TABLE_CAVE " SET ");


    wonderCount = 0;
    stolenCount = 0;
    for (i=0; i < MAX_EFFECT; ++i) {
      realEffectDeltas[i] = caveAfter.effect[i] - targetCaveInfo.effect[i];
      if (realEffectDeltas[i] != 0.) {
	dstring_append(wonder,
		       ", %s = %f",
		       effectTypeList[i].dbFieldName,
		       realEffectDeltas[i]);
	++wonderCount;
      }
    }
    for (i=0; i < MAX_RESOURCE; ++i) {
      realResourceDeltas[i] =
	caveAfter.resource[i] - targetCaveInfo.resource[i];
      if (realResourceDeltas[i] != 0.) {
	dstring_append(wonder,
		       ", %s = %f",
		       resourceTypeList[i].dbFieldName,
		       realResourceDeltas[i]);
	dstring_append(stolen,
		       "%s%s = GREATEST(0, LEAST(%s - %f, %s))",
		       stolenCount > 0 ? ", " : "",
		       resourceTypeList[i].dbFieldName,
		       resourceTypeList[i].dbFieldName,
		       realResourceDeltas[i] * impactInfo->steal,
		       parse_function(resourceTypeList[i].maxLevel));
	++wonderCount; ++stolenCount;
      }
    }
    for (i=0; i < MAX_UNIT; ++i) {
      realUnitDeltas[i] =
	caveAfter.unit[i] - targetCaveInfo.unit[i];
      if (realUnitDeltas[i] != 0.) {
	dstring_append(wonder,
		       ", %s = %f",
		       unitTypeList[i].dbFieldName,
		       realUnitDeltas[i]);
	dstring_append(stolen,
		       "%s%s = GREATEST(0, %s - %f)",
		       stolenCount > 0 ? ", " : "",
		       unitTypeList[i].dbFieldName,
		       unitTypeList[i].dbFieldName,
		       realUnitDeltas[i] * impactInfo->steal);
	++wonderCount; ++stolenCount;
      }
    }
    for (i=0; i < MAX_BUILDING; ++i) {
      realBuildingDeltas[i] =
	caveAfter.building[i] - targetCaveInfo.building[i];
      if (realBuildingDeltas[i] != 0.) {
	dstring_append(wonder,
		       ", %s = %f",
		       buildingTypeList[i].dbFieldName,
		       realBuildingDeltas[i]);
	dstring_append(stolen,
		       "%s%s = GREATEST(0, %s - %f)",
		       stolenCount > 0 ? ", " : "",
		       buildingTypeList[i].dbFieldName,
		       buildingTypeList[i].dbFieldName,
		       realBuildingDeltas[i] * impactInfo->steal);
	++wonderCount; ++stolenCount;
      }
    }
    for (i=0; i < MAX_SCIENCE; ++i) {
      realScienceDeltas[i] =
	caveAfter.science[i] - targetCaveInfo.science[i];
      if (realScienceDeltas[i] != 0.) {
	dstring_append(wonder,
		       ", %s = %f",
		       scienceTypeList[i].dbFieldName,
		       realScienceDeltas[i]);
	dstring_append(stolen,
		       "%s%s = GREATEST(0, %s - %f)",
		       stolenCount > 0 ? ", " : "",
		       scienceTypeList[i].dbFieldName,
		       scienceTypeList[i].dbFieldName,
		       realScienceDeltas[i] * impactInfo->steal);
	++wonderCount; ++stolenCount;
      }
    }
    for (i=0; i < MAX_DEFENSESYSTEM; ++i) {
      realDefenseSystemDeltas[i] =
	caveAfter.defense_system[i] - targetCaveInfo.defense_system[i];
      if (realDefenseSystemDeltas[i] != 0.) {
	dstring_append(wonder,
		       ", %s = %f",
		       defenseSystemTypeList[i].dbFieldName,
		       realDefenseSystemDeltas[i]);
	dstring_append(stolen,
		       "%s%s = GREATEST(0, %s - %f)",
		       stolenCount > 0 ? ", " : "",
		       defenseSystemTypeList[i].dbFieldName,
		       defenseSystemTypeList[i].dbFieldName,
		       realDefenseSystemDeltas[i] * impactInfo->steal);
	++wonderCount; ++stolenCount;
      }
    }
    dstring_append(stolen,
		   " WHERE caveID = %d",
		   event.sourceID);

    debug(DEBUG_TICKER, "ActiveWonderQuery: %s", dstring_str(wonder));
    debug(DEBUG_TICKER, "StolenQuery: %s", dstring_str(stolen));

    // add to active wonders, if necessary
    if (impactInfo->duration > 0) {
      mysql_query_dstring(database, wonder);
    }
    // insert stolen materials into cave of caster
    if (impactInfo->steal > 0 && stolenCount > 0) {
      mysql_query_dstring(database, stolen);
    }

    //
    // create messages
    //
    if (wonderCount) {
      reportChanged = mp_calloc(wonderCount, sizeof(reportEntity));
      get_report_entities(&reportChanged[0],
			  realEffectDeltas,
			  realResourceDeltas,
			  realUnitDeltas,
			  realBuildingDeltas,
			  realScienceDeltas,
			  realDefenseSystemDeltas);
    }

    wonder_report(database, &event, &targetCaveInfo, reportChanged,
                  wonderCount);
                  
// INSERTED by chris--- for stats		    
// Update stats
    ds = dstring_new("UPDATE stats SET wunderberichte = wunderberichte + 1");
      mysql_query_dstring(database, ds);
// ------------------------------------------

    // leave handler
    debug(DEBUG_TICKER, "leaving function wonder_handler()");
}






