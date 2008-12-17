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

#include "database.h"
#include "hashtable.h"
#include "memory.h"
#include "message.h"
#include "template.h"

#define SPY_DEFENSE	0	/*  1 */
#define SPY_UNIT	1	/*  2 */
#define SPY_RESOURCE	2	/*  4 */
#define SPY_BUILDING	3	/*  8 */
#define SPY_SCIENCE	4	/* 16 */
#define MAX_SPY		5

#define drand()		(rand() / (RAND_MAX+1.0))

struct SpyInfo
{
    float chance;	/* total spy chance */
    float quality;	/* average spy quality */
    float weight;	/* ability weight */
};

static log_handler_t *msg_handler;

void message_set_log_handler (log_handler_t *handler)
{
    if (msg_handler)
	log_handler_free(msg_handler);

    msg_handler = handler;
}

static template_t *message_template (const struct Player *player,
				     const char *name)
{
    static hashtable_t *templates;
    const char *locale = player->locale ? player->locale : "de_DE";
    dstring_t *file = dstring_new("reports/%s/%s.ihtml", locale, name);
    const char *filename = dstring_str(file);
    template_t *template;

    if (templates == NULL)
	templates = hashtable_new(string_hash, string_equals);

    if ((template = hashtable_lookup(templates, filename)))
    {
	template_clear(template);
    }
    else
    {
	template = template_from_file(filename);
	hashtable_insert(templates, xstrdup(filename), template);
    }

    return template;
}

static void message_new (db_t *database, int msg_class, int recipient,
			 const char *subject, const char *text)
{
    char timestamp[TIMESTAMP_LEN];

    if (recipient == PLAYER_SYSTEM) return;

    db_query(database,
	"INSERT INTO Message (senderID, recipientID, messageClass,"
	" messageSubject, messageText, messageTime) "
	"VALUES (%d, %d, %d, '%s', '%s', '%s')",
	PLAYER_SYSTEM, recipient, msg_class, subject, text,
	make_timestamp(timestamp, time(NULL)));

    log_handler_log(msg_handler, "--- from id %d to id %d ---\n%s",
		    PLAYER_SYSTEM, recipient, text);
}

static void report_units (template_t *template, int locale_id,
			  const int units[])
{
    int type;

    for (type = 0; type < MAX_UNIT; ++type)
	if (units[type] > 0)
	{
	    template_iterate(template, "UNITS/UNIT");
	    template_set(template, "UNITS/UNIT/name",
			 unit_type[type]->name[locale_id]);
	    template_set_fmt(template, "UNITS/UNIT/num", "%d", units[type]);
	}
}

static void report_resources (template_t *template, int locale_id,
			      const int resources[], const int base_res[])
{
    int type;

    for (type = 0; type < MAX_RESOURCE; ++type)
    {
	int res1 = resources[type];
	int res2 = base_res ? base_res[type] : 0;

	if (res1 - res2 > 0)
	{
	    template_iterate(template, "RESOURCES/RESOURCE");
	    template_set(template, "RESOURCES/RESOURCE/name",
			 resource_type[type]->name[locale_id]);
	    template_set_fmt(template, "RESOURCES/RESOURCE/num", "%d",
			     res1 - res2);
	}
    }
}

static void report_defenses (template_t *template, int locale_id,
			     const int defsys[])
{
    int type;

    for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
	if (defsys[type] > 0)
	{
	    template_iterate(template, "DEFENSES/DEFENSE");
	    template_set(template, "DEFENSES/DEFENSE/name",
			 defense_system_type[type]->name[locale_id]);
	    template_set_fmt(template, "DEFENSES/DEFENSE/num", "%d",
			     defsys[type]);
	}
}

static void report_buildings (template_t *template, int locale_id,
			      const int building[])
{
    int type;

    for (type = 0; type < MAX_BUILDING; ++type)
	if (building[type] > 0)
	{
	    template_iterate(template, "BUILDINGS/BUILDING");
	    template_set(template, "BUILDINGS/BUILDING/name",
			 building_type[type]->name[locale_id]);
	    template_set_fmt(template, "BUILDINGS/BUILDING/num", "%d",
			     building[type]);
	}
}

static void report_sciences (template_t *template, int locale_id,
			     const int science[])
{
    int type;

    for (type = 0; type < MAX_SCIENCE; ++type)
	if (science[type] > 0)
	{
	    template_iterate(template, "SCIENCES/SCIENCE");
	    template_set(template, "SCIENCES/SCIENCE/name",
			 science_type[type]->name[locale_id]);
	    template_set_fmt(template, "SCIENCES/SCIENCE/num", "%d",
			     science[type]);
	}
}

static void report_battle_info (template_t *template,
				const Battle *result, int battle_flag)
{
    int acc_range, acc_fort, acc_melee, acc_size;
    float rel_bonus, god_bonus;

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

static void report_army (template_t *template, const char *name,
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

static void report_army_list (template_t *template, int locale_id,
			      const Army *army)
{
    int type;

    if (army && army->units)
	for (type = 0; type < MAX_UNIT; ++type)
	    report_army(template, unit_type[type]->name[locale_id],
			&army->units[type]);

    if (army && army->defenseSystems)
	for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
	    report_army(template, defense_system_type[type]->name[locale_id],
			&army->defenseSystems[type]);
}

static void report_army_table (template_t *template, int locale_id,
			       const Battle *result)
{
    template_context(template, "/MSG/ATTACK");
    report_army_list(template, locale_id, result->attackers);
    report_battle_info(template, result, FLAG_ATTACKER);

    template_context(template, "/MSG/DEFEND");
    report_army_list(template, locale_id, result->defenders);
    report_battle_info(template, result, FLAG_DEFENDER);

    template_context(template, "/MSG");
}

static void report_army_units (template_t *template, int locale_id,
			       const Army *army)
{
    if (army && army->units)
    {
	int units[MAX_UNIT];
	int type;

	for (type = 0; type < MAX_UNIT; ++type)
	    units[type] = army->units[type].amount_before;

	report_units(template, locale_id, units);
    }
}

static void get_spy_values (struct SpyInfo *spy, const int att_units[],
			    const int def_units[], const int def_defsys[])
{
    float total_spy_chance = 0;
    float anti_spy_chance = 0;
    int spy_type, type;

    memset(spy, 0, MAX_SPY * sizeof spy[0]);

    for (spy_type = 0; spy_type < MAX_SPY; ++spy_type)
    {
	for (type = 0; type < MAX_UNIT; ++type)
	{
	    const struct Unit *unit = (struct Unit *) unit_type[type];

	    if (unit->spyValue & 1 << spy_type)
	    {
		float chance = unit->spyChance * att_units[type];

		spy[spy_type].quality += unit->spyQuality * chance;
		spy[spy_type].chance += chance;
	    }
	}
    }

    for (type = 0; type < MAX_UNIT; ++type)
    {
	const struct Unit *unit = (struct Unit *) unit_type[type];
	const struct BattleUnit *battle_unit = (struct BattleUnit *) unit;

	total_spy_chance += unit->spyChance * att_units[type];
	anti_spy_chance += battle_unit->antiSpyChance * def_units[type];
    }

    for (type = 0; type < MAX_DEFENSESYSTEM; ++type)
    {
	const struct BattleUnit *battle_unit =
	    (struct BattleUnit *) defense_system_type[type];

	anti_spy_chance += battle_unit->antiSpyChance * def_defsys[type];
    }

    for (spy_type = 0; spy_type < MAX_SPY; ++spy_type)
    {
	float spy_chance = spy[spy_type].chance;

	if (spy_chance > 0)
	{
	    spy[spy_type].quality /= spy_chance;
	    spy[spy_type].chance /= spy_chance + anti_spy_chance;
	    spy[spy_type].weight = spy_chance / total_spy_chance;
	}
    }
}

static float get_spy_chance (const struct SpyInfo *spy)
{
    float spy_chance = 0;
    int spy_type;

    for (spy_type = 0; spy_type < MAX_SPY; ++spy_type)
    {
	if (spy[spy_type].chance > spy_chance)
	    spy_chance = spy[spy_type].chance;
    }

    return spy_chance;
}

static float get_spy_quality_army (const Army *army, int num)
{
    float spy_quality = 0;
    int index, type;

    for (index = 0; index < num; ++index)
	if (army[index].units)
	    for (type = 0; type < MAX_UNIT; ++type)
	    {
		const struct Unit *unit = (struct Unit *) unit_type[type];

		if (army[index].units[type].amount_before > 0 &&
		    unit->spyQuality > spy_quality)
		    spy_quality = unit->spyQuality;
	    }

    return spy_quality;
}

static float get_spy_quality_battle (const Battle *result)
{
    float spy_quality =
	get_spy_quality_army(result->attackers, result->size_attackers) -
	get_spy_quality_army(result->defenders, result->size_defenders);

    return spy_quality > 0.2 ? spy_quality : 0.2;
}

static int fuzzy_value (double value, double quality, double chance)
{
    int result = chance > drand() ? pow(quality, 1 - 2 * drand()) * value : 0;
    int factor = 1;

    while (result > 999) result /= 10, factor *= 10;
    return result * factor;
}

static double fuzzy_wonder_value (double value, double chance)
{
    double factor = drand() * 6 - 3;

    factor = factor < 0 ? 1 / (1 - factor) : 1 + factor;

    return chance > drand() ? value * factor : 0;
}

static void report_fuzzy_size (template_t *template, const Battle *result)
{
    float spy_quality = get_spy_quality_battle(result);
    int def_size = result->defenders_acc_hitpoints_units_before +
		   result->defenders_acc_hitpoints_defenseSystems_before;

    if (spy_quality > 0)
	template_set_fmt(template, "GUESS/size", "%d",
	    fuzzy_value(def_size, spy_quality, 1.0) / 100 * 100);
}

static int guess_values (int guess[], const int value[], int len,
			 const struct SpyInfo *spy, int spy_type)
{
    float quality = spy[spy_type].quality;
    float chance = spy[spy_type].weight * quality * 1.5;
    int result = 0;
    int type;

    for (type = 0; type < len; ++type)
	if ((guess[type] = fuzzy_value(value[type], quality, chance)))
	    result = 1;

    return result;
}

#if 0	/* unused */
static void report_messages (db_t *database, int player_id)
{
    db_result_t *result = db_query(database,
	"SELECT p.name, m.messageSubject FROM Message m LEFT JOIN "
	DB_TABLE_PLAYER " p ON m.recipientID = p.playerID"
	" WHERE p.playerID = %d ORDER BY ? LIMIT 0,10", player_id);

    while (db_result_next_row(result))
    {
	const char *name = db_result_get_string_at(result, 0);
	const char *mesg = db_result_get_string_at(result, 1);
    }
}
#endif

static void report_spy_info (template_t *template, int locale_id,
			     const struct SpyInfo *spy,
			     const struct Cave *info,
			     const struct Monster *monster)
{
    struct Cave cave;

    if (guess_values(cave.defense_system, info->defense_system,
		     MAX_DEFENSESYSTEM, spy, SPY_DEFENSE))
	report_defenses(template, locale_id, cave.defense_system);

    if (guess_values(cave.unit, info->unit, MAX_UNIT, spy, SPY_UNIT))
	report_units(template, locale_id, cave.unit);

    if (guess_values(cave.resource, info->resource, MAX_RESOURCE,
		     spy, SPY_RESOURCE))
	report_resources(template, locale_id, cave.resource, NULL);

    if (guess_values(cave.building, info->building, MAX_BUILDING,
		     spy, SPY_BUILDING))
	report_buildings(template, locale_id, cave.building);

    if (info->player_id != PLAYER_SYSTEM &&
	guess_values(cave.science, info->science, MAX_SCIENCE,
		     spy, SPY_SCIENCE))
	report_sciences(template, locale_id, cave.science);

#if 0	/* no monsters yet */
    /* TODO is [0] intended here? */
    if (spy[0].quality > drand())
    {
	template_set(template, "MONSTER/name", monster->name);
	template_set_fmt(template, "MONSTER/attack", "%d", monster->attack);
	template_set_fmt(template, "MONSTER/defense", "%d", monster->defense);
	template_set_fmt(template, "MONSTER/mental", "%d", monster->mental);
	template_set_fmt(template, "MONSTER/strength", "%d", monster->strength);
	template_set_fmt(template, "MONSTER/exp", "%d", monster->exp_value);
	template_set(template, "MONSTER/attributes", monster->attributes);
    }
#endif

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

static const char *message_subject (template_t *template, const char *path,
				    const struct Cave *cave)
{
    const char *result;

    template_context(template, path);
    template_set(template, "cave", cave->name);
    template_set_fmt(template, "xpos", "%d", cave->xpos);
    template_set_fmt(template, "ypos", "%d", cave->ypos);
    result = template_eval(template);
    template_clear(template);

    return result;
}

/*
 * Note: This implementation relies on the fact that the movement handler
 * passes identical strings (same pointer values) for both player names if
 * the starting and destination cave belong to the same player.
 */
static void message_setup (template_t *template,
			   const struct Cave *orig, const struct Player *sender,
			   const struct Cave *cave, const struct Player *player)
{
    template_context(template, "MSG");
    template_set(template, "orig", orig->name);
    template_set(template, "cave", cave->name);

    if (sender->name)
	template_set(template, "sender", sender->name);
    if (player->name)
	template_set(template, "player", player->name);
    if (player->name == sender->name)
	template_set(template, "self", "");
}

/*
 * Return the class name of the artefact with the given id.
 */
static const char *artefact_name (db_t *database, int artefact_id)
{
    struct Artefact artefact;
    struct Artefact_class artefact_class;

    get_artefact_by_id(database, artefact_id, &artefact);
    get_artefact_class_by_id(database, artefact.artefactClassID,
			     &artefact_class);
    return artefact_class.name;
}

void trade_report (db_t *database,
		   const struct Cave *cave1, const struct Player *player1,
		   const struct Cave *cave2, const struct Player *player2,
		   const int resources[], const int units[], int artefact)
{
    template_t *tmpl_trade1 = message_template(player1, "trade1");
    template_t *tmpl_trade2 = message_template(player2, "trade2");
    const char *subject1 = message_subject(tmpl_trade1, "TITLE", cave2);
    const char *subject2 = message_subject(tmpl_trade2, "TITLE", cave2);

    message_setup(tmpl_trade1, cave1, player1, cave2, player2);
    message_setup(tmpl_trade2, cave1, player1, cave2, player2);

    if (units)
    {
	report_units(tmpl_trade1, player1->locale_id, units);
	report_units(tmpl_trade2, player2->locale_id, units);
    }

    report_resources(tmpl_trade1, player1->locale_id, resources, NULL);
    report_resources(tmpl_trade2, player2->locale_id, resources, NULL);

    if (artefact)
    {
	const char *name = artefact_name(database, artefact);

	template_set(tmpl_trade1, "ARTEFACT/artefact", name);
	template_set(tmpl_trade2, "ARTEFACT/artefact", name);
    }

    message_new(database, MSG_CLASS_TRADE,
		cave2->player_id, subject2, template_eval(tmpl_trade2));

    if (cave1->player_id != cave2->player_id)
	message_new(database, MSG_CLASS_TRADE,
		    cave1->player_id, subject1, template_eval(tmpl_trade1));
}

void return_report (db_t *database,
		    const struct Cave *cave1, const struct Player *player1,
		    const struct Cave *cave2, const struct Player *player2,
		    const int resources[], const int units[], int artefact)
{
    template_t *tmpl_return = message_template(player2, "return");
    const char *subject = message_subject(tmpl_return, "TITLE", cave2);

    message_setup(tmpl_return, cave1, player1, cave2, player2);

    report_units(tmpl_return, player2->locale_id, units);
    report_resources(tmpl_return, player2->locale_id, resources, NULL);

    if (artefact)
	template_set(tmpl_return, "ARTEFACT/artefact",
		     artefact_name(database, artefact));

    message_new(database, MSG_CLASS_RETURN,
		cave2->player_id, subject, template_eval(tmpl_return));
}

void battle_report (db_t *database,
		    const struct Cave *cave1, const struct Player *player1,
		    const struct Cave *cave2, const struct Player *player2,
		    const Battle *result, int artefact, int lost,
		    int change_owner, int takeover_multiplier,
		    const struct Relation *relation1,
		    const struct Relation *relation2,
		    int show_warpoints, int attacker_warpoints, int defender_warpoints)
{
    template_t *template1, *template2;
    const char *subject1, *subject2;
    int msg_class1 = MSG_CLASS_DEFEAT;
    int msg_class2 = MSG_CLASS_VICTORY;

    if (takeover_multiplier)
    {
	template1 = message_template(player1, "takeover1");
	template2 = message_template(player2, "takeover2");
    }
    else
    {
	template1 = message_template(player1, "battle1");
	template2 = message_template(player2, "battle2");
    }

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

    message_setup(template1, cave1, player1, cave2, player2);
    message_setup(template2, cave1, player1, cave2, player2);

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

    if (show_warpoints)
    {
	template_set_fmt(template1, "WARPOINTS/PointsOwn", "%d",
			 attacker_warpoints);
	template_set_fmt(template1, "WARPOINTS/PointsOther", "%d",
			 defender_warpoints);
	template_set_fmt(template2, "WARPOINTS/PointsOwn", "%d",
			 defender_warpoints);
	template_set_fmt(template2, "WARPOINTS/PointsOther", "%d",
			 attacker_warpoints);
    }

    template_set_fmt(template1, "factor", "%d", takeover_multiplier);
    template_set_fmt(template2, "factor", "%d", takeover_multiplier);

    /* attackers_acc_hitpoints_units is actually the army size */
    if (result->attackers_acc_hitpoints_units == 0)
    {
	report_fuzzy_size(template1, result);
	report_army_units(template1, player1->locale_id, result->attackers);
    }
    else
	report_army_table(template1, player1->locale_id, result);

    report_army_table(template2, player2->locale_id, result);

    if (result->winner == FLAG_ATTACKER)
    {
	report_resources(template1, player1->locale_id,
			 result->attackers->resourcesBefore, NULL);
	report_resources(template2, player2->locale_id,
			 result->defenders->resourcesBefore, NULL);
    }

    template_context(template1, "PLUNDER");
    template_context(template2, "PLUNDER");

    if (result->winner == FLAG_ATTACKER)
    {
	report_resources(template1, player1->locale_id,
			 result->attackers->resourcesAfter,
			 result->attackers->resourcesBefore);
	report_resources(template2, player2->locale_id,
			 result->attackers->resourcesAfter,
			 result->attackers->resourcesBefore);
    }
    else
    {
	report_resources(template1, player1->locale_id,
			 result->attackers->resourcesBefore, NULL);
	report_resources(template2, player2->locale_id,
			 result->attackers->resourcesBefore, NULL);
    }
    template_context(template1, "/MSG");
    template_context(template2, "/MSG");

    if (artefact)
    {
	const char *name = artefact_name(database, artefact);

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

void protected_report (db_t *database,
		       const struct Cave *cave1, const struct Player *player1,
		       const struct Cave *cave2, const struct Player *player2)
{
    template_t *tmpl_protected1 = message_template(player1, "protected1");
    template_t *tmpl_protected2 = message_template(player2, "protected2");
    const char *subject1 = message_subject(tmpl_protected1, "TITLE", cave2);
    const char *subject2 = message_subject(tmpl_protected2, "TITLE", cave2);

    message_setup(tmpl_protected1, cave1, player1, cave2, player2);
    message_setup(tmpl_protected2, cave1, player1, cave2, player2);

    message_new(database, MSG_CLASS_INFO, cave1->player_id,
		subject1, template_eval(tmpl_protected1));
    message_new(database, MSG_CLASS_INFO, cave2->player_id,
		subject2, template_eval(tmpl_protected2));
}

double spy_report (db_t *database,
		   const struct Cave *cave1, const struct Player *player1,
		   const struct Cave *cave2, const struct Player *player2,
		   const int resources[], const int units[], int artefact)
{
    struct SpyInfo spy[MAX_SPY];
    struct Monster monster;
    template_t *tmpl_spy1 = message_template(player1, "spy1");
    template_t *tmpl_spy2 = message_template(player2, "spy2");
    const char *subject1 = message_subject(tmpl_spy1, "TITLE", cave2);
    const char *subject2 = message_subject(tmpl_spy2, "TITLE", cave2);
    double result;
#ifdef DEBUG
    int type;
#endif

    message_setup(tmpl_spy1, cave1, player1, cave2, player2);
    message_setup(tmpl_spy2, cave1, player1, cave2, player2);

    get_spy_values(spy, units, cave2->unit, cave2->defense_system);
    result = get_spy_chance(spy);

    if (result > drand())
    {
	result = 1;
	template_set(tmpl_spy1, "report", "");
#if 0	/* no monsters yet */
	get_monster_info(database, cave2->monster_id, &monster);
#endif
	report_spy_info(tmpl_spy1, player1->locale_id, spy, cave2, &monster);
    }
    else
    {
	if (0.5 > drand())
	{
	    int dead_units[MAX_UNIT];
	    int type;

	    for (type = 0; type < MAX_UNIT; ++type)
		dead_units[type] = units[type] - (int) (units[type] * result);

	    template_set(tmpl_spy1, "dead", "");
	    template_set(tmpl_spy2, "dead", "");

	    template_context(tmpl_spy1, "DEAD");
	    template_context(tmpl_spy2, "DEAD");
	    report_units(tmpl_spy1, player1->locale_id, dead_units);
	    report_units(tmpl_spy2, player2->locale_id, dead_units);
	    template_context(tmpl_spy1, "/MSG");
	    template_context(tmpl_spy2, "/MSG");
	}
	else
	{
	    result = 1;
	}

	if (artefact)
	{
	    const char *name = artefact_name(database, artefact);

	    template_set(tmpl_spy1, "ARTEFACT/artefact", name);
	    template_set(tmpl_spy2, "ARTEFACT/artefact", name);
	}

	report_units(tmpl_spy2, player2->locale_id, units);
	report_resources(tmpl_spy2, player2->locale_id, resources, NULL);

	message_new(database, MSG_CLASS_SPY_REPORT,
		    cave2->player_id, subject2, template_eval(tmpl_spy2));
    }

#ifdef DEBUG
    for (type = 0; type < MAX_SPY; ++type)
    {
	template_iterate(tmpl_spy1, "SPY");
	template_set_fmt(tmpl_spy1, "SPY/chance",  "%g", spy[type].chance);
	template_set_fmt(tmpl_spy1, "SPY/quality", "%g", spy[type].quality);
	template_set_fmt(tmpl_spy1, "SPY/weight",  "%g", spy[type].weight);
    }
#endif

    message_new(database, MSG_CLASS_SPY_REPORT,
		cave1->player_id, subject1, template_eval(tmpl_spy1));
    return result;
}

void artefact_report (db_t *database,
		      const struct Cave *cave, const struct Player *player,
		      const char *artefact_name)
{
    template_t *tmpl_artefact = message_template(player, "artefact");
    const char *subject = message_subject(tmpl_artefact, "TITLE", cave);

    template_context(tmpl_artefact, "MSG");
    template_set(tmpl_artefact, "cave", cave->name);
    template_set(tmpl_artefact, "artefact", artefact_name);

    message_new(database, MSG_CLASS_ARTEFACT,
		cave->player_id, subject, template_eval(tmpl_artefact));
}

void artefact_merging_report (db_t *database,
			      const struct Cave *cave,
			      const struct Player *player,
			      const struct Artefact *key_artefact,
			      const struct Artefact *lock_artefact,
			      const struct Artefact *result_artefact)
{
    struct Artefact_class key_artefact_class,
			  lock_artefact_class,
			  result_artefact_class;
    template_t *tmpl_merge = message_template(player, "merge");
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

static void wonder_prepare_message (template_t *template,
				    const char *message, float steal,
				    const struct Cave *cave, int locale_id,
				    const struct ReportEntity *values,
				    int num, int message_type)
{
    int index;

    template_context(template, "MSG");
    template_set(template, "cave", cave->name);
    template_set(template, "wonder_message", message);

    if (message_type == WONDER_MESSAGE_note)
	template_set(template, "note", "");

    for (index = 0; index < num; ++index)
    {
	double value = values[index].value;

	if (message_type == WONDER_MESSAGE_note)
	    value = fuzzy_wonder_value(value, 0.6);

	if (value)
	{
	    template_iterate(template, "VALUE");
	    template_set(template, "VALUE/name",
			 values[index].object->name[locale_id]);
	    template_set_fmt(template, "VALUE/amount", "%+g", value);
	}
    }

    if (steal > 0)
	template_set_fmt(template, "STOLEN/steal", "%d", (int) (steal * 100));
}

void wonder_report (db_t *database,
		    const struct Player *caster,
		    const struct Cave *cave, const struct Player *target,
		    const struct WonderImpact *impact,
		    const struct ReportEntity *values, int num)
{
    if (caster->player_id != target->player_id &&
	impact->sourceMessageType != WONDER_MESSAGE_none)
    {
	template_t *template = message_template(caster, "wonder");
	const char *subject = message_subject(template, "TITLE", cave);

	/* TODO localize impact->sourceMessage */
	wonder_prepare_message(template, impact->sourceMessage,
			       impact->steal, cave, caster->locale_id,
			       values, num, impact->sourceMessageType);

	message_new(database, MSG_CLASS_WONDER, caster->player_id,
		    subject, template_eval(template));
    }

    if (impact->targetMessageType != WONDER_MESSAGE_none)
    {
	template_t *template = message_template(target, "wonder");
	const char *subject = message_subject(template, "TITLE", cave);

	/* TODO localize impact->targetMessage */
	wonder_prepare_message(template, impact->targetMessage,
			       impact->steal, cave, target->locale_id,
			       values, num, impact->targetMessageType);

	message_new(database, MSG_CLASS_WONDER, target->player_id,
		    subject, template_eval(template));
    }
}

void wonder_end_report (db_t *database,
			const struct Player *caster,
			const struct Cave *cave, const struct Player *target,
			const struct WonderImpact *impact,
			const struct ReportEntity *values, int num)
{
    if (caster->player_id != target->player_id &&
	impact->sourceMessageType != WONDER_MESSAGE_none)
    {
	template_t *template = message_template(caster, "wonder_end");
	const char *subject = message_subject(template, "TITLE", cave);

	wonder_prepare_message(template, "", 0, cave, caster->locale_id,
			       values, num, impact->sourceMessageType);

	message_new(database, MSG_CLASS_WONDER, caster->player_id,
		    subject, template_eval(template));
    }

    if (impact->targetMessageType != WONDER_MESSAGE_none)
    {
	template_t *template = message_template(target, "wonder_end");
	const char *subject = message_subject(template, "TITLE", cave);

	wonder_prepare_message(template, "", 0, cave, target->locale_id,
			       values, num, impact->targetMessageType);

	message_new(database, MSG_CLASS_WONDER, target->player_id,
		    subject, template_eval(template));
    }
}

static void wonder_extend_report_player (db_t *database,
					 const struct Player *player,
					 const struct Cave *cave,
					 const struct Wonder *wonder)
{
    template_t *template = message_template(player, "wonder_extend");
    const char *subject = message_subject(template, "TITLE", cave);
    const struct GameObject *object = (struct GameObject *) wonder;

    template_context(template, "MSG");
    template_set(template, "cave", cave->name);
    template_set(template, "wonder", object->name[player->locale_id]);

    message_new(database, MSG_CLASS_WONDER, player->player_id,
		subject, template_eval(template));
}

void wonder_extend_report (db_t *database,
			   const struct Player *caster,
			   const struct Cave *cave, const struct Player *target,
			   const struct Wonder *wonder,
			   const struct WonderImpact *impact)
{
    if (caster->player_id != target->player_id &&
	impact->sourceMessageType != WONDER_MESSAGE_none)
	wonder_extend_report_player(database, caster, cave, wonder);

    if (impact->targetMessageType != WONDER_MESSAGE_none)
	wonder_extend_report_player(database, target, cave, wonder);
}
