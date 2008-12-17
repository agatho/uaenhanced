/*
 * artefact.h - handle artefacts
 * Copyright (c) 2003 Marcus Lunzenauer
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#ifndef _ARTEFACT_H_
#define _ARTEFACT_H_

#include <mysql/mysql.h>
#include "effect_list.h"

#define ARTEFACT_INITIATING	 -1
#define ARTEFACT_UNINITIATED	0
#define ARTEFACT_INITIATED	  1

typedef struct Artefact_class {
  int         artefactClassID;
  const char* name;
  const char* resref;
  const char* description;
  const char* description_initiated;
  int         initiationID;
  double      effect[MAX_EFFECT];
  double      destroy_chance;
} Artefact_class;

typedef struct Artefact {
  int artefactID;
  int artefactClassID;
  int caveID;
  int initiated;
} Artefact;

void get_artefact_by_id(MYSQL *database, int artefactID, struct Artefact *artefact);
void get_artefact_class_by_id(MYSQL *database, int artefactClassID, struct Artefact_class *artefact_class);

void put_artefact_into_cave(MYSQL *database, int artefactID, int caveID);
void remove_artefact_from_cave(MYSQL *database, int artefactID);

void initiate_artefact(MYSQL *database, int artefactID);
void uninitiate_artefact(MYSQL *database, int artefactID);

void apply_effects_to_cave(MYSQL *database, int artefactID);
void remove_effects_from_cave(MYSQL *database, int artefactID);

int merge_artefacts_general(MYSQL *database, const struct Artefact *key_artefact, struct Artefact *lock_artefact, struct Artefact *result_artefact);
int merge_artefacts_special(MYSQL *database, const struct Artefact *key_artefact, struct Artefact *lock_artefact, struct Artefact *result_artefact);

int new_artefact(MYSQL *database, int artefactClassID);

void merge_artefacts(MYSQL *database, int caveID, int keyArtefactID, int lockArtefactID, int resultArtefactID);

#endif /* _ARTEFACT_H_ */
