/*
 * artefact_handler.c - handle artefact events
 * Copyright (c) 2003 Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
*/

#include <stdlib.h>
#include <stdio.h>

#include "artefact.h"      // artefact/artefact_class typedefs
#include "cave.h"          // get_cave_info
#include "event_handler.h" // function declaration
#include "except.h"        // exception handling
#include "logging.h"       // debug
#include "message.h"       // artefact_report etc.
#include "mysql_tools.h"   // mysql_get_int_field etc.

void artefact_handler (MYSQL *database, MYSQL_RES *result){
  MYSQL_ROW row;
  int       artefactID;
  int       caveID;

  struct Cave           cave;
  struct Artefact       artefact;
  struct Artefact_class artefact_class;

  struct Artefact lock_artefact;
  struct Artefact result_artefact;

  debug(DEBUG_TICKER, "entering function artefact_handler()");

  row = mysql_fetch_row(result);
  if (!row) throw(SQL_EXCEPTION, "artefact_handler: no artefact event");

  // get Event_artefact
  artefactID = mysql_get_int_field(result, row, "artefactID");
  caveID     = mysql_get_int_field(result, row, "caveID");

  debug(DEBUG_TICKER, "artefactID = %d, caveID = %d",artefactID,caveID);

  // get Artefact and its class
  get_artefact_by_id(database, artefactID, &artefact);
  get_artefact_class_by_id(database, artefact.artefactClassID, &artefact_class);
  // XXX artefact.caveID != 0 here?
  get_cave_info(database, artefact.caveID, &cave);
  

// INSERTED by chris--- for artefacts destroying
// ----------------------------------------  
double r = (double)rand() / RAND_MAX;
    
//if (artefact_class.artefactClassID > 3) r = 1;

double destroy_chance;
destroy_chance = artefact_class.destroy_chance;
if (destroy_chance == 0) r = 2;
if (destroy_chance == 1) r = -1;

debug(DEBUG_TICKER, "artefactClassID = %d, destroy_chance = %f, random = %f", artefact.artefactClassID, destroy_chance, r);

//if (r > 0.5) {
if (r > destroy_chance) {
// normal artefact handling

  // initieren
  initiate_artefact(database, artefactID);
  debug(DEBUG_TICKER, "initiated artefact");

  // effekte eintragen
  apply_effects_to_cave(database, artefactID);
  debug(DEBUG_TICKER, "applied effects");

  // write message
  artefact_report(database, &cave, artefact_class.name);

  // merge artefacts
  lock_artefact.artefactID   = 0;
  result_artefact.artefactID = 0;

  // try formulas
  if (merge_artefacts_special(database, &artefact, &lock_artefact, &result_artefact) ||
      merge_artefacts_general(database, &artefact, &lock_artefact, &result_artefact)){

    // formula found
    artefact_merging_report(database, &cave, &artefact, &lock_artefact, &result_artefact);
  }

} // end my if
else {
// artefact destroyed

  // write message
  artefact_destr_report(database, &cave, artefact_class.name);
  
// deleting artefact from artefact table
  mysql_query_fmt(database, "DELETE FROM Artefact "
			    "WHERE artefactID = %d AND caveID = %d",
		  artefactID, caveID);

// Decresing artefact from cave
  mysql_query_fmt(database, "UPDATE Cave SET artefacts = artefacts -1 "
			    "WHERE caveID = %d",
		  caveID);
		  
debug(DEBUG_TICKER, "artefact with id = %d destroyed in caveID %d", artefactID, caveID);
  
} // end my if

  debug(DEBUG_TICKER, "leaving function artefact_handler()");
}
