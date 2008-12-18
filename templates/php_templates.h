/*
    +---------------------------------------------------------------------+
    | php_templates                                                       |
    +---------------------------------------------------------------------+
    | Copyright (C) 2001, 2002 Maxim Poltarak                             |
    +---------------------------------------------------------------------+
    | This library is free software; you can redistribute it and/or       |
    | modify it under the terms of the version 2.1 of the GNU Lesser      |
    | General Public License as published by the Free Software Foundation.|
    +---------------------------------------------------------------------+
    | This library is distributed in the hope that it will be useful,     |
    | but WITHOUT ANY WARRANTY; without even the implied warranty of      |
    | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU   |
    | Lesser General Public License for more details.                     |
    +---------------------------------------------------------------------+
    | You should have received a copy of the GNU Lesser General Public    |
    | License along with this library; if not, write to the Free Software |
    | Foundation, Inc., 59 Temple Place, Suite 330, Boston,               |
    | MA  02111-1307  USA                                                 |
    +---------------------------------------------------------------------+
*/

#ifndef PHP_TEMPLATES_H
#define PHP_TEMPLATES_H

#ifdef PHP_WIN32
#define PHP_TEMPLATES_API __declspec(dllexport)
#else
#define PHP_TEMPLATES_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

extern zend_module_entry templates_module_entry;
#define phpext_templates_ptr &templates_module_entry

#if PHP_API_VERSION >= 20010901
#	define TMPL_PHP_4_1		1
#endif

#if PHP_API_VERSION >= 20020918
#	define TMPL_PHP_4_3		1
#endif

#define TMPL_VERSION		"1.7"

#define TMPL_CACHE_ENABLED	0

#define TMPL_TAG_LEFT		"{"
#define TMPL_TAG_RIGHT		"}"

#define TMPL_CTX_OL			"<tmpl:"
#define TMPL_CTX_OR			">"
#define TMPL_CTX_CL			"</tmpl:"
#define TMPL_CTX_CR			">"

#define TMPL_CONFIG_TAG_NAME	"template"

#define TMPL_UNDEFINED				0
#define TMPL_TAG					1
#define TMPL_CONTEXT				2
#define TMPL_TAG_END				4
#define TMPL_CONTEXT_OPEN_LEFT		8
#define TMPL_CONTEXT_OPEN_RIGHT		16
#define TMPL_CONTEXT_CLOSE_LEFT		32
#define TMPL_CONTEXT_CLOSE_RIGHT	64
#define TMPL_CONFIG_TAG				128

#define TMPL_LONG			1
#define TMPL_SHORT			2
#define TMPL_TREE			4

#define TMPL_ITERATION_CURRENT	0
#define TMPL_ITERATION_NEW		1
#define TMPL_ITERATION_PARENT	2
#define TMPL_ITERATION_EXISTING	4

#define TMPL_MAX_TAG_LEN	256

PHP_MINIT_FUNCTION(templates);
PHP_MSHUTDOWN_FUNCTION(templates);
PHP_RINIT_FUNCTION(templates);
PHP_RSHUTDOWN_FUNCTION(templates);
PHP_MINFO_FUNCTION(templates);

PHP_FUNCTION(tmpl_open);
PHP_FUNCTION(tmpl_load);
PHP_FUNCTION(tmpl_close);
PHP_FUNCTION(tmpl_set);
PHP_FUNCTION(tmpl_set_global);
PHP_FUNCTION(tmpl_parse);
PHP_FUNCTION(tmpl_iterate);
PHP_FUNCTION(tmpl_context);
PHP_FUNCTION(tmpl_type_of);
PHP_FUNCTION(tmpl_get);
PHP_FUNCTION(tmpl_structure);
PHP_FUNCTION(tmpl_unset);

/* True global resources - no need for thread safety here */
extern int le_templates;

#ifndef uchar
#define uchar unsigned char
#endif
#ifndef ulong
#define ulong unsigned long
#endif

typedef struct _ulong_pair {
	uint	l;
	uint	r;
} ulong_pair;

typedef struct _t_tmpl_tag {
	zval				*name;
	unsigned short		typ;
	uint				tag_num;
	uint				loff, roff;
	long				size;
	struct _t_tmpl_tag	*ctx;
} t_tmpl_tag;

#define TAG_INIT(tag) {									\
	(tag) = (t_tmpl_tag*)emalloc(sizeof(t_tmpl_tag));	\
	memset((char*)tag, 0, sizeof(t_tmpl_tag));			\
	MAKE_STD_ZVAL((tag)->name);							\
	(tag)->tag_num = 0;									\
}

#define TAG_DESTROY(tag) {		\
	zval_dtor((tag)->name);		\
	FREE_ZVAL((tag)->name);		\
	efree(tag);					\
}

#define Z_TMPL_TAG(ztag) ((t_tmpl_tag*)Z_STRVAL_PP(ztag))

typedef struct _t_template {
	ulong			config_start, config_end;			/* <template> tag position */
	zval			*tag_left, *tag_right;				/* tag delimiters */
	zval			*ctx_ol, *ctx_or, *ctx_cl, *ctx_cr;	/* context delimiters */
	zval			*tags;			/* sd (single dimensioned) array : tags[path] = (t_tmpl_tag*) */
	zval			*original;		/* string  : original template content */
	zval			*path;			/* string : current path */
	zval			*data;			/* md array : template data */
	int				size;			/* size of parsed template */
	zval			*dup_tag;		/* sd array : path[offset] */
} t_template;

#define IS_TAG_CHAR(c)	(isalnum(c) || (c) == '_' || (c) == '-')
#define IS_QUOTE(c) ((c) == '\'' || (c) == '"')
#define IS_DELIM(c) (IS_QUOTE(c) || (c) == ' ' || (c) == '\n' || (c) == '\r' || (c) == '\t' || (c) == '/')

/* Shortcuts */
#define ZV(z) ((uchar*)Z_STRVAL_P(z))
#define ZL(z) Z_STRLEN_P(z)

/* Theese two macros are just for debuging purposes */
#define PRINT_ZVAL(z)	{ zend_printf("<pre>\n"); zend_print_zval_r((z), 1); zend_printf("\n</pre>\n\n"); }
#define EXIT_ZVAL(z)	{ PRINT_ZVAL(z); exit(0); }

/* {{{ Pair macros */
#define PAIR_INIT_SIZE	64

#define PAIR_INIT(p) {												\
	(p) = (ulong_pair*)emalloc(sizeof(ulong_pair)*PAIR_INIT_SIZE);	\
	(p)[0].l = PAIR_INIT_SIZE;										\
	(p)[0].r = 0;													\
}

#define PAIR_INIT_EX(p, siz) {									\
	(p) = (ulong_pair*)emalloc(sizeof(ulong_pair)*(siz));		\
	(p)[0].l = (siz);											\
	(p)[0].r = 0;												\
}

#define PAIR_CHECK(p) {									\
	if( ((p)[0].r + 1) >= (p)[0].l ) {					\
		(p)[0].l = (p)[0].l << 1;						\
		(p) = erealloc((p), sizeof(*(p)) * (p)[0].l);	\
	}													\
}

#define PAIR_DESTROY(p) { efree(p); }
/* }}} */

/* 
  	Declare any global variables you may need between the BEGIN
	and END macros here:	*/

ZEND_BEGIN_MODULE_GLOBALS(templates)
	/* short cache; */
	char	*left, *right;
	char	*ctx_ol, *ctx_or;
	char	*ctx_cl, *ctx_cr;
	zval	*tmpl_param;
ZEND_END_MODULE_GLOBALS(templates)


/* In every utility function you add that needs to use variables 
   in php_templates_globals, call TSRM_FETCH(); after declaring other 
   variables used by that function, or better yet, pass in TSRMG_CC
   after the last function argument and declare your utility function
   with TSRMG_DC after the last declared argument.  Always refer to
   the globals in your function as TEMPLATES_G(variable).  You are 
   encouraged to rename these macros something shorter, see
   examples in any other php module directory.
*/

#ifdef ZTS
#define TMPL_G(v) TSRMG(templates_globals_id, zend_templates_globals *, v)
#else
#define TMPL_G(v) (templates_globals.v)
#endif

#endif	/* PHP_TEMPLATES_H */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
