/*
 * fortification_handler.c - process defense system events
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include "logging.h"
#include "mysql_tools.h"
#include "game_rules.h"


void quest_check_win (MYSQL *database, int questID, int *playerID, int *event_movementID, char *timestamp, int *target_caveID)
{
    MYSQL_ROW row;
    MYSQL_RES *result;
    dstring_t *ds = dstring_new("");
    
        debug(DEBUG_TICKER, "Checking winning conditions for quest %d", questID);

        // Getting winning conditions
        const char *winningCond;

        dstring_set(ds, "SELECT questWonIf FROM quests WHERE questID = %d",questID);
        result = mysql_query_dstring(database, ds);
        row = mysql_fetch_row(result);

        if(row) {
          winningCond = mysql_get_string_field(result, row, "questWonIf");
          
          // Parsing the condition
          debug(DEBUG_TICKER, "Winning condition for quest %d is %s", questID, winningCond);
          
          if (parse_quest(winningCond, playerID, database, event_movementID)) {

            debug(DEBUG_TICKER, "Player has won quest %d at %s. Need to enter it in quest_succeeded", questID, timestamp);
            dstring_set(ds, "INSERT INTO quests_succeeded SET playerID = %d, questID = %d, timestamp = '%s'", *playerID, questID, timestamp);
            mysql_query_dstring(database, ds);
            
            debug(DEBUG_TICKER, "Deleting it from quests_active");
            dstring_set(ds, "DELETE FROM quests_active WHERE playerID = %d AND questID = %d", *playerID, questID);
            mysql_query_dstring(database, ds);
            
            // Can there be only one winner of this quest?
            debug(DEBUG_TICKER, "checking if there can be only one winner");
            dstring_set(ds, "SELECT onlyOneWinner FROM quests WHERE questID = %d", questID);
            result = mysql_query_dstring(database, ds);
            row = mysql_fetch_row(result);

            if(row) {
              int oneWinner = 0;
              oneWinner = mysql_get_int_field(result, row, "onlyOneWinner");
              if (oneWinner) {
                debug(DEBUG_TICKER, "Yes only one winner. Setting all others to failed");
                
                // So we set the quest to finished
                dstring_set(ds, "UPDATE quests SET quest_finished = 1 WHERE questID = %d", questID);
                mysql_query_dstring(database, ds);
                
                dstring_set(ds, "SELECT playerID FROM quests_active WHERE questID = %d AND playerID != %d", questID, *playerID);
                result = mysql_query_dstring(database, ds);
                int num_rows = mysql_num_rows(result);
                int playerID[num_rows];
                int i = 0;
            
                for (i = 0; i < num_rows; i++) {
                  MYSQL_ROW row = mysql_fetch_row(result);
                  playerID[i] = mysql_get_int_field(result, row, "playerID");
    
                  debug(DEBUG_TICKER, "playerID is: %d", playerID[i]);
                }
                for (i = 0; i < num_rows; i++) {
                  dstring_set(ds, "INSERT INTO quests_failed SET playerID = %d, questID = %d, timestamp = '%s'", playerID[i], questID, timestamp);
                  mysql_query_dstring(database, ds);
    
                  debug(DEBUG_TICKER, "Inserted into quests_failed: playerID %d", playerID[i]);
                }
                
                for (i = 0; i < num_rows; i++) {
                  dstring_set(ds, "DELETE FROM quests_active WHERE playerID = %d AND questID = %d", playerID[i], questID);
                  mysql_query_dstring(database, ds);
    
                  debug(DEBUG_TICKER, "Deleted from quests_active: playerID %d", playerID[i]);
                }
                
// Should we set the quest impossible for all the other players?
// Hm, maybe. If not comment the following part of code
dstring_set(ds, "SELECT playerID FROM player WHERE playerID != %d ", *playerID);
for (i = 0; i < num_rows; i++) {
  dstring_append(ds, "AND playerID != %d ", playerID[i]);
}
result = mysql_query_dstring(database, ds);
num_rows = mysql_num_rows(result);
int playerID_player[num_rows];
for (i = 0; i < num_rows; i++) {
  MYSQL_ROW row = mysql_fetch_row(result);
  playerID_player[i] = mysql_get_int_field(result, row, "playerID");
}
for (i = 0; i < num_rows; i++) {
  dstring_set(ds, "INSERT INTO quests_aborted SET playerID = %d, questID = %d, timestamp = '%s'", playerID_player[i], questID, timestamp);
  mysql_query_dstring(database, ds);
  debug(DEBUG_TICKER, "Inserted into quests_aborted: playerID %d", playerID[i]);
}


                
                
              } else {
                debug(DEBUG_TICKER, "No there can be many winners");
              }
            } else {
              debug(DEBUG_TICKER, "Database failure.");
            }
          
          // We need to check if there are any further quests in this cave
          // we should make visible to this player
          
          dstring_set(ds, "SELECT * FROM quests_in_cave WHERE caveID = %d ORDER BY ID",*target_caveID);
    
          result = mysql_query_dstring(database, ds);

          int num_rows = mysql_num_rows(result);
    
          if (num_rows < 1) {
            debug(DEBUG_TICKER, "No quests at all, so no quest there.");
            return;
          } else {
            debug(DEBUG_TICKER, "%d quests in Cave.", mysql_num_rows(result));
          }
    
          int i;
          int questarrayID[num_rows];
          for (i = 0; i < num_rows; i++) {
            MYSQL_ROW row = mysql_fetch_row(result);
            questarrayID[i] = mysql_get_int_field(result, row, "questID");
    
            debug(DEBUG_TICKER, "questID is: %d", questarrayID[i]);

          }
          mysql_data_seek(result, 0);	/* move the row pointer back */
          
          for (i = 0; i < num_rows; i++) {
          
// Is the quest already finished?
// then we dont need to process anything
int quest_finished = 0;
questID = questarrayID[i];
debug(DEBUG_TICKER, "Getting the finished flag for Quest %d", questID);
dstring_set(ds, "SELECT quest_finished FROM quests WHERE questID = %d",questID);
result = mysql_query_dstring(database, ds);
row = mysql_fetch_row(result);
if(row) quest_finished = mysql_get_int_field(result, row, "quest_finished");
if (!quest_finished) {

// ok so now there is a quest in the cave
// and we have the player currently in the cave
// so we should now check, if he already knows the quest

debug(DEBUG_TICKER, "Checking if the player already knows the quest");
int questknown = 0;
questID = questarrayID[i];

    dstring_set(ds, "SELECT * FROM quests_active WHERE questID = %d AND playerID = %d",questID, *playerID);
    result = mysql_query_dstring(database, ds);
    if (mysql_num_rows(result)) {
      questknown = 1;
    } else {
    
      dstring_set(ds, "SELECT * FROM quests_failed WHERE questID = %d AND playerID = %d",questID, *playerID);
      result = mysql_query_dstring(database, ds);
      if (mysql_num_rows(result)) {
        questknown = 1;
      } else {
    
        dstring_set(ds, "SELECT * FROM quests_succeeded WHERE questID = %d AND playerID = %d",questID, *playerID);
        result = mysql_query_dstring(database, ds);
        if (mysql_num_rows(result)) {
          questknown = 1;
        } else {
    
          dstring_set(ds, "SELECT * FROM quests_aborted WHERE questID = %d AND playerID = %d",questID, *playerID);
          result = mysql_query_dstring(database, ds);
          if (mysql_num_rows(result)) {
            questknown = 1;
          }
        }
      }
    }
    
    if (questknown) {
      debug(DEBUG_TICKER, "Yes player already knows the quest");
      
      debug(DEBUG_TICKER, "Checking if the Player has won the quest");

      dstring_set(ds, "SELECT * FROM quests_succeeded WHERE questID = %d AND playerID = %d",questID, *playerID);
      result = mysql_query_dstring(database, ds);
      if (mysql_num_rows(result)) {
        debug(DEBUG_TICKER, "Yes the Player has won the quest %d", questID);
      } else {
        debug(DEBUG_TICKER, "No the Player hasnt won the quest %d yet", questID);
          
      } // end if Not won check winning
      
      
      
      
    } else {
        debug(DEBUG_TICKER, "No the Player doesnt know the quest %d", questID);

// he doesnt know the quest.
// but should he already know about it?
// this is the case if he had in the previous
// quest succeeded.
// 1st we need the previousQuestID of this quest

int prevQuestID = 0;

debug(DEBUG_TICKER, "Getting the prevQuestID from Quest %d", questID);
dstring_set(ds, "SELECT prevQuestID FROM quests WHERE questID = %d",questID);
result = mysql_query_dstring(database, ds);
row = mysql_fetch_row(result);
if(row) prevQuestID = mysql_get_int_field(result, row, "prevQuestID");

// Is prevQuestID == 0?
// if it 0 its possibly a starting Quest

if (prevQuestID > 0) {

// We should now check if he already won the prevQuest
// than he should know about it. Otherwise not

debug(DEBUG_TICKER, "Checking if player has won the prevQuest");
dstring_set(ds, "SELECT * FROM quests_succeeded WHERE questID = %d AND playerID = %d",prevQuestID, *playerID);
result = mysql_query_dstring(database, ds);
row = mysql_fetch_row(result);
if (mysql_num_rows(result)) {

  debug(DEBUG_TICKER, "Yes player has won prevQuest");
          
// he should now be informed of the quest
// and we need to send him a message

// sending message to player

// Getting title and message
    const char *message;
    const char *title;
debug(DEBUG_TICKER, "Trying to get title from quest.");

    dstring_set(ds, "SELECT title FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) title = mysql_get_string_field(result, row, "title");
      else {
      debug(DEBUG_TICKER, "Databse error. This should never happen.");
    }

debug(DEBUG_TICKER, "Trying to get message from quest.");

    dstring_set(ds, "SELECT description FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) message = mysql_get_string_field(result, row, "description");
      else {
      debug(DEBUG_TICKER, "Databse error. This should never happen.");
    }

make_timestamp(timestamp, time(NULL));
// sending message to player
dstring_set(ds, "INSERT INTO message SET recipientID = %d, senderID = 0, messageTime = '%s', messageClass = 30, messageSubject = '%s', messageText = '%s'", *playerID, timestamp, title, message);
mysql_query_dstring(database, ds);


// now we need to unlock the quest to the player (setting the quest active for this player)

// unlock quest to player
debug(DEBUG_TICKER, "Making this quest active to this player.");

make_timestamp(timestamp, time(NULL));
dstring_set(ds, "INSERT INTO quests_active SET questID = %d, playerID = %d, timestamp = %s", questID, *playerID, timestamp);
mysql_query_dstring(database, ds);


// and we check if there are any further quest caves to make
// visible to this player

// checking further quest caves and parsing them and stuff

    const char *string;
debug(DEBUG_TICKER, "Are there any caves to make visible to this player?");

    dstring_set(ds, "SELECT make_caves_visible FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) {
    string = mysql_get_string_field(result, row, "make_caves_visible");

    
    debug(DEBUG_TICKER, "making caves visible is: %s", string);
// and make them visible to this player
debug(DEBUG_TICKER, "making caves visible to this player");
    
// parsing that string
	char *foo;//points to strtok() results.
	int i;

    i = strlen(string);
     char make_caves_visible[25];
     memset(make_caves_visible, 65, i);
     memcpy(make_caves_visible, string, i);
     char *pmake;
     char *ppmake;
     pmake = make_caves_visible;
     ppmake = pmake+i;
     memcpy(ppmake, "\0ST", 4);

	//parse by words
	foo=strtok(make_caves_visible,",");//parse values delimited by spaces, commas, and periods.
	if(foo)	{
      dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %s", *playerID, foo);
      mysql_query_dstring(database, ds);
    }

	do {
		foo=strtok(NULL,",");//NULL tells it to use the last value.  Not threadsafe.

	    if(foo)	{
          dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %s", *playerID, foo);
          mysql_query_dstring(database, ds);
        }
	}while(foo);
	
	} // end if make caves visible

} // end if player has won prevQuest

} // end if prevQuest > 0

} // end if player knows quest

} // end quest not finished

} // end for each quest in cave
          
          
          
          
          
          } else {
            debug(DEBUG_TICKER, "Not sufficient to win the quest.");
          }
          
          
        }
          else {
            debug(DEBUG_TICKER, "Databse error. This should never happen.");
          }
} // end of function


const char *parse_part (const char *function, int first_call,  int *playerID, MYSQL *database, int *event_movementID)
{
  debug(DEBUG_TICKER, "Entering function parse_part");

// Splitting that string
    int resources[MAX_RESOURCE];
    int units[MAX_RESOURCE];

    int resource = 0;
    int unit = 0;
    
    int fromplayer = 0;
    int movement = 0;
    int tocave = 0;
    
    MYSQL_ROW row;
    MYSQL_RES *result;
    
    char *foo;//points to strtok() results.
	int i;
    dstring_t *ds = dstring_new("");
    dstring_t *ds2 = dstring_new("");
    
     i = strlen(function);
     char part[100];
     memset(part, 65, i);
     memcpy(part, function, i);
     char *pmake;
     char *ppmake;
     pmake = part;
     ppmake = pmake+i;
     memcpy(ppmake, "\0ST", 4);
     

     
	//parse by words
	foo=strtok(part,".");//parse values delimited by spaces, commas, and periods.
	if(foo)	{
    // Call parse function (foo)
      debug(DEBUG_TICKER, "Part: %s", foo);
      if (strcmp(foo, "MOVEMENT") == 0) {
        if (!first_call) dstring_append(ds, "AND ");
        dstring_append(ds, "event_movement WHERE movementID = ");
        movement = 1;
      }
      if (strcmp(foo, "RESOURCE") == 0) {
        if (!first_call) dstring_append(ds, "AND ");
        resource = 1;
      }
      if (strcmp(foo, "UNIT") == 0) {
        if (!first_call) dstring_append(ds, "AND ");
        unit = 1;
      }
      if (strcmp(foo, "AMOUNT") == 0) {
      }
      if (strcmp(foo, "TOCAVE") == 0) {
        tocave = 1;
      }
      if (strcmp(foo, "FROMPLAYER") == 0) {
        fromplayer = 1;
      }
      if (strcmp(foo, "ARTEFACT") == 0) {
        if (!first_call) dstring_append(ds, "AND ");
        dstring_append(ds, "artefactID = ");
      }
      
    }

	do {
		foo=strtok(NULL,".");//NULL tells it to use the last value.  Not threadsafe.

	    if(foo)	{
          debug(DEBUG_TICKER, "Part: %s", foo);
          
          
          if (resource) {
          int q = atoi(foo);
          dstring_append(ds, "%s = ", resourceTypeList[q].dbFieldName);
          }
          if (unit) {
          int q = atoi(foo);
          dstring_append(ds, "%s = ", unitTypeList[q].dbFieldName);
          }
          
          if (strcmp(foo, "player") == 0) {
            if (fromplayer) {

            // Asking SQL if source_caveID belongs to player
            if (!first_call) dstring_append(ds, "AND ");
            dstring_append(ds, "(");

            dstring_set(ds2, "SELECT caveID FROM cave WHERE playerID = %d", *playerID);
            result = mysql_query_dstring(database, ds2);
            int num_rows = mysql_num_rows(result);
            int playerCave = 0;
            
            int first_call_player = 1;
            for (i = 0; i < num_rows; i++) {
              MYSQL_ROW row = mysql_fetch_row(result);
              playerCave = mysql_get_int_field(result, row, "caveID");
    
              debug(DEBUG_TICKER, "playerCave is: %d", playerCave);
              if (!first_call_player) dstring_append(ds, "OR ");
              dstring_append(ds, "source_caveID = %d ", playerCave);
              first_call_player = 0;
            }
              mysql_data_seek(result, 0);	/* move the row pointer back */
              dstring_append(ds, ")");
           } // end if fromplayer
           
            if (tocave) {

            // Asking SQL if source_caveID belongs to player
            if (!first_call) dstring_append(ds, "AND ");
            dstring_append(ds, "(");

            dstring_set(ds2, "SELECT caveID FROM cave WHERE playerID = %d", *playerID);
            result = mysql_query_dstring(database, ds2);
            int num_rows = mysql_num_rows(result);
            int playerCave = 0;
            
            int first_call_player = 1;
            for (i = 0; i < num_rows; i++) {
              MYSQL_ROW row = mysql_fetch_row(result);
              playerCave = mysql_get_int_field(result, row, "caveID");
    
              debug(DEBUG_TICKER, "playerCave is: %d", playerCave);
              if (!first_call_player) dstring_append(ds, "OR ");
              dstring_append(ds, "target_caveID = %d ", playerCave);
              first_call_player = 0;
            }
              mysql_data_seek(result, 0);	/* move the row pointer back */
              dstring_append(ds, ")");
           } // end if fromplayer
          } // enf if player

          
          if (!resource && !unit && !fromplayer && !tocave) dstring_append(ds, "%s ", foo);
          
          if (movement) {
            dstring_append(ds, "AND event_movementID = %d ", event_movementID);
          }
          
        }
	}while(foo);


  debug(DEBUG_TICKER, "Leaving function parse_part");
    return dstring_str(ds);
}


// This function parses the quest_win_function and returns the SQL
int parse_quest (const char *function, int *playerID, MYSQL *database, int *event_movementID)
{
  debug(DEBUG_TICKER, "Entering function parse_quest");

// Splitting that string
    int first_call = 1;
    char *foo;//points to strtok() results.
	int i;
    dstring_t *ds = dstring_new("");
    
    MYSQL_ROW row;
    MYSQL_RES *result;
	
	dstring_append(ds, "SELECT * FROM ");

     i = strlen(function);
     int pmake_len;
     char win_cond[100];
     char win_cond2[100];
     memset(win_cond, 65, i);
     memcpy(win_cond, function, i);
     memset(win_cond2, 65, i);
     memcpy(win_cond2, win_cond, i);
     char *pmake;
     char *ppmake;
     pmake = win_cond;
     ppmake = pmake+i;
     memcpy(ppmake, "\0\0\0\0", 4);
     pmake = win_cond2;
     ppmake = pmake+i;
     memcpy(ppmake, "\0\0\0\0", 4);
     int length = 0;

	//parse by words
	do {
	foo=strtok(win_cond,", ");//parse values delimited by spaces, commas, and periods.

	  if(foo)	{
      // Call parse function (foo)
        debug(DEBUG_TICKER, "Condition: %s", foo);
   	    dstring_append(ds, "%s ", parse_part(foo, first_call, playerID, database, event_movementID));
   	    
   	    length = length+strlen(foo)+2;
   	    pmake_len = strlen(foo);

   	    pmake = win_cond2;
   	    ppmake = pmake+length;
   	    memcpy(win_cond, ppmake, strlen(win_cond2));

      }
      first_call = 0;
    }while(foo);
    
    

debug(DEBUG_TICKER, "SQL: %s", dstring_str(ds));

  debug(DEBUG_TICKER, "Leaving function parse_quest");

result = mysql_query_dstring(database, ds);
int num_rows = mysql_num_rows(result);
if (num_rows > 0) return 1;
  else return 0;
}


int setCaveVisibleToPlayer(MYSQL *database, int *target_caveID, int *playerID)
{
    MYSQL_RES *result;
    dstring_t *ds;
    
    ds = dstring_new("INSERT INTO quests_vis_to_player SET caveID = %d, playerID = %d", *target_caveID, *playerID);
    
    result = mysql_query_dstring(database, ds);
    
    if(!result) return 0;
      else {
        debug(DEBUG_TICKER, "Database error: Setting cave visible. caveID: %d, playerID: %d", *target_caveID, *playerID);
        return 1;
      }

}


int caveIsInvisibleToPlayer(MYSQL *database, int *target_caveID, int *playerID)
{
    MYSQL_RES *result;
    dstring_t *ds;
    
    ds = dstring_new("SELECT * FROM quests_vis_to_player WHERE caveID = %d AND playerID = %d", *target_caveID, *playerID);
    
    result = mysql_query_dstring(database, ds);

    if (mysql_num_rows(result)) return 0;
    
    return 1;
}


int caveIsInvisible(MYSQL *database, int *target_caveID)
{
    MYSQL_ROW row;
    MYSQL_RES *result;
    int invisible = 0;
    dstring_t *ds;
    
    ds = dstring_new("SELECT invisible_to_non_quest_players FROM cave WHERE caveID = %d", *target_caveID);
    
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);
    
    if(row) invisible = mysql_get_int_field(result, row, "invisible_to_non_quest_players");
      else {
        debug(DEBUG_TICKER, "Database error: Getting quest cave");
        return -1;
      }
      
    if (!invisible) return 0;
    
    return 1;
}


int isCaveQuestCave (MYSQL *database, int *target_caveID)
{
    MYSQL_ROW row;
    MYSQL_RES *result;
    int quest_cave = 0;
    dstring_t *ds;
    
    ds = dstring_new("SELECT quest_cave FROM cave WHERE caveID = %d", *target_caveID);
    
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);
    
    if(row) quest_cave = mysql_get_int_field(result, row, "quest_cave");
      else {
        debug(DEBUG_TICKER, "Database error: Getting quest cave");
        return -1;
      }
      
    if (!quest_cave) return 0;
    
    return 1;
}


void process_quest_visit (MYSQL *database, int *target_caveID, int *playerID)
{
    MYSQL_ROW row;
    MYSQL_RES *result;
    
    char timestamp[TIMESTAMP_LEN];
    int quest_cave = 0;
    int questID = 0;

    dstring_t *ds;

//    debug(DEBUG_TICKER, "entering function process_quest_visit()");
    
//    debug(DEBUG_TICKER, "target_caveID is: %d", *target_caveID);
    
//    debug(DEBUG_TICKER, "checking if target_cave is a quest cave");
    

    ds = dstring_new("SELECT * FROM cave WHERE caveID = %d",*target_caveID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);
    if(row) quest_cave = mysql_get_int_field(result, row, "quest_cave");
      else {
        debug(DEBUG_TICKER, "Database error: Getting quest cave");
        return;
      }
//    debug(DEBUG_TICKER, "quest_cave is: %d", quest_cave);
    if (!quest_cave) {
//      debug(DEBUG_TICKER, "No quest cave, so no quest there.");
//      debug(DEBUG_TICKER, "leaving function process_quest_visit()");
      return;
    }

    debug(DEBUG_TICKER, "entering function process_quest_visit()");
    debug(DEBUG_TICKER, "getting the quest from cave with ID %d",*target_caveID);
    
// Our SQL

    dstring_set(ds, "SELECT * FROM quests_in_cave WHERE caveID = %d ORDER BY ID",*target_caveID);
    
    result = mysql_query_dstring(database, ds);

    int num_rows = mysql_num_rows(result);
    
    if (num_rows < 1) {
      debug(DEBUG_TICKER, "No quests at all, so no quest there.");
      debug(DEBUG_TICKER, "leaving function process_quest_visit()");
      return;
    } else {
      debug(DEBUG_TICKER, "%d quests in Cave.", mysql_num_rows(result));
    }
    
    int i;
    int questarrayID[num_rows];
    for (i = 0; i < num_rows; i++) {
    MYSQL_ROW row = mysql_fetch_row(result);
        questarrayID[i] = mysql_get_int_field(result, row, "questID");
    
    debug(DEBUG_TICKER, "questID is: %d", questarrayID[i]);

    }
     mysql_data_seek(result, 0);	/* move the row pointer back */


for (i = 0; i < num_rows; i++) {

// Is the quest already finished?
// then we dont need to process anything
int quest_finished = 0;
questID = questarrayID[i];
debug(DEBUG_TICKER, "Getting the finished flag for Quest %d", questID);
dstring_set(ds, "SELECT quest_finished FROM quests WHERE questID = %d",questID);
result = mysql_query_dstring(database, ds);
row = mysql_fetch_row(result);
if(row) quest_finished = mysql_get_int_field(result, row, "quest_finished");
if (!quest_finished) {

// ok so now there is a quest in the cave
// and we have the player currently in the cave
// so we should now check, if he already knows the quest

debug(DEBUG_TICKER, "Checking if the player already knows the quest");
int questknown = 0;
questID = questarrayID[i];

    dstring_set(ds, "SELECT * FROM quests_active WHERE questID = %d AND playerID = %d",questID, *playerID);
    result = mysql_query_dstring(database, ds);
    if (mysql_num_rows(result)) {
      questknown = 1;
    } else {
    
      dstring_set(ds, "SELECT * FROM quests_failed WHERE questID = %d AND playerID = %d",questID, *playerID);
      result = mysql_query_dstring(database, ds);
      if (mysql_num_rows(result)) {
        questknown = 1;
      } else {
    
        dstring_set(ds, "SELECT * FROM quests_succeeded WHERE questID = %d AND playerID = %d",questID, *playerID);
        result = mysql_query_dstring(database, ds);
        if (mysql_num_rows(result)) {
          questknown = 1;
        } else {
    
          dstring_set(ds, "SELECT * FROM quests_aborted WHERE questID = %d AND playerID = %d",questID, *playerID);
          result = mysql_query_dstring(database, ds);
          if (mysql_num_rows(result)) {
            questknown = 1;
          }
        }
      }
    }
    
    if (questknown) {
      debug(DEBUG_TICKER, "Yes player already knows the quest");
      
      debug(DEBUG_TICKER, "Checking if the Player has won the quest");

      dstring_set(ds, "SELECT * FROM quests_succeeded WHERE questID = %d AND playerID = %d",questID, *playerID);
      result = mysql_query_dstring(database, ds);
      if (mysql_num_rows(result)) {
        debug(DEBUG_TICKER, "Yes the Player has won the quest %d", questID);
      } else {
        debug(DEBUG_TICKER, "No the Player hasnt won the quest %d yet", questID);

      } // end if Not won check winning
      
      
      
      
    } else {
        debug(DEBUG_TICKER, "No the Player doesnt know the quest %d", questID);

// he doesnt know the quest.
// but should he already know about it?
// this is the case if he had in the previous
// quest succeeded.
// 1st we need the previousQuestID of this quest

int prevQuestID = 0;

debug(DEBUG_TICKER, "Getting the prevQuestID from Quest %d", questID);
dstring_set(ds, "SELECT prevQuestID FROM quests WHERE questID = %d",questID);
result = mysql_query_dstring(database, ds);
row = mysql_fetch_row(result);
if(row) prevQuestID = mysql_get_int_field(result, row, "prevQuestID");

// Is prevQuestID == 0?
// if it 0 its possibly a starting Quest

if (prevQuestID > 0) {

// We should now check if he already won the prevQuest
// than he should know about it. Otherwise not

debug(DEBUG_TICKER, "Checking if player has won the prevQuest");
dstring_set(ds, "SELECT * FROM quests_succeeded WHERE questID = %d AND playerID = %d",prevQuestID, *playerID);
result = mysql_query_dstring(database, ds);
row = mysql_fetch_row(result);
if (mysql_num_rows(result)) {

debug(DEBUG_TICKER, "Yes player has won prevQuest");


// he should now be informed of the quest
// and we need to send him a message

// sending message to player

// Getting title and message
    const char *message;
    const char *title;
debug(DEBUG_TICKER, "Trying to get title from quest.");

    dstring_set(ds, "SELECT title FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) title = mysql_get_string_field(result, row, "title");
      else {
      debug(DEBUG_TICKER, "Databse error. This should never happen.");
    }

debug(DEBUG_TICKER, "Trying to get message from quest.");

    dstring_set(ds, "SELECT description FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) message = mysql_get_string_field(result, row, "description");
      else {
      debug(DEBUG_TICKER, "Databse error. This should never happen.");
    }

make_timestamp(timestamp, time(NULL));
// sending message to player
dstring_set(ds, "INSERT INTO message SET recipientID = %d, senderID = 0, messageTime = '%s', messageClass = 30, messageSubject = '%s', messageText = '%s'", *playerID, timestamp, title, message);
mysql_query_dstring(database, ds);


// now we need to unlock the quest to the player (setting the quest active for this player)

// unlock quest to player
debug(DEBUG_TICKER, "Making this quest active to this player.");

make_timestamp(timestamp, time(NULL));
dstring_set(ds, "INSERT INTO quests_active SET questID = %d, playerID = %d, timestamp = %s", questID, *playerID, timestamp);
mysql_query_dstring(database, ds);


// and we check if there are any further quest caves to make
// visible to this player

// checking further quest caves and parsing them and stuff

    const char *string;
debug(DEBUG_TICKER, "Are there any caves to make visible to this player?");

    dstring_set(ds, "SELECT make_caves_visible FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) {
    string = mysql_get_string_field(result, row, "make_caves_visible");
//dstring_set(ds, "SELECT * FROM quests WHERE questID = %d", questID);
//    result = mysql_query_dstring(database, ds);
//    row = mysql_fetch_row(result);
    
//      if(row) {
//      string = mysql_get_string_field(result, row, "make_caves_visible");
//    if(row) make_caves_visible = mysql_get_int_field(result, row, "make_caves_visible");

    
    debug(DEBUG_TICKER, "making caves visible is: %s", string);
// and make them visible to this player
debug(DEBUG_TICKER, "making caves visible to this player");
    
// parsing that string
	char *foo;//points to strtok() results.
	int i;

    i = strlen(string);
     char make_caves_visible[25];
     memset(make_caves_visible, 65, i);
     memcpy(make_caves_visible, string, i);
     char *pmake;
     char *ppmake;
     pmake = make_caves_visible;
     ppmake = pmake+i;
     memcpy(ppmake, "\0ST", 4);

	//parse by words
	foo=strtok(make_caves_visible,",");//parse values delimited by spaces, commas, and periods.
	if(foo)	{
      dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %s", *playerID, foo);
      mysql_query_dstring(database, ds);
    }

	do {
		foo=strtok(NULL,",");//NULL tells it to use the last value.  Not threadsafe.

	    if(foo)	{
          dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %s", *playerID, foo);
          mysql_query_dstring(database, ds);
        }
	}while(foo);
	
//dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %d", *playerID, make_caves_visible);
//mysql_query_dstring(database, ds);

	} // end if make caves visible


// Now that this was handled we need to check if the quest was finished, failed or whatever
// oh my god I think this is too complicated in c for me
// maybe we should run some simple php script for this

// we need to check if this quest was won by this player
// and if we should set the quest for other players to be aborted
// if this player has won, he should get some present.
// or another quest or whatever.
// I have to think about this some more.



} // end if player has won prevQuest

} // end if prevQuest > 0
  else {
  // must than be a starting quest
  debug(DEBUG_TICKER, "seems this is a starting quest");
  
  // he should now be informed of the quest
// and we need to send him a message

// sending message to player

// Getting title and message
    const char *message;
    const char *title;
debug(DEBUG_TICKER, "Trying to get title from quest.");

    dstring_set(ds, "SELECT title FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) title = mysql_get_string_field(result, row, "title");
      else {
      debug(DEBUG_TICKER, "Databse error. This should never happen.");
    }

debug(DEBUG_TICKER, "Trying to get message from quest.");

    dstring_set(ds, "SELECT description FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) message = mysql_get_string_field(result, row, "description");
      else {
      debug(DEBUG_TICKER, "Databse error. This should never happen.");
    }

make_timestamp(timestamp, time(NULL));
// sending message to player
dstring_set(ds, "INSERT INTO message SET recipientID = %d, senderID = 0, messageTime = '%s', messageClass = 30, messageSubject = '%s', messageText = '%s'", *playerID, timestamp, title, message);
mysql_query_dstring(database, ds);


// now we need to unlock the quest to the player (setting the quest active for this player)

// unlock quest to player
debug(DEBUG_TICKER, "Making this quest active to this player.");

make_timestamp(timestamp, time(NULL));
dstring_set(ds, "INSERT INTO quests_active SET questID = %d, playerID = %d, timestamp = %s", questID, *playerID, timestamp);
mysql_query_dstring(database, ds);

// and we check if there are any further quest caves to make
// visible to this player

// checking further quest caves and parsing them and stuff

    const char *string;
debug(DEBUG_TICKER, "Are there any caves to make visible to this player?");

    dstring_set(ds, "SELECT make_caves_visible FROM quests WHERE questID = %d",questID);
    result = mysql_query_dstring(database, ds);
    row = mysql_fetch_row(result);

    if(row) {
    string = mysql_get_string_field(result, row, "make_caves_visible");
//dstring_set(ds, "SELECT * FROM quests WHERE questID = %d", questID);
//    result = mysql_query_dstring(database, ds);
//    row = mysql_fetch_row(result);
    
//      if(row) {
//      string = mysql_get_string_field(result, row, "make_caves_visible");
//    if(row) make_caves_visible = mysql_get_int_field(result, row, "make_caves_visible");

    
    debug(DEBUG_TICKER, "making caves visible is: %s", string);
// and make them visible to this player
debug(DEBUG_TICKER, "making caves visible to this player");
    
// parsing that string
	char *foo;//points to strtok() results.
	int i;

    i = strlen(string);
     char make_caves_visible[25];
     memset(make_caves_visible, 65, i);
     memcpy(make_caves_visible, string, i);
     char *pmake;
     char *ppmake;
     pmake = make_caves_visible;
     ppmake = pmake+i;
     memcpy(ppmake, "\0ST", 4);

	//parse by words
	foo=strtok(make_caves_visible,",");//parse values delimited by spaces, commas, and periods.
	if(foo)	{
      dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %s", *playerID, foo);
      mysql_query_dstring(database, ds);
    }

	do {
		foo=strtok(NULL,",");//NULL tells it to use the last value.  Not threadsafe.

	    if(foo)	{
          dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %s", *playerID, foo);
          mysql_query_dstring(database, ds);
        }
	}while(foo);
	
//dstring_set(ds, "INSERT INTO quests_vis_to_player SET playerID = %d, caveID = %d", *playerID, make_caves_visible);
//mysql_query_dstring(database, ds);

	} // end if make caves visible



} // end starting quest  

} // end if player knows quest

} // end if quest not finished

} // end for each quest in cave
      
    debug(DEBUG_TICKER, "leaving function quest_handler()");
}
