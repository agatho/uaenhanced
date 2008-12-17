/*
 * message.c - generate ticker reports to players
 * Copyright (c) 2003  OGP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

#include <math.h>
#include <stdarg.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "logging.h"
#include "memory.h"
#include "message.h"
#include "messagelog.h"
#include "mysql_tools.h"
#include "template.h"
#include "wonder_rules.h"

#define drand()		(rand() / (RAND_MAX+1.0))

struct SpyInfo
{
    int level;		/* maximum spy value */
    double value;	/* average spy value */
    double chance;	/* total spy chance */
    double quality;	/* average spy quality */
};

// ADDED by chris--- for artefact destroying
static struct template *tmpl_artefact_destr;

static struct template *tmpl_artefact;
static struct template *tmpl_battle1;
static struct template *tmpl_battle2;
static struct template *tmpl_merge;
static struct template *tmpl_protected1;
static struct template *tmpl_protected2;
static struct template *tmpl_return;
static struct template *tmpl_spy1;
static struct template *tmpl_spy2;
static struct template *tmpl_takeover1;
static struct template *tmpl_takeover2;
static struct template *tmpl_trade1;
static struct template *tmpl_trade2;
static struct template *tmpl_wonder_prolonged;
static struct template *tmpl_wonder;

void reports_init (void)
{
    struct memory_pool *pool = memory_pool_new();

// ADDED by chris--- for artefact destroying
    tmpl_artefact_destr   = template_from_file("reports/artefact_destr.ihtml");

    tmpl_artefact   = template_from_file("reports/artefact.ihtml");
    tmpl_battle1    = template_from_file("reports/battle1.ihtml");
    tmpl_battle2    = template_from_file("reports/battle2.ihtml");
    tmpl_merge      = template_from_file("reports/merge.ihtml");
    tmpl_protected1 = template_from_file("reports/protected1.ihtml");
    tmpl_protected2 = template_from_file("reports/protected2.ihtml");
    tmpl_return     = template_from_file("reports/return.ihtml");
    tmpl_spy1       = template_from_file("reports/spy1.ihtml");
    tmpl_spy2       = template_from_file("reports/spy2.ihtml");
    tmpl_takeover1  = template_from_file("reports/takeover1.ihtml");
    tmpl_takeover2  = template_from_file("reports/takeover2.ihtml");
    tmpl_trade1     = template_from_file("reports/trade1.ihtml");
    tmpl_trade2     = template_from_file("reports/trade2.ihtml");
    tmpl_wonder_prolonged =
      template_from_file("reports/wonderprolonged.ihtml");
    tmpl_wonder    = template_from_file("reports/wonder.ihtml");

    memory_pool_free(pool);
}

static void report_units (struct template *template, const int units[])
{
    int type;

    for (type = 0; type < MAX_UNIT; ++type)
	if (units[type] > 0)
	{
	    template_iterate(template, "UNITS/UNIT");
	    template_set(template, "UNITS/UNIT/name", unitTypeList[type].name);
	    template_set_fmt(template, "UNITS/UNIT/num", "%d", units[type]);
	}
}

static void report_resources (struct template *template, const int resources[],
			      const int base_resources[])
{
    int type;

    for (type = 0; type < MAX_RESOURCE; ++type)
    {
	int res1 = resources[type];
	int res2 = base_resources ? base_resources[type] : 0;

	if (res1 - res2 > 0)
	{
	    template_iterate(template, "RESOURCES/RESOURCE");
	    template_set(template, "RESOURCES/RESOURCE/name",
			 resourceTypeList[type].name);
	    template_set_fmt(template, "RESOURCES/RESOURCE/num", "%d",
			     res1 - res2);
	}
    }
}

static void report_defenses (struct template *template, const int defsys[])
{
    int type;

    for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
	if (defsys[type] > 0)
	{
	    template_iterate(template, "DEFENSES/DEFENSE");
	    template_set(template, "DEFENSES/DEFENSE/name",
			 defenseSystemTypeList[type].name);
	    template_set_fmt(template, "DEFENSES/DEFENSE/num", "%d",
			     defsys[type]);
	}
}

static void report_buildings (struct template *template, const int building[])
{
    int type;

    for (type = 0; type < MAX_BUILDING; ++type)
	if (building[type] > 0)
	{
	    template_iterate(template, "BUILDINGS/BUILDING");
	    template_set(template, "BUILDINGS/BUILDING/name",
			 buildingTypeList[type].name);
	    template_set_fmt(template, "BUILDINGS/BUILDING/num", "%d",
			     building[type]);
	}
}

static void report_sciences (struct template *template, const int science[])
{
    int type;

    for (type = 0; type < MAX_SCIENCE; ++type)
	if (science[type] > 0)
	{
	    template_iterate(template, "SCIENCES/SCIENCE");
	    template_set(template, "SCIENCES/SCIENCE/name",
			 scienceTypeList[type].name);
	    template_set_fmt(template, "SCIENCES/SCIENCE/num", "%d",
			     science[type]);
	}
}

static void report_battle_info (struct template *template, const Battle *result,
				int battle_flag)
{
    int acc_range, acc_fort, acc_melee, acc_size;
    double rel_bonus, god_bonus;

    if (battle_flag == FLAG_ATTACKER)
    {
	acc_range = result->attackers_acc_range_before;
	acc_fort  = result->attackers_acc_areal_before;
	acc_melee = result->attackers_acc_melee_before;
	acc_size  = result->attackers_acc_hitpoints_units_before +
		    result->attackers_acc_hitpoints_defenseSystems_before;
	rel_bonus = result->attackers[0].relationMultiplicator;
	god_bonus = result->attackers[0].religion_bonus;
    }
    else
    {
	acc_range = result->defenders_acc_range_before;
	acc_fort  = result->defenders_acc_areal_before;
	acc_melee = result->defenders_acc_melee_before;
	acc_size  = result->defenders_acc_hitpoints_units_before +
		    result->defenders_acc_hitpoints_defenseSystems_before;
	rel_bonus = result->defenders[0].relationMultiplicator;
	god_bonus = result->defenders[0].religion_bonus;
    }

    template_set_fmt(template, "range", "%d", acc_range);
    template_set_fmt(template, "struct", "%d", acc_fort);
    template_set_fmt(template, "melee", "%d", acc_melee);
    template_set_fmt(template, "size", "%d", acc_size);
    template_set_fmt(template, "relation", "%.2f", rel_bonus);
    template_set_fmt(template, "religion", "%.2f", god_bonus);
}

static void report_army (struct template *template, const char *name,
			 const Army_unit *unit)
{
    if (unit->amount_before > 0)
    {
	template_iterate(template, "BEFORE");
	template_set(template, "BEFORE/name", name);
	template_set_fmt(template, "BEFORE/num", "%d", unit->amount_before);

	template_iterate(template, "AFTER");
	template_set(template, "AFTER/name", name);
	template_set_fmt(template, "AFTER/num", "%d", unit->amount_after);
	if (unit->amount_after < unit->amount_before)
	    template_set_fmt(template, "AFTER/DELTA/num", "%d",
			     unit->amount_after - unit->amount_before);
    }
}

static void report_army_list (struct template *template, const Army *army)
{
    int type;

    if (army && army->units)
	for (type = 0; type < MAX_UNIT; ++type)
	    report_army(template, unitTypeList[type].name, &army->units[type]);

    if (army && army->defenseSystems)
	for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
	    report_army(template, defenseSystemTypeList[type].name,
			&army->defenseSystems[type]);
}

static void report_army_table (struct template *template, const Battle *result)
{
    template_context(template, "/MSG/ATTACK");
    report_army_list(template, result->attackers);
    report_battle_info(template, result, FLAG_ATTACKER);

    template_context(template, "/MSG/DEFEND");
    report_army_list(template, result->defenders);
    report_battle_info(template, result, FLAG_DEFENDER);

    template_context(template, "/MSG");
}

static void get_spy_values (struct SpyInfo *spy, const int att_units[],
			    const int def_units[], const int def_defsys[])
{
    double anti_spy_chance = 0;
    int type;

    spy->level = 0;
    spy->value = spy->chance = spy->quality = 0;

    for (type = 0; type < MAX_UNIT; ++type)
    {
	double chance = unitTypeList[type].spyChance * att_units[type];

	spy->value += unitTypeList[type].spyValue * chance;
	spy->quality += unitTypeList[type].spyQuality * chance;
	spy->chance += chance;

	if (chance > 0 && unitTypeList[type].spyValue > spy->level)
	    spy->level = unitTypeList[type].spyValue;
    }

    for (type = 0; type < MAX_UNIT; ++type)
	anti_spy_chance += unitTypeList[type].antiSpyChance * def_units[type];

    for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
	anti_spy_chance += defenseSystemTypeList[type].antiSpyChance *
			   def_defsys[type];

    if (spy->chance > 0)
    {
	spy->value /= spy->chance;
	spy->quality /= spy->chance;
	spy->chance /= spy->chance + anti_spy_chance;
    }
}

static double get_spy_quality_battle (const Battle *result)
{
    double attacker_spy_quality = 0;
    double defender_spy_quality = 0;
    int index, type;

    for (index = 0; index < result->size_attackers; ++index)
    {
	const Army *army = &result->attackers[index];

	if (army && army->units)
	    for (type = 0; type < MAX_UNIT; ++type)
		if (army->units[type].amount_before > 0 &&
		    unitTypeList[type].spyQuality > attacker_spy_quality)
		    attacker_spy_quality = unitTypeList[type].spyQuality;
    }

    for (index = 0; index < result->size_defenders; ++index)
    {
	const Army *army = &result->defenders[index];

	if (army && army->units)
	    for (type = 0; type < MAX_UNIT; ++type)
		if (army->units[type].amount_before > 0 &&
		    unitTypeList[type].spyQuality > defender_spy_quality)
		    defender_spy_quality = unitTypeList[type].spyQuality;
    }

    attacker_spy_quality -= defender_spy_quality;
    return attacker_spy_quality > 0.2 ? attacker_spy_quality : 0.2;
}

static int fuzzy_value (double value, double quality, double chance)
{
    int result = chance > drand() ? pow(quality, 1 - 2 * drand()) * value : 0;
    int factor = 1;

    while (result > 999) result /= 10, factor *= 10;
    return result * factor;
}

static double fuzzy_wonder_value (double value, double chance) {
  double factor = drand() * 6 - 3;
  factor = factor < 0 ? 1 / (1-factor) : 1+factor;

  if (drand() > chance) return 0;
  return value * factor;
}

static void report_fuzzy_size (struct template *template, const Battle *result)
{
    double spy_quality = get_spy_quality_battle(result);
    int def_size = result->defenders_acc_hitpoints_units_before +
		   result->defenders_acc_hitpoints_defenseSystems_before;

    if (spy_quality > 0)
	template_set_fmt(template, "GUESS/size", "%d",
	    (int) fuzzy_value(def_size, spy_quality, 1.0) / 100 * 100);
}

static int guess_values (int guess[], const int value[], int len,
			 const struct SpyInfo *spy, int level)
{
    double chance = spy->quality * spy->value / level;
    int result = 0;
    int type;

    if (spy->level < level) return 0;

    for (type = 0; type < len; ++type)
	if ((guess[type] = fuzzy_value(value[type], spy->quality, chance)))
	    result = 1;

    return result;
}

#if 0	/* unused */
static void report_messages (MYSQL *database, int player_id)
{
    MYSQL_RES *result = mysql_query_fmt(database,
	"SELECT p.name, m.messageSubject FROM Message m LEFT JOIN "
	DB_MAIN_TABLE_PLAYER " p ON m.recipientID = p.playerID"
	" WHERE p.playerID = %d ORDER BY ? LIMIT 0,10", player_id);
    MYSQL_ROW row;

    while ((row = mysql_fetch_row(result)))
    {
	const char *name = row[0];
	const char *mesg = row[1];
    }
}
#endif

static void report_spy_info (struct template *template,
			     const struct SpyInfo *spy,
			     const struct Cave *info)
// DELETED by chris---: damn monster
//			     const struct Cave *info,
//			     const struct Monster *monster)
{
    struct Cave cave;

    if (guess_values(cave.resource, info->resource, MAX_RESOURCE, spy, 1))
	report_resources(template, cave.resource, NULL);

    if (guess_values(cave.defense_system, info->defense_system,
		     MAX_DEFENSESYSTEM, spy, 2))
	report_defenses(template, cave.defense_system);

    if (guess_values(cave.unit, info->unit, MAX_UNIT, spy, 3))
	report_units(template, cave.unit);

    if (guess_values(cave.building, info->building, MAX_BUILDING, spy, 4))
	report_buildings(template, cave.building);

    if (guess_values(cave.science, info->science, MAX_SCIENCE, spy, 5))
	report_sciences(template, cave.science);

    if (spy->quality * spy->value > drand())
    {
// DELETED by chris---: damn monster
/*
	template_set(template, "MONSTER/name", monster->name);
	template_set_fmt(template, "MONSTER/attack", "%d", monster->attack);
	template_set_fmt(template, "MONSTER/defense", "%d", monster->defense);
	template_set_fmt(template, "MONSTER/mental", "%d", monster->mental);
	template_set_fmt(template, "MONSTER/strength", "%d", monster->strength);
	template_set_fmt(template, "MONSTER/exp", "%d", monster->exp_value);
	template_set(template, "MONSTER/attributes", monster->attributes);
*/
    }

    /* TODO wonder effects, messages */
    /*	Bei dieser Höhle scheinen wertvolle Rohstoffe zu lagern: */
    /*	Aus sicherer Entfernung sind vage die Umrisse einiger Bauten zu
	erahnen, die anscheinend zur Verteidigung der Höhle gegen Angriffe
	errichtet worden sind: */
    /*	Beim Versuch, sich näher an die Höhle heranzuschleichen, entdeckt
	ein Kundschafter einige gefährlich aussehende Gestalten: */
    /*	Eine Reihe von Gebäuden erregt eure besondere Aufmerksamkeit: */
    /*	Als eure Spione einen Gefangenen verhören, berichtet dieser von
	aktuellen Forschungen seines Stammes: */
    /*  Beim Stöbern in den Privatgemächern des gegnerischen Stammesführers
	entdeckt Euer Spion einige Nachrichten: */
}

static void message_new (MYSQL *database, int msg_class, int recipient,
			 const char *subject, const char *text)
{
    if (recipient == PLAYER_SYSTEM) return;

    mysql_query_fmt(database,
	"INSERT INTO Message (senderID, recipientID, messageClass, "
	"messageSubject, messageText, messageTime) "
	"VALUES (%d, %d, %d, '%s', '%s', NOW()+0)",
	PLAYER_SYSTEM, recipient, msg_class, subject, text);

    message_log(PLAYER_SYSTEM, recipient, text);
}

static const char *message_subject (struct template *template, const char *path,
				    const struct Cave *cave)
{
    template_clear(template);
    template_context(template, path);
    template_set(template, "cave", cave->name);
    template_set_fmt(template, "xpos", "%d", cave->xpos);
    template_set_fmt(template, "ypos", "%d", cave->ypos);

    return template_eval(template);
}

/*
 * Note: This implementation relies on the fact that the movement handler
 * passes identical strings (same pointer values) for both player names if
 * the starting and destination cave belong to the same player.
 */
static void message_setup (struct template *template,
			   const struct Cave *cave, const struct Cave *orig,
			   const char *player, const char *sender)
{
    template_clear(template);
    template_context(template, "MSG");
    template_set(template, "cave", cave->name);
    template_set(template, "orig", orig->name);

    if (player)
	template_set(template, "player", player);
    if (sender)
	template_set(template, "sender", sender);
    if (player == sender)
	template_set(template, "self", "");
}

/*
 * Return the class name of the artefact with the given id.
 */
static const char *artefact_name (MYSQL *database, int artefact_id)
{
    struct Artefact artefact;
    struct Artefact_class artefact_class;

    get_artefact_by_id(database, artefact_id, &artefact);
    get_artefact_class_by_id(database, artefact.artefactClassID,
			     &artefact_class);
    return artefact_class.name;
}

void trade_report (MYSQL *database,
		   const struct Cave *cave1, const char *player1,
		   const struct Cave *cave2, const char *player2,
		   const int resources[], const int units[], int artifact)
{
    const char *subject1 = message_subject(tmpl_trade1, "TITLE", cave2);
    const char *subject2 = message_subject(tmpl_trade2, "TITLE", cave2);

    message_setup(tmpl_trade1, cave2, cave1, player2, player1);
    message_setup(tmpl_trade2, cave2, cave1, player2, player1);

    if (units)
    {
	report_units(tmpl_trade1, units);
	report_units(tmpl_trade2, units);
    }

    report_resources(tmpl_trade1, resources, NULL);
    report_resources(tmpl_trade2, resources, NULL);

    if (artifact)
    {
	const char *name = artefact_name(database, artifact);

	template_set(tmpl_trade1, "ARTEFACT/artefact", name);
	template_set(tmpl_trade2, "ARTEFACT/artefact", name);
    }

    message_new(database, MSG_CLASS_TRADE,
		cave2->player_id, subject2, template_eval(tmpl_trade2));

    if (cave1->player_id != cave2->player_id)
	message_new(database, MSG_CLASS_TRADE,
		    cave1->player_id, subject1, template_eval(tmpl_trade1));
}

void return_report (MYSQL *database,
		    const struct Cave *cave1, const char *player1,
		    const struct Cave *cave2, const char *player2,
		    const int resources[], const int units[], int artifact)
{
    const char *subject = message_subject(tmpl_return, "TITLE", cave2);

    message_setup(tmpl_return, cave2, cave1, player2, player1);

    report_units(tmpl_return, units);
    report_resources(tmpl_return, resources, NULL);

    if (artifact)
	template_set(tmpl_return, "ARTEFACT/artefact",
		     artefact_name(database, artifact));

    message_new(database, MSG_CLASS_RETURN,
		cave2->player_id, subject, template_eval(tmpl_return));
}

void battle_report (MYSQL *database,
		    const struct Cave *cave1, const char *player1,
		    const struct Cave *cave2, const char *player2,
		    const Battle *result, int artifact, int lost,
		    int change_owner, int takeover_multiplier,
		    const struct Relation *relation1,
		    const struct Relation *relation2, int fame)
{
    struct template *template1, *template2;
    const char *subject1, *subject2;
    int msg_class1 = MSG_CLASS_DEFEAT;
    int msg_class2 = MSG_CLASS_VICTORY;
    int res_vorher_A = 0;
    int res_vorher_V = 0;
    int res_nachher_A = 0;
    int type;

    if (takeover_multiplier)
	template1 = tmpl_takeover1, template2 = tmpl_takeover2;
    else
	template1 = tmpl_battle1,   template2 = tmpl_battle2;

    if (result->winner == FLAG_ATTACKER)
    {
	msg_class1 = MSG_CLASS_VICTORY;
	msg_class2 = MSG_CLASS_DEFEAT;
	subject1 = message_subject(template1, "TITLE_WIN", cave2);
	subject2 = message_subject(template2, "TITLE_LOSE", cave2);
    }
    else
    {
	subject1 = message_subject(template1, "TITLE_LOSE", cave2);
	subject2 = message_subject(template2, "TITLE_WIN", cave2);
    }

    message_setup(template1, cave2, cave1, player2, player1);
    message_setup(template2, cave2, cave1, player2, player1);

    if (result->winner == FLAG_ATTACKER)
    {
	template_set(template1, "att_won", "");
	template_set(template2, "att_won", "");

	if (change_owner)
	{
	    template_set(template1, "takeover", "");
	    template_set(template2, "takeover", "");
	}
    }

    if (relation1->attackerReceivesFame)
	template_set_fmt(template1, "FAME/fame", "%d", fame);
    if (relation2->defenderReceivesFame)
	template_set_fmt(template2, "FAME/fame", "%d", fame);

    template_set_fmt(template1, "factor", "%d", takeover_multiplier);
    template_set_fmt(template2, "factor", "%d", takeover_multiplier);

    /* attackers_acc_hitpoints_units is actually the army size */
    if (result->attackers_acc_hitpoints_units == 0)
	report_fuzzy_size(template1, result);
    else
	report_army_table(template1, result);

    report_army_table(template2, result);

    /* Ressourcen nachzaehlen */
    for (type = 0; type < MAX_RESOURCE; ++type)
    {
	res_vorher_A  += result->attackers->resourcesBefore[type];
	res_vorher_V  += result->defenders->resourcesBefore[type];
	res_nachher_A += result->attackers->resourcesAfter[type];
    }

    /* hatte der A was mit, anzeigen */
    if (res_vorher_A > 0)
	report_resources(template1, result->attackers->resourcesBefore, NULL);

    /* hatte der V vorher was, anzeigen */
    if (res_vorher_V > 0)
	report_resources(template2, result->defenders->resourcesBefore, NULL);

    template_context(template1, "PLUNDER");
    template_context(template2, "PLUNDER");

    /* hat der A gewonnen, Beute ausgeben */
    if (result->winner == FLAG_ATTACKER)
    {
	if (res_nachher_A > res_vorher_A)
	{
	    report_resources(template1,
			     result->attackers->resourcesAfter,
			     result->attackers->resourcesBefore);
	    report_resources(template2,
			     result->attackers->resourcesAfter,
			     result->attackers->resourcesBefore);
	}
    }
    /* hat der A verloren, hat er hinterher nichts mehr */
    else if (res_vorher_A > 0)
    {
	report_resources(template1, result->attackers->resourcesBefore, NULL);
	report_resources(template2, result->attackers->resourcesBefore, NULL);
    }

    template_context(template1, "/MSG");
    template_context(template2, "/MSG");

    if (artifact)
    {
	const char *name = artefact_name(database, artifact);

	if (!lost)
	{
	    template_set(template1, "ARTEFACT/artefact", name);
	    template_set(template2, "ARTEFACT/artefact", name);
	}
	else
	{
	    template_set(template1, "ARTEFACT_LOST/artefact", name);
	    template_set(template2, "ARTEFACT_LOST/artefact", name);
	}
    }

    message_new(database, msg_class1, cave1->player_id,
		subject1, template_eval(template1));
    message_new(database, msg_class2, cave2->player_id,
		subject2, template_eval(template2));
}

void protected_report (MYSQL *database,
		       const struct Cave *cave1, const char *player1,
		       const struct Cave *cave2, const char *player2)
{
    const char *subject1 = message_subject(tmpl_protected1, "TITLE", cave2);
    const char *subject2 = message_subject(tmpl_protected2, "TITLE", cave2);

    message_setup(tmpl_protected1, cave2, cave1, player2, player1);
    message_setup(tmpl_protected2, cave2, cave1, player2, player1);

    message_new(database, MSG_CLASS_INFO, cave1->player_id,
		subject1, template_eval(tmpl_protected1));
    message_new(database, MSG_CLASS_INFO, cave2->player_id,
		subject2, template_eval(tmpl_protected2));
}

int spy_report (MYSQL *database,
		const struct Cave *cave1, const char *player1,
		const struct Cave *cave2, const char *player2,
		const int resources[], const int units[], int artifact)
{
    struct SpyInfo spy;
    struct Monster monster;
    const char *subject1 = message_subject(tmpl_spy1, "TITLE", cave2);
    const char *subject2 = message_subject(tmpl_spy2, "TITLE", cave2);
    int result = 1;

    message_setup(tmpl_spy1, cave2, cave1, player2, player1);
    message_setup(tmpl_spy2, cave2, cave1, player2, player1);

    get_spy_values(&spy, units, cave2->unit, cave2->defense_system);

    if (spy.chance > drand())
    {
	template_set(tmpl_spy1, "report", "");

// DELETED by chris---: damn monster
//	get_monster_info(database, cave2->monster_id, &monster);
//	report_spy_info(tmpl_spy1, &spy, cave2, &monster);
	report_spy_info(tmpl_spy1, &spy, cave2);
    }
    else
    {
	if (0.5 > drand())
	{
	    result = 0;
	    template_set(tmpl_spy1, "dead", "");
	    template_set(tmpl_spy2, "dead", "");
	}

	if (artifact)
	{
	    const char *name = artefact_name(database, artifact);

	    template_set(tmpl_spy1, "ARTEFACT/artefact", name);
	    template_set(tmpl_spy2, "ARTEFACT/artefact", name);
	}

	report_units(tmpl_spy2, units);
	report_resources(tmpl_spy2, resources, NULL);

	message_new(database, MSG_CLASS_SPY_REPORT,
		    cave2->player_id, subject2, template_eval(tmpl_spy2));
    }

    template_set_fmt(tmpl_spy1, "spy_level", "%d", spy.level);
    template_set_fmt(tmpl_spy1, "spy_value", "%g", spy.value);
    template_set_fmt(tmpl_spy1, "spy_chance", "%g", spy.chance);
    template_set_fmt(tmpl_spy1, "spy_quality", "%g", spy.quality);

    message_new(database, MSG_CLASS_SPY_REPORT,
		cave1->player_id, subject1, template_eval(tmpl_spy1));
    return result;
}

static void wonder_prepare_message(struct template*    template,
				   const char*         message,
				   const WonderEvent*  event,
				   const struct Cave*  targetCave,
				   const reportEntity* values,
                                   int                 values_size,
				   int                 message_type,
// ADDED by chris--- for wonder
                     int       msg_target)
{
  int i;
  double steal = wonderList[event->wonderID].impacts[event->impactID].steal;
  double tmp;

  template_clear(template);
  template_context(template, "MSG");
  template_set(template, "cave_name", targetCave->name);
  template_set_fmt(template, "xpos", "%d", targetCave->xpos);
  template_set_fmt(template, "ypos", "%d", targetCave->ypos);
  template_set(template, "wonder_message", message);

  template_context(template, "/MSG/REPORT");

  if (message_type == WONDER_MESSAGE_note) {
    template_set(template, "heading",
		 "Einer Eurer Schamanen berichtet Euch von folgenden "
		 "Wirkungen, da eine göttliche Eingebung ausbleibt. Nun "
		 "ja, an der Vollständigkeit und Korrektheit seines "
		 "Berichts hegt Ihr, aus Erfahrung schlau geworden, "
		 "Zweifel.");
  }
  else {
    template_set(template, "heading", "Es zeigten sich folgende Wirkungen:");
  }
  if (values) {
    for (i=0; i < values_size; ++i) {
      if (message_type == WONDER_MESSAGE_note) {

// ADDED by chris--- for wonder
//        tmp = fuzzy_wonder_value(values[i].value, 0.6);
        if (msg_target == 0) tmp = fuzzy_wonder_value(values[i].value, 0.6);
          else tmp = values[i].value;
// -----------------------------------------------

        if (tmp == 0) continue;
      }
      else {
        tmp = values[i].value;
      }
      template_iterate(template, "VALUE");
      template_set(template, "VALUE/name", values[i].name);
      template_set_fmt(template, "VALUE/amount", "%+.*f", values[i].decimals,
                       tmp);
    }
  }
  if (steal != 0) {
    template_context(template, "STOLEN");
    template_set_fmt(template, "steal", "%.2f", steal*100.);
  }
}

void wonder_report (MYSQL *database,
		    const WonderEvent  *event,
		    const struct Cave  *targetCave,
		    const reportEntity *values,
                    int values_size)
{
  enum wonder_message_type source_message_type =
    wonderList[event->wonderID].impacts[event->impactID].sourceMessageType;
  enum wonder_message_type target_message_type =
    wonderList[event->wonderID].impacts[event->impactID].targetMessageType;

  const char *subject = message_subject(tmpl_wonder, "TITLE", targetCave);

  if (source_message_type != WONDER_MESSAGE_none) {
    wonder_prepare_message(tmpl_wonder,
			   wonderList[event->wonderID]
			   .impacts[event->impactID].sourceMessage,
			   event,
			   targetCave,
			   values,
                           values_size,
			   source_message_type,
// ADDED by chris--- for wonder:
                  0);
    message_new(database, MSG_CLASS_WONDER,
		event->casterID, subject, template_eval(tmpl_wonder));
  }

  if (target_message_type != WONDER_MESSAGE_none) {
    wonder_prepare_message(tmpl_wonder,
			   wonderList[event->wonderID]
			   .impacts[event->impactID].targetMessage,
			   event,
			   targetCave,
			   values,
                           values_size,
			   target_message_type,
// ADDED by chris--- for wonder: 
                  1);
    message_new(database, MSG_CLASS_WONDER,
		targetCave->player_id, subject, template_eval(tmpl_wonder));
  }
}

void artefact_report (MYSQL *database, const struct Cave *cave,
		      const char *artefact_name)
{
    const char *subject = message_subject(tmpl_artefact, "TITLE", cave);

    template_clear(tmpl_artefact);
    template_context(tmpl_artefact, "MSG");
    template_set(tmpl_artefact, "cave", cave->name);
    template_set(tmpl_artefact, "artefact", artefact_name);

    message_new(database, MSG_CLASS_ARTEFACT,
		cave->player_id, subject, template_eval(tmpl_artefact));
}

// ADDED by chris--- for artefact destroying
void artefact_destr_report (MYSQL *database, const struct Cave *cave,
		      const char *artefact_name)
{
    const char *subject = message_subject(tmpl_artefact_destr, "TITLE", cave);

    template_clear(tmpl_artefact_destr);
    template_context(tmpl_artefact_destr, "MSG");
    template_set(tmpl_artefact_destr, "cave", cave->name);
    template_set(tmpl_artefact_destr, "artefact", artefact_name);

    message_new(database, MSG_CLASS_ARTEFACT,
		cave->player_id, subject, template_eval(tmpl_artefact_destr));
}
// -----------------------------------------------------------------

void wonder_prolonged_report (MYSQL *database, int casterID,
			      const struct Cave* targetCave) {
    const char *subject = message_subject(tmpl_wonder_prolonged, "TITLE",
					targetCave);

    template_clear(tmpl_wonder_prolonged);
    template_context(tmpl_wonder_prolonged, "MSG");
    template_set(tmpl_wonder_prolonged, "cave", targetCave->name);

    message_new(database, MSG_CLASS_WONDER,
		casterID, subject, template_eval(tmpl_wonder_prolonged));
}

void artefact_merging_report (MYSQL *database, const struct Cave *cave,
			      const struct Artefact *key_artefact,
			      const struct Artefact *lock_artefact,
			      const struct Artefact *result_artefact)
{
    struct Artefact_class key_artefact_class,
			  lock_artefact_class,
			  result_artefact_class;
    const char *subject = message_subject(tmpl_merge, "TITLE", cave);

    /* get key artefacts class */
    get_artefact_class_by_id(database, key_artefact->artefactClassID,
			     &key_artefact_class);

    /* get lock artefacts class */
    if (lock_artefact->artefactID)
	get_artefact_class_by_id(database, lock_artefact->artefactClassID,
				 &lock_artefact_class);

    /* get result artefacts class */
    if (result_artefact->artefactID)
	get_artefact_class_by_id(database, result_artefact->artefactClassID,
				 &result_artefact_class);

    template_clear(tmpl_merge);
    template_context(tmpl_merge, "MSG");
    template_set(tmpl_merge, "cave", cave->name);
    template_set(tmpl_merge, "artefact", key_artefact_class.name);

    if (lock_artefact->artefactID)
	template_set(tmpl_merge, "lock_artefact", lock_artefact_class.name);
    if (result_artefact->artefactID)
	template_set(tmpl_merge, "res_artefact", result_artefact_class.name);

    message_new(database, MSG_CLASS_ARTEFACT,
		cave->player_id, subject, template_eval(tmpl_merge));
}
