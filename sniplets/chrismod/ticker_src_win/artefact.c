/*
 * artefact.c - handle artefacts
 * Copyright (c) 2003 Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "artefact.h"     // artefact/artefact_class typedefs
#include "except.h"       // exception handling
#include "logging.h"      // warning_msg
#include "memory.h"       // dstring et.al.
#include "mysql_tools.h"  // mysql_get_int_field etc.
#include "ticker_defs.h"  // DB_MAIN_TABLE_MOVEMENT

/** Retrieve artefact for the given id.
 */
void get_artefact_by_id(MYSQL *database, int artefactID, struct Artefact *artefact){

  MYSQL_RES *result = mysql_query_fmt(database, "SELECT * FROM Artefact WHERE artefactID = %d", artefactID);
  MYSQL_ROW row;

  // Bedingung: Artefakt muss vorhanden sein
  if (mysql_num_rows(result) != 1)
    throw(SQL_EXCEPTION, "get_artefact_by_id: no such artefactID");

  row = mysql_fetch_row(result);

  // memset(artefact, 0, sizeof *artefact);

  artefact->artefactID      = artefactID;
  artefact->artefactClassID = mysql_get_int_field(result, row, "artefactClassID");
  artefact->caveID          = mysql_get_int_field(result, row, "caveID");
  artefact->initiated       = mysql_get_int_field(result, row, "initiated");
}

/** Retrieve artefact_class for the given id.
 */
void get_artefact_class_by_id(MYSQL *database, int artefactClassID, struct Artefact_class *artefact_class){

  MYSQL_RES *result = mysql_query_fmt(database, "SELECT * FROM Artefact_class WHERE artefactClassID = %d", artefactClassID);
  MYSQL_ROW row;
  int type;

  // Bedingung: Artefaktklasse muss vorhanden sein
  if (mysql_num_rows(result) != 1)
    throw(SQL_EXCEPTION, "get_artefact_class_by_id: no such artefactClassID");

  row = mysql_fetch_row(result);

  memset(artefact_class, 0, sizeof *artefact_class);

  artefact_class->artefactClassID       = artefactClassID;
  artefact_class->name                  = mysql_get_string_field(result, row, "name");
  artefact_class->resref                = mysql_get_string_field(result, row, "resref");
  artefact_class->description           = mysql_get_string_field(result, row, "description");
  artefact_class->description_initiated = mysql_get_string_field(result, row, "description_initiated");
  artefact_class->initiationID          = mysql_get_int_field(result, row, "initiationID");
  artefact_class->destroy_chance        = mysql_get_double_field(result, row, "destroy_chance"); // ADDED by chris--- for artefact destroying

  for (type = 0; type < MAX_EFFECT; ++type)
    try {
      artefact_class->effect[type] = mysql_get_double_field(result, row, effectTypeList[type].dbFieldName);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } end_try;
}

/** put artefact into cave after finished movement.
 */
void put_artefact_into_cave(MYSQL *database, int artefactID, int caveID){

  mysql_query_fmt(database, "UPDATE Artefact SET caveID = %d "
                            "WHERE artefactID = %d "
                            "AND caveID = 0 "
                            "AND initiated = %d",
                            caveID, artefactID, ARTEFACT_UNINITIATED);

  // Bedingung: Artefakt muss vorhanden sein; darf in keiner anderen Höhle liegen;
  //   muss uninitialisiert sein
  if (mysql_affected_rows(database) != 1)
    throw(BAD_ARGUMENT_EXCEPTION, "put_artefact_into_cave: no such artefactID or"
                             "artefact already in another cave or not uninitiated");


  mysql_query_fmt(database, "UPDATE Cave SET artefacts = artefacts + 1 "
			    "WHERE caveID = %d", caveID);

  // Bedingung: Höhle muss vorhanden sein
  if (mysql_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "put_artefact_into_cave: no such caveID");
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the artefact from its cave
 */
void remove_artefact_from_cave(MYSQL *database, int artefactID){

  struct Artefact artefact;

  // save artefact values; throws exception, if that artefact is missing
  get_artefact_by_id(database, artefactID, &artefact);

  mysql_query_fmt(database, "UPDATE Artefact SET caveID = 0 "
			    "WHERE artefactID = %d", artefactID);

  // Bedingung: Artefakt muss vorhanden sein
  // XXX can this happen? get_artefact_by_id() had failed if it does not exist
  if (mysql_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "remove_artefact_from_cave: no such artefactID");

  mysql_query_fmt(database, "UPDATE Cave SET artefacts = artefacts - 1 "
			    "WHERE caveID = %d", artefact.caveID);

  // Bedingung: Höhle muss vorhanden sein
  if (mysql_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "remove_artefact_from_cave: no such caveID");
}

/** initiating finished. now set the status of the artefact to ARTEFACT_INITIATED.
 */
void initiate_artefact(MYSQL *database, int artefactID){

  struct Artefact artefact;

  // get artefact values; throws exception, if that artefact is missing
  get_artefact_by_id(database, artefactID, &artefact);

  // Bedingung: muss gerade eingeweiht werden
  if (artefact.initiated != ARTEFACT_INITIATING)
    throw(BAD_ARGUMENT_EXCEPTION, "initiate_artefact: artefact was not initiating");

  // Bedingung: muss in einer Höhle liegen
  if (artefact.caveID == 0)
    throw(BAD_ARGUMENT_EXCEPTION, "initiate_artefact: artefact was not in a cave");

  mysql_query_fmt(database, "UPDATE Artefact SET initiated = %d "
			    "WHERE artefactID = %d AND caveID = %d",
		  ARTEFACT_INITIATED, artefact.artefactID, artefact.caveID);

  // Bedingung: Artefakt und Höhle müssen existieren
  if (mysql_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "initiate_artefact: no such artefactID or caveID");
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  uninitiate this artefact
 */
void uninitiate_artefact(MYSQL *database, int artefactID){

  mysql_query_fmt(database, "UPDATE Artefact SET initiated = %d "
			    "WHERE artefactID = %d",
		  ARTEFACT_UNINITIATED, artefactID);

  mysql_query_fmt(database, "DELETE FROM Event_artefact WHERE artefactID = %d",
		  artefactID);
}

/*
 * status already set to ARTEFACT_INITIATED, now apply the effects
 */
void apply_effects_to_cave(MYSQL *database, int artefactID){

  struct Artefact       artefact;
  struct Artefact_class artefact_class;
  dstring_t             *ds = dstring_new("UPDATE Cave SET ");
  int                   i;

  // get artefact values; throws exception, if that artefact is missing
  get_artefact_by_id(database, artefactID, &artefact);
  // get artefactClass; throws exception, if that artefactClass is missing
  get_artefact_class_by_id(database, artefact.artefactClassID, &artefact_class);

  // Bedingung: muss eingeweiht sein
  if (artefact.initiated != ARTEFACT_INITIATED)
    throw(BAD_ARGUMENT_EXCEPTION, "initiate_artefact: artefact was not initiated");

  for (i = 0; i < MAX_EFFECT; ++i)
    dstring_append(ds, "%s %s = %s + %f",
                  (i == 0 ? "" : ","),
                  effectTypeList[i].dbFieldName,
                  effectTypeList[i].dbFieldName,
                  artefact_class.effect[i]);

  dstring_append(ds, " WHERE caveID = %d", artefact.caveID);

  mysql_query_dstring(database, ds);
}

/** user wants to remove the artefact from cave or another user just robbed that user.
 *  remove the effects. (same as apply_effects but with a "-" instead of the "+"
 */
void remove_effects_from_cave(MYSQL *database, int artefactID){
  struct Artefact       artefact;
  struct Artefact_class artefact_class;
  dstring_t             *ds = dstring_new("UPDATE Cave SET ");
  int                   i;

  // get artefact values; throws exception, if that artefact is missing
  get_artefact_by_id(database, artefactID, &artefact);
  // get artefactClass; throws exception, if that artefactClass is missing
  get_artefact_class_by_id(database, artefact.artefactClassID, &artefact_class);

  // Wenn das Artefakt nicht mehr eingeweiht ist, müssen die Effekte nicht mehr entfernt werden.
  if (artefact.initiated != ARTEFACT_INITIATED) return;

  for (i = 0; i < MAX_EFFECT; ++i)
    dstring_append(ds, "%s %s = %s - %f",
                  (i == 0 ? "" : ","),
                  effectTypeList[i].dbFieldName,
                  effectTypeList[i].dbFieldName,
                  artefact_class.effect[i]);

  dstring_append(ds, " WHERE caveID = %d", artefact.caveID);

  mysql_query_dstring(database, ds);
}

int new_artefact(MYSQL *database, int artefactClassID){

  MYSQL_RES *result;

  // get artefact class
  result = mysql_query_fmt(database,
                           "SELECT * FROM Artefact_class "
                           "WHERE artefactClassID = %d",
                           artefactClassID);

  // no such class
  if (mysql_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "new_artefact: no such artefact class");

  mysql_query_fmt(database, "INSERT INTO Artefact "
			    "(artefactClassID, caveID, initiated) "
			    "VALUES (%d, 0, 0)", artefactClassID);

  // successfully inserted?
  if (mysql_affected_rows(database) != 1)
    throw(SQL_EXCEPTION, "new_artefact: could not insert artefact");

  return mysql_insert_id(database);
}

/** merge_artefacts tries to merge a key and a lock artefact
 *  into a result artefact
 *  throws exceptions, if needed conditions are not as they should have been
 */
void merge_artefacts(MYSQL *database, int caveID, int keyArtefactID, int lockArtefactID, int resultArtefactID){

  // first remove the key
  remove_effects_from_cave(database,  keyArtefactID);
  uninitiate_artefact(database,       keyArtefactID);
  remove_artefact_from_cave(database, keyArtefactID);
  debug(DEBUG_TICKER, "merge_artefacts: removed key artefact [id %d]",
        keyArtefactID);

  // then remove the lock
  if (lockArtefactID != 0 && lockArtefactID != keyArtefactID){

    remove_effects_from_cave(database,  lockArtefactID);
    uninitiate_artefact(database,       lockArtefactID);
    remove_artefact_from_cave(database, lockArtefactID);
    debug(DEBUG_TICKER, "merge_artefacts: removed lock artefact [id %d]",
          lockArtefactID);

  } else {
    debug(DEBUG_TICKER, "merge_artefacts: no lock artefact needed");
  }

  // now put the result into the cave
  if (resultArtefactID != 0){

    put_artefact_into_cave(database, resultArtefactID, caveID);
    debug(DEBUG_TICKER,
          "merge_artefacts: put result artefact [id %d] into cave [id %d]",
          resultArtefactID, caveID);

  } else {
    debug(DEBUG_TICKER, "merge_artefacts: no result artefact");
  }
}
/** merge_artefacts_special
 *  throws exceptions, if needed conditions are not as they should have been
 */

int merge_artefacts_special(MYSQL *database,
                      const struct Artefact *key_artefact,
                            struct Artefact *lock_artefact,
                            struct Artefact *result_artefact)
{
  MYSQL_RES *result;
  MYSQL_RES *temp_result;
  MYSQL_ROW row;

  // get merging formulas
  result = mysql_query_fmt(database,
                           "SELECT * FROM Artefact_merge_special "
                           "WHERE keyID = %d", key_artefact->artefactID);

  // check for a suitable merging formula
  while ((row = mysql_fetch_row(result)) != NULL)
  {
    // some special cases:
    //
    // lockID == 0 || keyID == lockID
    // no lock artefact needed; key artefact transforms directly
    //
    // resultID == 0
    // key and lock artefacts just vanish


    // lock artefact
    lock_artefact->artefactID = mysql_get_int_field(result, row, "lockID");

    // special cases: lockID == 0 || keyID == lockID (no lock required)
    if (lock_artefact->artefactID == 0 ||
	lock_artefact->artefactID == key_artefact->artefactID)
      break;

    // get lock_artefact
    // throws exception, if that artefact is missing
    get_artefact_by_id(database, lock_artefact->artefactID, lock_artefact);

    // check: key and lock have to be in the same cave and initiated
    if (lock_artefact->caveID == key_artefact->caveID &&
	lock_artefact->initiated == ARTEFACT_INITIATED)
      break;
  }

  if (row)
  {
    // result artefact
    result_artefact->artefactID = mysql_get_int_field(result, row, "resultID");

    // special case: resultID == 0
    if (result_artefact->artefactID != 0)
    {
      // get result_artefact
      // throws exception, if that artefact is missing
      get_artefact_by_id(database, result_artefact->artefactID, result_artefact);

      // check: result_artefact must not be in any cave
      if (result_artefact->caveID != 0)
        throwf(BAD_ARGUMENT_EXCEPTION,
	       "merge_artefacts_special: result artefact %d is in cave %d",
               result_artefact->artefactID, result_artefact->caveID);

      // result_artefact must not be in any movement
      temp_result = mysql_query_fmt(database,
                                    "SELECT * FROM " DB_MAIN_TABLE_MOVEMENT
				    " WHERE artefactID = %d",
                                    result_artefact->artefactID);

      if (mysql_num_rows(temp_result) != 0)
        throwf(BAD_ARGUMENT_EXCEPTION,
	       "merge_artefacts_special: result artefact %d is moving",
               result_artefact->artefactID);

      // check: result_artefact has to be uninitiated
      // XXX can this ever happen (it is not in a cave)?
      if (result_artefact->initiated != ARTEFACT_UNINITIATED)
        uninitiate_artefact(database, result_artefact->artefactID);
    }

    // now merge them
    merge_artefacts(database,
                    key_artefact->caveID,
                    key_artefact->artefactID,
                    lock_artefact->artefactID,
                    result_artefact->artefactID);
    return 1;
  }

  return 0;
}



/** merge_artefacts_general ...
 *  throws exceptions, if needed conditions are not as they should have been
 */
int merge_artefacts_general(MYSQL *database,
                      const struct Artefact *key_artefact,
                            struct Artefact *lock_artefact,
                            struct Artefact *result_artefact)
{
  MYSQL_RES *result;
  MYSQL_ROW row;
  MYSQL_RES *temp_result;
  MYSQL_ROW temp_row;

  // now get possible merging formulas
  result = mysql_query_fmt(database, "SELECT * FROM Artefact_merge_general "
                           "WHERE keyClassID = %d",
                           key_artefact->artefactClassID);

  // check for a suitable merging
  while ((row = mysql_fetch_row(result)) != NULL)
  {
    // special rules:

    // lockClassID = 0
    // no lock artefact needed

    // keyClassID = lockClassID
    // unlocks if at least one other initiated artefact
    // of the same class exists

    // resultClassID = 0
    // key artefact and one present instance of the lockClass vanish


    // lock artefact
    lock_artefact->artefactClassID =
	mysql_get_int_field(result, row, "lockClassID");

    if (lock_artefact->artefactClassID == 0)
      break;

    // implicit checks:
    // - lock artefact has to be different from the key artefact
    // - lock artefact has to be in the same cave as the key artefact
    // - lock artefact has to be initiated
    // - lock artefact has be of the specified class
    temp_result = mysql_query_fmt(database,
				  "SELECT artefactID FROM Artefact "
				  "WHERE artefactID != %d "
				  "AND artefactClassID = %d "
				  "AND caveID = %d "
				  "AND initiated = %d",
				  key_artefact->artefactID,
				  lock_artefact->artefactClassID,
				  key_artefact->caveID,
				  ARTEFACT_INITIATED);

    // get the "first" one (whatever first means)
    temp_row = mysql_fetch_row(temp_result);

    // is there a suitable lock artefact?
    if (temp_row)
    {
      lock_artefact->artefactID =
	  mysql_get_int_field(temp_result, temp_row, "artefactID");
      lock_artefact->caveID     = key_artefact->caveID;
      lock_artefact->initiated  = ARTEFACT_INITIATED;
      break;
    }
  }

  if (row)
  {
    // result artefact
    result_artefact->artefactClassID =
	mysql_get_int_field(result, row, "resultClassID");

    if (result_artefact->artefactClassID != 0)
      result_artefact->artefactID =
	  new_artefact(database, result_artefact->artefactClassID);

    // now merge them
    merge_artefacts(database,
                    key_artefact->caveID,
                    key_artefact->artefactID,
                    lock_artefact->artefactID,
                    result_artefact->artefactID);
    return 1;
  }

  return 0;
}
