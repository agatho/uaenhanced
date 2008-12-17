/*
 * ticker.c - ticker daemon process
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
#include <mysql/mysql.h>
#include <winsock.h>
#include <signal.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/types.h>
//#include <unistd.h>	/* chdir, getopt, getpid, usleep */

#include "config.h"
#include "event_handler.h"
#include "except.h"
#include "game_rules.h"
#include "logging.h"
#include "memory.h"
#include "message.h"
#include "messagelog.h"
#include "mysql_tools.h"
#include "resource_ticker.h"

#ifdef DEBUG_MALLOC
#include <gc/leak_detector.h>
#endif

//#define TICKER_LOADAVG_TIME	300

/* ticker configuration parameters */

static const char *db_host;
static const char *db_name;
static const char *db_user;
static const char *db_passwd;

static const char *ticker_home;
static const char *pid_file;
static const char *debug_logfile;
static const char *error_logfile;
static const char *msg_logfile;
static const char *shutdown_file;

static long sleep_time;
static long ticker_load_time;

/* ticker static variables */

static const char *config_file = "ticker.conf";

static volatile int finish;
static volatile int reload;

/*
 * Get current configuration parameters.
 */
static void fetch_config_values (void)
{
  db_host = config_get_value("db_host");
  db_name = config_get_value("db_name");
  db_user = config_get_value("db_user");
  db_passwd = config_get_value("db_passwd");
  ticker_home = config_get_value("ticker_home");
  pid_file = config_get_value("pid_file");
  ticker_state = config_get_value("ticker_state");
  debug_logfile = config_get_value("debug_logfile");
  error_logfile = config_get_value("error_logfile");
  msg_logfile = config_get_value("msg_logfile");
  tick_interval = config_get_long_value("tick_interval");
  sleep_time = config_get_long_value("sleep_time");
  ticker_load_time = config_get_long_value("ticker_load_time");
  
  shutdown_file = "shutdown_ticker";
}

/*
 * Store the process identifier in the pid file.
 */
static void write_pidfile (const char *pid_file)
{
  FILE *file = fopen(pid_file, "r");
  long pid;

  if (file)
  {
    /* check for an already running ticker instance */
    if (fscanf(file, "%ld", &pid) == 1) error("ticker already running, exiting");
    fclose(file);
  }

  file = fopen_check(pid_file, "w");
  fprintf(file, "%ld\n", (long) getpid());

  fprintf(file, "Ticker start: %ld\n", time(NULL));
  
  if (fclose(file))
    perror(pid_file), exit(EXIT_FAILURE);
}

/*
 * Block automatic ticker restart and exit with error message.
 */
static void block_ticker (const char *error_msg)
{
    fclose(fopen_check("BLOCKED", "w"));
    error("%s", error_msg);
}

/*
 * Signal handling functions.
 */
static void set_finish (int signum)
{
  finish = 1;
}

static void set_reload (int signum)
{
  reload = 1;
}

/*
 * The event sheduler and resource ticker.
 */
static void run_ticker (MYSQL *database)
{
  time_t last = time(NULL);
  int events = 0, sleeps = 0;

  debug(DEBUG_TICKER, "running");

  while (!finish) {
    /* set up memory pool */
    struct memory_pool *pool = memory_pool_new();
    time_t now = time(NULL);
    int secs = now - last;
    MYSQL_RES *result; // ADDED by chris---
    int ichr; // ADDED by chris---

//    if (secs >= TICKER_LOADAVG_TIME)
    if (secs >= ticker_load_time)
    {
	debug(DEBUG_TICKER, "ticker load: %.2f (%d events/min)",
	      1 - sleep_time / 1000000.0 * sleeps / secs, 60 * events / secs);

	events = sleeps = 0;
	last = now;
#ifdef DEBUG_MALLOC
	CHECK_LEAKS();
#endif
    }

    if (reload)
    {
	debug(DEBUG_TICKER, "reload config");
	reload = 0;

	/* read config file */
	config_read_file(config_file);
	fetch_config_values();
	start_logging(debug_logfile, error_logfile);

	/* message logfile */
	message_closelog();
	message_openlog(msg_logfile);
    }

    try {
      char resource_timestamp[TIMESTAMP_LEN];
      char timestamp[TIMESTAMP_LEN];
      MYSQL_RES *next_result = NULL;
      const char *next_timestamp =
	make_timestamp(resource_timestamp, tick_next_event());
      const char *next_db_eventID;
      int next_eventType;
      int i;

      /* check each event queue to find the next event to process */
      debug(DEBUG_EVENTS, "start loop, next resource event");

next_db_eventID = 0; // ADDED by chris---

      for (i = 0; i < eventTableSize; ++i)
      {
      const char *next_timestamp = make_timestamp(resource_timestamp, tick_next_event()); // ADDED by chris---, important var, dont delete
//      MYSQL_RES *result; // DELETED by chris---

	/* get only the next non-blocked event, if its timestamp
	   is smaller than the smallest found timestamp */
	debug(DEBUG_EVENTS, "query event table: %s", eventTableList[i].table);

//	result = mysql_query_fmt(database,
//		    "SELECT * FROM %s WHERE %s = 0 AND %s < '%s' "
//		    "ORDER BY %s ASC, %s ASC LIMIT 0,1",
//		    eventTableList[i].table,
//		    eventTableList[i].block_field,
//		    eventTableList[i].time_field, next_timestamp,
//		    eventTableList[i].time_field,
//		    eventTableList[i].id_field);

//	if (mysql_num_rows(result))	/* is there an earlier event? */
//	{
	  /* extract this earlier event's needed data */
//	  MYSQL_ROW row = mysql_fetch_row(result);

//	  next_result = result;		/* remember the earlier one */
//	  next_timestamp =
//	    mysql_get_string_field(result, row, eventTableList[i].time_field);
//	  next_db_eventID =
//	    mysql_get_string_field(result, row, eventTableList[i].id_field);
//	  next_eventType = i;

//	  mysql_data_seek(result, 0);	/* move the row pointer back */
//	}


// ------------------------------------------------------------------------------------------
// begin my sql routine

//if (next_db_eventID <= 0) {
//printf("Nummer: %d, next_db_eventID: %s\n\r", i, next_db_eventID);
char *myquery;
myquery=malloc(2048); /*Make sure we have enough space for the query */
  /* Build query */
sprintf(myquery, "SELECT * FROM %s WHERE %s = 0 AND %s < '%s' ORDER BY %s ASC, %s ASC LIMIT 0,1",
		    eventTableList[i].table,
		    eventTableList[i].block_field,
		    eventTableList[i].time_field, next_timestamp,
		    eventTableList[i].time_field,
		    eventTableList[i].id_field);

mysql_real_query(database, myquery, strlen(myquery));
result = mysql_store_result(database); /* Download result from server */

	if (mysql_num_rows(result)>0)	/* is there an earlier event? */
	{
	  /* extract this earlier event's needed data */
	  MYSQL_ROW row = mysql_fetch_row(result);

	  next_result = result;		/* remember the earlier one */
	  next_timestamp =
	    mysql_get_string_field(result, row, eventTableList[i].time_field);
	  next_db_eventID =
	    mysql_get_string_field(result, row, eventTableList[i].id_field);
	  next_eventType = i;

	  mysql_data_seek(result, 0);	/* move the row pointer back */
	  
if (strcmp(next_timestamp, make_timestamp(timestamp, time(NULL))) < 0)
      {	  

	  	debug(DEBUG_TICKER, "event: scheduled %s, now %s",
	      next_timestamp, timestamp);
	  
	  /* found an event in the event tables: block the event */
	  debug(DEBUG_EVENTS, "block event: %s", next_db_eventID);
  	  debug(DEBUG_TICKER, "block event: %s", next_db_eventID);
	  ++events;

//printf("Blocking next_db_eventID: %s\n\r", next_db_eventID);
	  mysql_query_fmt(database, "UPDATE %s SET %s = 1 WHERE %s = %s",
			  eventTableList[next_eventType].table,
			  eventTableList[next_eventType].block_field,
			  eventTableList[next_eventType].id_field,
			  next_db_eventID);

	  /* call handler and delete event */
	  eventTableList[next_eventType].handler(database, next_result);

	  debug(DEBUG_EVENTS, "delete event: %s", next_db_eventID);
//printf("Deleting next_db_eventID: %s\n\r", next_db_eventID);
	  mysql_query_fmt(database, "DELETE FROM %s WHERE %s = %s",
			  eventTableList[next_eventType].table,
			  eventTableList[next_eventType].id_field,
			  next_db_eventID);
	  
//next_db_eventID = -1;
} // end if timestamp compare

	}
free(myquery);
mysql_free_result(result); /* Release memory used to store results. */
//if (next_db_eventID > 0) break;
//} // end if next_db_eventID
// end my sql routine
// ------------------------------------------------------------------------------------------

      }

      if (strcmp(next_timestamp, make_timestamp(timestamp, time(NULL))) > 0)
      {
	debug(DEBUG_EVENTS, "no event pending, sleep");
	++sleeps;
	sleep(sleep_time*1000);
      }
      else
      {
//	debug(DEBUG_TICKER, "event: scheduled %s, now %s",
//	      next_timestamp, timestamp);

	/* check which handler to call (resource ticker or event handler) */
	if (!next_result)
//	{
	  /* found an event in the event tables: block the event */
//	  debug(DEBUG_EVENTS, "block event: %s", next_db_eventID);
//  	  debug(DEBUG_TICKER, "block event: %s", next_db_eventID);
//	  ++events;

//	  mysql_query_fmt(database, "UPDATE %s SET %s = 1 WHERE %s = %s",
//			  eventTableList[next_eventType].table,
//			  eventTableList[next_eventType].block_field,
//			  eventTableList[next_eventType].id_field,
//			  next_db_eventID);

	  /* call handler and delete event */
//	  eventTableList[next_eventType].handler(database, next_result);

//	  debug(DEBUG_EVENTS, "delete event: %s", next_db_eventID);

//	  mysql_query_fmt(database, "DELETE FROM %s WHERE %s = %s",
//			  eventTableList[next_eventType].table,
//			  eventTableList[next_eventType].id_field,
//			  next_db_eventID);
//	}
//	else
	{
	  /* next event is resource tick: call resource ticker */
	  debug(DEBUG_TICKER, "resource tick %s", resource_timestamp);

	  resource_ticker(database, tick_advance());
	  debug(DEBUG_TICKER, "resource tick ended");
	  tick_log();			/* log last successful update */
	}
      }
    } catch (BAD_ARGUMENT_EXCEPTION) {
      warning("%s", except_msg);
    } catch (SQL_EXCEPTION) {
      warning("%s", except_msg);
    } catch (GENERIC_EXCEPTION) {
      warning("%s", except_msg);
    } catch (DB_EXCEPTION) {
      block_ticker(except_msg);
    } catch (NULL) {
      error("%s", except_msg);
    } end_try;

    memory_pool_free(pool);
    
    if (kbhit ()) ichr = getch ();
    if (ichr == 120) {
      finish = 1;
      printf("Exiting...");
    }
    
// External shutdown
    FILE *file = fopen(shutdown_file, "r");

  if (file)
  {
    /* delete file and finish */
    fclose(file);
    remove(shutdown_file);
    finish = 1;
    printf("External shutdown. Exiting...");
  }
    
  }

  debug(DEBUG_TICKER, "end");
}

int main (int argc, char *argv[])
{

  MYSQL *db;
/*
  int opt;

  while ((opt = getopt(argc, argv, "C:V")) >= 0)
  {
    switch (opt)
    {
      case 'C':
	config_file = optarg;
	break;
      case 'V':
	puts("$Id: ticker.c,v 1.15 2004/02/17 21:40:49 eludwig Exp $");
	return 0;
      default:
	return 1;
    }
  }
 */

#ifdef DEBUG_MALLOC
  GC_find_leak = 1;
#endif

  /* init random number generator */
  srand(time(NULL));

  /* read config file */
  config_read_file(config_file);
  fetch_config_values();

  /* open ticker logfiles */
  if (chdir(ticker_home))
      perror(ticker_home), exit(EXIT_FAILURE);
  start_logging(debug_logfile, error_logfile);

  /* init the game rules */
  debug(DEBUG_TICKER, "init");

  init_buildings();
  init_sciences();
  init_units();
  init_resources();
  init_defenseSystems();

  /* connect to the database */
  if (!(db = mysql_init(0)))
    error("couldn't initialize MySQL");

  if (!mysql_real_connect(db, db_host, db_user, db_passwd, db_name, 0, NULL, 0))
    error("failed to connect to database: %s", mysql_error(db));

  /* read last tick */
  tick_init();

  /* load report templates */
  reports_init();

  /* open message logfile */
  message_openlog(msg_logfile);

  /* install signal handler */
/*  signal(SIGTERM, set_finish);
  signal(SIGINT, set_finish);
  signal(SIGHUP, set_reload);
*/
  write_pidfile(pid_file);

  /* start the ticker */
  puts("$Id: ticker.c,v 1.15 2004/02/17 21:40:49 eludwig Exp $\n\r$Windows-Version: 0.5, by chris---");
  puts("\n\r\n\rrunning...\n\r\n\rpress x for exit. exiting may take a while due to sleep time");
  run_ticker(db);

  /* clean up */
  debug(DEBUG_TICKER, "cleanup");

  remove(pid_file);

  message_closelog();
  mysql_close(db);
  return 0;
}
