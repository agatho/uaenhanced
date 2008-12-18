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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "ext/standard/php_fopen_wrappers.h"
#include "php_templates.h"
#include "tmpl_lib.h"

extern PHPAPI size_t php_sock_fread(char *buf, size_t maxlen, int socket);
extern PHPAPI int php_sock_close(int socket);

#ifdef TMPL_PHP_4_1
#	define HashKeyLen	uint
#else
#	define HashKeyLen	ulong
#	define TSRMLS_DC 
#	define TSRMLS_CC 
#endif

#define PHP_FILE_BUF_SIZE	1024

/* True global resources - no need for thread safety here */
int		le_templates;

/* {{{ templates_functions[] */
function_entry templates_functions[] = {
	PHP_FE(tmpl_open,		NULL)
	PHP_FE(tmpl_load,		NULL)
	PHP_FE(tmpl_close,		NULL)
	PHP_FE(tmpl_set,		NULL)
	PHP_FE(tmpl_set_global,	NULL)
	PHP_FE(tmpl_parse,		NULL)
	PHP_FE(tmpl_iterate,	NULL)
	PHP_FE(tmpl_context,	NULL)
	PHP_FE(tmpl_type_of,	NULL)
	PHP_FE(tmpl_get,		NULL)
	PHP_FE(tmpl_structure,	NULL)
	PHP_FE(tmpl_unset,		NULL)
	{NULL, NULL, NULL}
};
/* }}} */

static t_template* php_tmpl_init(char*, long, zval** TSRMLS_DC);

/* {{{ templates_module_entry */
zend_module_entry templates_module_entry = {
#ifdef TMPL_PHP_4_1
	STANDARD_MODULE_HEADER,
#endif
	"templates",
	templates_functions,
	PHP_MINIT(templates),
	PHP_MSHUTDOWN(templates),
	PHP_RINIT(templates),
	PHP_RSHUTDOWN(templates),
	PHP_MINFO(templates),
#ifdef TMPL_PHP_4_1
    TMPL_VERSION,
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

ZEND_DECLARE_MODULE_GLOBALS(templates)

#ifdef COMPILE_DL_TEMPLATES
ZEND_GET_MODULE(templates)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini */
PHP_INI_BEGIN()
    /* STD_PHP_INI_ENTRY("templates.cache",      "1", PHP_INI_ALL, OnUpdateBool, cache, zend_templates_globals, templates_globals) */
    STD_PHP_INI_ENTRY("templates.left", TMPL_TAG_LEFT, PHP_INI_ALL, OnUpdateString, left, zend_templates_globals, templates_globals)
    STD_PHP_INI_ENTRY("templates.right", TMPL_TAG_RIGHT, PHP_INI_ALL, OnUpdateString, right, zend_templates_globals, templates_globals)
    STD_PHP_INI_ENTRY("templates.ctx_ol", TMPL_CTX_OL, PHP_INI_ALL, OnUpdateString, ctx_ol, zend_templates_globals, templates_globals)
    STD_PHP_INI_ENTRY("templates.ctx_or", TMPL_CTX_OR, PHP_INI_ALL, OnUpdateString, ctx_or, zend_templates_globals, templates_globals)
    STD_PHP_INI_ENTRY("templates.ctx_cl", TMPL_CTX_CL, PHP_INI_ALL, OnUpdateString, ctx_cl, zend_templates_globals, templates_globals)
    STD_PHP_INI_ENTRY("templates.ctx_cr", TMPL_CTX_CR, PHP_INI_ALL, OnUpdateString, ctx_cr, zend_templates_globals, templates_globals)
PHP_INI_END()
/* }}} */

/* {{{ php_tmpl_dtor */
inline void php_tmpl_dtor(t_template* tmpl) {
	zval_dtor(tmpl->original);	FREE_ZVAL(tmpl->original);

	zval_dtor(tmpl->tag_left);	FREE_ZVAL(tmpl->tag_left);
	zval_dtor(tmpl->tag_right);	FREE_ZVAL(tmpl->tag_right);
	zval_dtor(tmpl->ctx_ol);	FREE_ZVAL(tmpl->ctx_ol);
	zval_dtor(tmpl->ctx_or);	FREE_ZVAL(tmpl->ctx_or);
	zval_dtor(tmpl->ctx_cl);	FREE_ZVAL(tmpl->ctx_cl);
	zval_dtor(tmpl->ctx_cr);	FREE_ZVAL(tmpl->ctx_cr);

	zval_dtor(tmpl->tags);		FREE_ZVAL(tmpl->tags);
	zval_dtor(tmpl->path);		FREE_ZVAL(tmpl->path);
	zval_dtor(tmpl->data);		FREE_ZVAL(tmpl->data);
	zval_dtor(tmpl->dup_tag);	FREE_ZVAL(tmpl->dup_tag);

	efree(tmpl);
}
/* }}} */

/* {{{ tmpl_resource_dtor */
static void tmpl_resource_dtor(zend_rsrc_list_entry *rsrc TSRMLS_DC) {
	php_tmpl_dtor((t_template*)rsrc->ptr);
}
/* }}} */


/* {{{ php_templates_init_globals */
/* Uncomment this function if you have INI entries	*/
static void php_templates_init_globals(zend_templates_globals *templates_globals) {
	/* templates_globals->cache = 1; */
	templates_globals->left = TMPL_TAG_LEFT;
	templates_globals->right = TMPL_TAG_RIGHT;
	templates_globals->ctx_ol = TMPL_CTX_OL;
	templates_globals->ctx_or = TMPL_CTX_OR;
	templates_globals->ctx_cl = TMPL_CTX_CL;
	templates_globals->ctx_cr = TMPL_CTX_CR;
	templates_globals->tmpl_param = NULL;
}
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(templates) {

	ZEND_INIT_MODULE_GLOBALS(templates, php_templates_init_globals, NULL);

	REGISTER_INI_ENTRIES();

	le_templates = zend_register_list_destructors_ex(tmpl_resource_dtor, NULL, "Template handle", module_number);

	REGISTER_LONG_CONSTANT("TMPL_UNKNOWN",       TMPL_UNDEFINED, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("TMPL_UNDEFINED",     TMPL_UNDEFINED, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("TMPL_TAG",                 TMPL_TAG, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("TMPL_CONTEXT",         TMPL_CONTEXT, CONST_CS | CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("TMPL_LONG",   TMPL_LONG, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("TMPL_SHORT", TMPL_SHORT, CONST_CS | CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("TMPL_TREE",   TMPL_TREE, CONST_CS | CONST_PERSISTENT);

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(templates) {

	/* uncomment this line if you have INI entries */
	UNREGISTER_INI_ENTRIES();

	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(templates) {

	MAKE_STD_ZVAL(TMPL_G(tmpl_param)); array_init(TMPL_G(tmpl_param));

	add_assoc_stringl(TMPL_G(tmpl_param), "left", TMPL_G(left), strlen(TMPL_G(left)), 1);
	add_assoc_stringl(TMPL_G(tmpl_param), "right", TMPL_G(right), strlen(TMPL_G(right)), 1);
	add_assoc_stringl(TMPL_G(tmpl_param), "ctx_ol", TMPL_G(ctx_ol), strlen(TMPL_G(ctx_ol)), 1);
	add_assoc_stringl(TMPL_G(tmpl_param), "ctx_or", TMPL_G(ctx_or), strlen(TMPL_G(ctx_or)), 1);
	add_assoc_stringl(TMPL_G(tmpl_param), "ctx_cl", TMPL_G(ctx_cl), strlen(TMPL_G(ctx_cl)), 1);
	add_assoc_stringl(TMPL_G(tmpl_param), "ctx_cr", TMPL_G(ctx_cr), strlen(TMPL_G(ctx_cr)), 1);

	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(templates) {

	zval_dtor(TMPL_G(tmpl_param)); FREE_ZVAL(TMPL_G(tmpl_param));

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(templates) {
char	buf[TMPL_MAX_TAG_LEN*3];

	php_info_print_table_start();

	php_info_print_table_row(2, "Templates Support", "enabled");

#ifdef TMPL_PHP_4_1
	php_info_print_table_row(2, "Engine version", templates_module_entry.version);
#endif

	sprintf(buf, "%stag_name%s", TMPL_TAG_LEFT, TMPL_TAG_RIGHT);
	php_info_print_table_row(2, "Template tag", buf);

	sprintf(buf, "%scontext_name%s %scontext_name%s", TMPL_CTX_OL, TMPL_CTX_OR, TMPL_CTX_CL, TMPL_CTX_CR);
	php_info_print_table_row(2, "Template context", buf);

	php_info_print_table_end();

	DISPLAY_INI_ENTRIES();

	php_info_print_table_start();
	php_info_print_table_row(2,	"WWW", "http://php-templates.sourceforge.net/");
	php_info_print_table_end();
}
/* }}} */

/* {{{ proto resource tmpl_open(string filename [, array delimiters])
   Open template file and return Template handle or FAILURE on error */
#define TMPL_OPEN_CLEANUP {	\
	php_tmpl_dtor(tmpl);	\
	efree(buf);			\
}

PHP_FUNCTION(tmpl_open) {
	zval			**filename, **delimiters = NULL;
	t_template		*tmpl;
	char			*buf;
	size_t			buf_len=0;
#ifdef TMPL_PHP_4_3
	php_stream		*stream = NULL;
#else
	FILE*			fp;
	size_t			get_len;
	int				issock=0, socketd=0;
#endif

	if(!(ZEND_NUM_ARGS() == 2 && zend_get_parameters_ex(2, &filename, &delimiters) == SUCCESS && Z_TYPE_PP(delimiters) == IS_ARRAY)
	&& !(ZEND_NUM_ARGS() == 1 && zend_get_parameters_ex(1, &filename) == SUCCESS)) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}
		
	/* load template */
	convert_to_string_ex(filename);

	/* check for open base_dir restrictions */
    if (php_check_open_basedir(Z_STRVAL_PP(filename) TSRMLS_CC)) {
        RETURN_FALSE;
    }

#ifdef TMPL_PHP_4_3
	stream = php_stream_open_wrapper(Z_STRVAL_PP(filename), "rb", IGNORE_PATH|ENFORCE_SAFE_MODE|REPORT_ERRORS, NULL);
	if(!stream) {
		char	*tmp = estrndup(Z_STRVAL_PP(filename), Z_STRLEN_PP(filename));
		php_strip_url_passwd(tmp);
		php_error(E_ERROR, "Can't open template \"%s\" - %s", tmp, strerror(errno));
		efree(tmp);
		RETURN_FALSE;
	}

	buf_len = php_stream_copy_to_mem(stream, &buf, PHP_STREAM_COPY_ALL, 0);
	php_stream_close(stream);

	if(0 == buf_len) buf = (char*)emalloc(1);
#else
	fp = php_fopen_wrapper(Z_STRVAL_PP(filename), "rb", ENFORCE_SAFE_MODE, &issock, &socketd, NULL TSRMLS_CC);
	if(!fp && !socketd) {
		if(issock != BAD_URL) {
			char	*tmp = estrndup(Z_STRVAL_PP(filename), Z_STRLEN_PP(filename));
			php_strip_url_passwd(tmp);
			php_error(E_ERROR, "Can't open template \"%s\" - %s", tmp, strerror(errno));
			efree(tmp);
		}
		RETURN_FALSE;
	}

	buf = (char*)emalloc(PHP_FILE_BUF_SIZE+1); buf_len = 0;
	while((get_len = FP_FREAD(buf+buf_len, PHP_FILE_BUF_SIZE, socketd, fp, issock)) > 0) {
		buf_len += get_len;
		buf = (char*)erealloc(buf, buf_len + PHP_FILE_BUF_SIZE + 1);
	}
	if(issock) SOCK_FCLOSE(socketd); else fclose(fp);
#endif

	buf[buf_len] = 0;

	if(!(tmpl = php_tmpl_init(buf, buf_len, delimiters TSRMLS_CC))) { TMPL_OPEN_CLEANUP; RETURN_FALSE; }

	/* Pre-parse template */
	if(FAILURE == php_tmpl_pre_parse(tmpl)) { TMPL_OPEN_CLEANUP; RETURN_FALSE; }

	ZEND_REGISTER_RESOURCE(return_value, tmpl, le_templates);
}
/* }}} */

/* {{{ proto resource tmpl_load(string content [, array delimiters])
   Load template from string */
PHP_FUNCTION(tmpl_load) {
	zval			**content, **delimiters = NULL;
	t_template		*tmpl;
	char			*buf;

	if(!(ZEND_NUM_ARGS() == 2 && zend_get_parameters_ex(2, &content, &delimiters) == SUCCESS && Z_TYPE_PP(delimiters) == IS_ARRAY)
	&& !(ZEND_NUM_ARGS() == 1 && zend_get_parameters_ex(1, &content) == SUCCESS)) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	convert_to_string_ex(content);

	if(Z_STRLEN_PP(content) < 1) {
		buf = (char*)emalloc(1);
		buf[0] = 0;
	} else {
		buf = (char*)estrndup(Z_STRVAL_PP(content), Z_STRLEN_PP(content)+1);
		buf[Z_STRLEN_PP(content)] = 0;
	}

	tmpl = php_tmpl_init(buf, Z_STRLEN_PP(content), (2 == ZEND_NUM_ARGS()) ? delimiters : NULL TSRMLS_CC);

	/* Pre-parse template */
	if(FAILURE == php_tmpl_pre_parse(tmpl)) { RETURN_FALSE; }

	ZEND_REGISTER_RESOURCE(return_value, tmpl, le_templates);
}
/* }}} */

/* {{{ php_tmpl_init(char* content, long content_len, zval** delimiters) */
t_template* php_tmpl_init(char* content, long content_len, zval** delimiters TSRMLS_DC) {
t_template		*tmpl;
char			*tag_type[] = {"open tag string", "close tag string", "context name"};
zval			*iteration;

	/* allocate and initialize Template structure */
	tmpl = (t_template*)emalloc(sizeof(t_template));
	memset(tmpl, 0, sizeof(t_template));
	MAKE_STD_ZVAL(tmpl->tag_left);	ZVAL_STRING(tmpl->tag_left,     TMPL_TAG_LEFT, 1);
	MAKE_STD_ZVAL(tmpl->tag_right);	ZVAL_STRING(tmpl->tag_right,   TMPL_TAG_RIGHT, 1);
	MAKE_STD_ZVAL(tmpl->ctx_ol);	ZVAL_STRING(tmpl->ctx_ol,		  TMPL_CTX_OL, 1);
	MAKE_STD_ZVAL(tmpl->ctx_or);	ZVAL_STRING(tmpl->ctx_or,		  TMPL_CTX_OR, 1);
	MAKE_STD_ZVAL(tmpl->ctx_cl);	ZVAL_STRING(tmpl->ctx_cl,		  TMPL_CTX_CL, 1);
	MAKE_STD_ZVAL(tmpl->ctx_cr);	ZVAL_STRING(tmpl->ctx_cr,		  TMPL_CTX_CR, 1);

	MAKE_STD_ZVAL(tmpl->tags);
	ALLOC_HASHTABLE_REL(Z_ARRVAL_P(tmpl->tags));
	zend_hash_init(Z_ARRVAL_P(tmpl->tags), 0, NULL, (dtor_func_t)php_tmpl_dtor_tag, 0);
	tmpl->tags->type = IS_ARRAY;
	tmpl->size = 0;

	MAKE_STD_ZVAL(tmpl->dup_tag);
	ALLOC_HASHTABLE_REL(Z_ARRVAL_P(tmpl->dup_tag));
	zend_hash_init(Z_ARRVAL_P(tmpl->dup_tag), 0, NULL, (dtor_func_t)php_tmpl_dtor_tag, 0);
	tmpl->dup_tag->type = IS_ARRAY;

	MAKE_STD_ZVAL(tmpl->path);		ZVAL_STRINGL(tmpl->path, "/", 1, 1);
	MAKE_STD_ZVAL(tmpl->original);	ZVAL_STRINGL(tmpl->original, content, content_len, 0);
	MAKE_STD_ZVAL(tmpl->data);		array_init(tmpl->data);

	MAKE_STD_ZVAL(iteration);	array_init(iteration);
	zend_hash_next_index_insert(Z_ARRVAL_P(tmpl->data), &iteration, sizeof(zval*), NULL);

	/* Change template and context tag names if passed in the second parameter */
	php_tmpl_process_param_array(tmpl, TMPL_G(tmpl_param));
	if(delimiters) php_tmpl_process_param_array(tmpl, *delimiters);

	return tmpl;
}
/* }}} */

#define TMPL_GET_RESOURCE(t, id) {														\
	ZEND_FETCH_RESOURCE((t), t_template *, (id), -1, "Template handle", le_templates);	\
	if((t) == NULL) { RETURN_FALSE; }													\
}

/* {{{ proto bool tmpl_set(resource id, string path, string value)
	Assign value to the template tag addressed by path */
PHP_FUNCTION(tmpl_set) {
	zval		**id, **arg1, **arg2;
	zval		*path, *inr_dest, *inr_path;
	t_template	*tmpl, *inner_template;
	char		*buf;

	RETVAL_FALSE;
	if(2 == ZEND_NUM_ARGS() && SUCCESS == zend_get_parameters_ex(2, &id, &arg1) && IS_ARRAY == Z_TYPE_PP(arg1)) {

		TMPL_GET_RESOURCE(tmpl, id);

		if(SUCCESS == php_tmpl_set_array(tmpl, tmpl->path, arg1 TSRMLS_CC)) { RETVAL_TRUE; }

	} else if(3 == ZEND_NUM_ARGS() && SUCCESS == zend_get_parameters_ex(3, &id, &arg1, &arg2)) {
		convert_to_string_ex(arg1); 
		TMPL_GET_RESOURCE(tmpl, id);

		MAKE_STD_ZVAL(path); ZVAL_EMPTY_STRING(path);
		php_tmpl_load_path(&path, Z_STRVAL_PP(arg1), Z_STRLEN_PP(arg1), tmpl->path);

		if(IS_ARRAY == Z_TYPE_PP(arg2)) {

			if(SUCCESS == php_tmpl_set_array(tmpl, path, arg2 TSRMLS_CC)) { RETVAL_TRUE; }

		} else if (IS_RESOURCE == Z_TYPE_PP(arg2)) {

			if((inner_template = (t_template*)zend_list_find(Z_LVAL_PP(arg2), &le_templates)) != NULL) { 

				buf = (char*)emalloc(inner_template->size + 1);
				MAKE_STD_ZVAL(inr_dest); ZVAL_STRINGL(inr_dest, buf, 0, 0);
				MAKE_STD_ZVAL(inr_path); ZVAL_STRINGL(inr_path, "/", 1, 0);

				php_tmpl_parse(&inr_dest, inner_template, inr_path, NULL, NULL);
				if(SUCCESS == php_tmpl_set(tmpl, path, &inr_dest)) { RETVAL_TRUE; }

				FREE_ZVAL(inr_path);
				zval_dtor(inr_dest); FREE_ZVAL(inr_dest);

			} else {
				php_error(E_WARNING, "Supplied argument is not a valid Template handle resource");
			}

		} else {

			if(SUCCESS == php_tmpl_set(tmpl, path, arg2)) { RETVAL_TRUE; }

		}

		zval_dtor(path); FREE_ZVAL(path);

	} else {
		WRONG_PARAM_COUNT;
	}

}
/* }}} */

/* {{{ proto bool tmpl_set_global(int id, string tag, string value)
	   Assign value to all instances of a tag in every context */
PHP_FUNCTION(tmpl_set_global) {
	zval		**id, **arg1, **arg2;
	zval		*path;
	t_template	*tmpl;

	t_tmpl_tag	*tag;
	zval		**ztag;
	char		*key_tag_key;
	ulong		key_tag_index;
	uint		key_tag_len;

	RETVAL_FALSE;
	if(3 != ZEND_NUM_ARGS() || SUCCESS != zend_get_parameters_ex(3, &id, &arg1, &arg2)) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	convert_to_string_ex(arg1); 
	convert_to_string_ex(arg2);
	TMPL_GET_RESOURCE(tmpl, id);

	if(!zend_hash_num_elements(Z_ARRVAL_P(tmpl->tags))) { RETURN_TRUE; }

	MAKE_STD_ZVAL(path);

	zend_hash_internal_pointer_reset(Z_ARRVAL_P(tmpl->tags));
	do {
		if(HASH_KEY_IS_STRING != zend_hash_get_current_key_ex(Z_ARRVAL_P(tmpl->tags), &key_tag_key, &key_tag_len, &key_tag_index, 0, NULL)) break;
		if(SUCCESS != zend_hash_get_current_data(Z_ARRVAL_P(tmpl->tags), (void**)&ztag)) break;
		tag = (t_tmpl_tag*)Z_STRVAL_PP(ztag);

		if(TMPL_TAG == tag->typ && ZL(tag->name) == Z_STRLEN_PP(arg1) && !strncasecmp(ZV(tag->name), Z_STRVAL_PP(arg1), ZL(tag->name))) {

			ZVAL_STRINGL(path, key_tag_key, key_tag_len-1, 0);

			if(NULL == php_tmpl_get_iteration(tmpl, path, TMPL_ITERATION_EXISTING)) continue;

			if(FAILURE == php_tmpl_set(tmpl, path, arg2)) {
				php_error(E_NOTICE, "Can't set \"%s\" globally in case of \"%s\"", ZV(tag->name), ZV(path));
			}

		}
	} while(SUCCESS == zend_hash_move_forward(Z_ARRVAL_P(tmpl->tags)));

	FREE_ZVAL(path);

	RETVAL_TRUE;
}
/* }}} */

/* {{{ proto string tmpl_parse(int id)
   Parse template and return template's content */
PHP_FUNCTION(tmpl_parse) {
	zval			**id, **arg1;
	t_template		*tmpl;
	zval			*dest;
	zval			*path;
	zval			**ztag;
	char			*buf;

	if(ZEND_NUM_ARGS() == 2 && zend_get_parameters_ex(2, &id, &arg1) == SUCCESS) {
		TMPL_GET_RESOURCE(tmpl, id);
		convert_to_string_ex(arg1);

		MAKE_STD_ZVAL(path); ZVAL_EMPTY_STRING(path);
		php_tmpl_load_path(&path, Z_STRVAL_PP(arg1), Z_STRLEN_PP(arg1), tmpl->path);
		if(NULL == php_tmpl_get_iteration(tmpl, path, TMPL_ITERATION_CURRENT)) { RETURN_FALSE; }

	} else if(ZEND_NUM_ARGS() == 1 && zend_get_parameters_ex(1, &id) == SUCCESS) {
		TMPL_GET_RESOURCE(tmpl, id);

		MAKE_STD_ZVAL(path); ZVAL_STRINGL(path, "/", 1, 1);

	} else {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	if(FAILURE == zend_hash_find(Z_ARRVAL_P(tmpl->tags), ZV(path), ZL(path)+1, (void*)&ztag)) {
		RETURN_FALSE;
	}
	buf = (char*)emalloc(tmpl->size + 1);
	MAKE_STD_ZVAL(dest); ZVAL_STRINGL(dest, buf, 0, 0);

	if(FAILURE == php_tmpl_parse(&dest, tmpl, path, NULL, NULL)) {
		zval_dtor(dest); FREE_ZVAL(dest);
		RETVAL_FALSE;
	} else {
		RETVAL_STRINGL(ZV(dest), ZL(dest), 0);
	}
	zval_dtor(path); FREE_ZVAL(path);
}
/* }}} */

/* {{{ proto bool tmpl_iterate(int id, string path)
   Iterate path */
PHP_FUNCTION(tmpl_iterate) {
	zval		**id, **path;
	zval		*real_path, **iteration;
	t_template	*tmpl;

	if(!(ZEND_NUM_ARGS() == 2 && zend_get_parameters_ex(2, &id, &path) == SUCCESS)
	&& !(ZEND_NUM_ARGS() == 1 && zend_get_parameters_ex(1, &id) == SUCCESS)) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}
	TMPL_GET_RESOURCE(tmpl, id);

	MAKE_STD_ZVAL(real_path); ZVAL_EMPTY_STRING(real_path);
	if(2 == ZEND_NUM_ARGS()) {
		convert_to_string_ex(path);
		php_tmpl_load_path(&real_path, Z_STRVAL_PP(path), Z_STRLEN_PP(path), tmpl->path);
	} else {
		ZVAL_STRINGL(real_path, ZV(tmpl->path), ZL(tmpl->path), 1);
	}

	iteration = php_tmpl_get_iteration(tmpl, real_path, TMPL_ITERATION_NEW);

	zval_dtor(real_path); FREE_ZVAL(real_path);
	if(iteration == NULL) {	RETURN_FALSE; }

	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool tmpl_close(int id)
   Close template and free its resources */
PHP_FUNCTION(tmpl_close) {
	zval		**id;

	if(ZEND_NUM_ARGS() != 1 || zend_get_parameters_ex(1, &id) != SUCCESS || IS_RESOURCE != Z_TYPE_PP(id)) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	if(zend_list_delete(Z_LVAL_PP(id)) == FAILURE) {
		RETURN_FALSE;
	}

	RETURN_TRUE;
}
/* }}} */

/* {{{ proto string tmpl_context(int id [, string context])
   Set and/or return current context */
PHP_FUNCTION(tmpl_context) {
	zval		**id, **path;
	zval		*real_path, **ztag;
	t_template	*tmpl;

	if(!(ZEND_NUM_ARGS() == 2 && zend_get_parameters_ex(2, &id, &path) == SUCCESS)
	&& !(ZEND_NUM_ARGS() == 1 && zend_get_parameters_ex(1, &id) == SUCCESS)) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	TMPL_GET_RESOURCE(tmpl, id);

	if(2 == ZEND_NUM_ARGS()) {
		convert_to_string_ex(path);

		MAKE_STD_ZVAL(real_path); ZVAL_EMPTY_STRING(real_path);
		php_tmpl_load_path(&real_path, Z_STRVAL_PP(path), Z_STRLEN_PP(path), tmpl->path);

		if(FAILURE == zend_hash_find(Z_ARRVAL_P(tmpl->tags), ZV(real_path), ZL(real_path)+1, (void*)&ztag)) {
			zval_dtor(real_path); FREE_ZVAL(real_path);
			RETVAL_FALSE; 
		} else {
			zval_dtor(tmpl->path);
			ZVAL_STRINGL(tmpl->path, Z_STRVAL_P(real_path), Z_STRLEN_P(real_path), 0);
			RETVAL_STRINGL(Z_STRVAL_P(tmpl->path), Z_STRLEN_P(tmpl->path), 1);
		}

	} else {
		RETVAL_STRINGL(Z_STRVAL_P(tmpl->path), Z_STRLEN_P(tmpl->path), 1);
	}
}
/* }}} */

/* {{{ proto long tmpl_type_of(int id, string path)
   Returns the type of an element or 0 if it doesn't exist */
PHP_FUNCTION(tmpl_type_of) {
	zval		**id, **path;
	zval		*real_path, **ztag;
	t_template	*tmpl;

	if(!(2 == ZEND_NUM_ARGS() && SUCCESS == zend_get_parameters_ex(2, &id, &path))) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	TMPL_GET_RESOURCE(tmpl, id);
	convert_to_string_ex(path);

	RETVAL_LONG(TMPL_UNDEFINED);
	MAKE_STD_ZVAL(real_path); ZVAL_EMPTY_STRING(real_path);

	php_tmpl_load_path(&real_path, Z_STRVAL_PP(path), Z_STRLEN_PP(path), tmpl->path);
	if(SUCCESS == zend_hash_find(Z_ARRVAL_P(tmpl->tags), ZV(real_path), ZL(real_path)+1, (void*)&ztag)) {
		RETVAL_LONG(((t_tmpl_tag*)Z_STRVAL_PP(ztag))->typ);
	}

	zval_dtor(real_path); FREE_ZVAL(real_path);
}
/* }}} */

/* {{{ proto long tmpl_get(int id, string path)
   Returns the value of a tag or a context in the last iteration */
PHP_FUNCTION(tmpl_get) {
	zval		**id, **path;
	zval		*real_path, *result, **ztag;
	t_template	*tmpl;
	t_tmpl_tag	*tag;

	if(!(2 == ZEND_NUM_ARGS() && SUCCESS == zend_get_parameters_ex(2, &id, &path))
		&& !(1 == ZEND_NUM_ARGS() && SUCCESS == zend_get_parameters_ex(1, &id))) {
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	TMPL_GET_RESOURCE(tmpl, id);
	if(2 == ZEND_NUM_ARGS()) {
		convert_to_string_ex(path);
	} else path = &tmpl->path;
	RETVAL_FALSE;

	MAKE_STD_ZVAL(real_path); ZVAL_EMPTY_STRING(real_path);
	php_tmpl_load_path(&real_path, Z_STRVAL_PP(path), Z_STRLEN_PP(path), tmpl->path);

	if(SUCCESS == zend_hash_find(Z_ARRVAL_P(tmpl->tags), ZV(real_path), ZL(real_path)+1, (void*)&ztag)) {
		tag = (t_tmpl_tag*)Z_STRVAL_PP(ztag);
		if((ztag = php_tmpl_get_iteration(tmpl, real_path, TMPL_ITERATION_EXISTING))) {
			if(TMPL_TAG == tag->typ) {
				if(SUCCESS == zend_hash_find(Z_ARRVAL_PP(ztag), ZV(tag->name), ZL(tag->name)+1, (void*)&ztag)) {
					RETVAL_STRINGL(Z_STRVAL_PP(ztag), Z_STRLEN_PP(ztag), 1);
				} else RETVAL_STRINGL("", 0, 1);
			} else if(TMPL_CONTEXT == tag->typ) {
				MAKE_STD_ZVAL(result); array_init(result);
				zend_hash_copy(Z_ARRVAL_P(result), Z_ARRVAL_PP(ztag), NULL, NULL, sizeof(zval*));
				zval_dtor(return_value);
				*return_value = *result;
				zval_copy_ctor(return_value);
			}
		}
	} else {
		php_error(E_NOTICE, "Tag/context \"%s\" doesn't exist", ZV(real_path));
	}

	zval_dtor(real_path); FREE_ZVAL(real_path);
}
/* }}} */

/* {{{ proto array tmpl_structure(int id [, string path [, long mask [, long mod]]])
   Returns the structure of tags and contexts in the temlpate */
PHP_FUNCTION(tmpl_structure) {
	zval		**id, **path, **mask, **mod;
	zval		*result, *real_path;
	long		typ_mask;
	int			typ_mod;
	t_template	*tmpl;

	MAKE_STD_ZVAL(real_path); ZVAL_EMPTY_STRING(real_path);
	typ_mask = 0; typ_mod = 0;

	if(
		(4 != ZEND_NUM_ARGS() || FAILURE == zend_get_parameters_ex(4, &id, &path, &mask, &mod))
		&&
		(3 != ZEND_NUM_ARGS() || FAILURE == zend_get_parameters_ex(3, &id, &path, &mask))
		&&
		(2 != ZEND_NUM_ARGS() || FAILURE == zend_get_parameters_ex(2, &id, &path))
		&&
		(1 != ZEND_NUM_ARGS() || FAILURE == zend_get_parameters_ex(1, &id))		) {
		zval_dtor(real_path); FREE_ZVAL(real_path);
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	TMPL_GET_RESOURCE(tmpl, id);
	if(ZEND_NUM_ARGS() > 1) {
		convert_to_string_ex(path);
		php_tmpl_load_path(&real_path, Z_STRVAL_PP(path), Z_STRLEN_PP(path), tmpl->path);
	}
	if(ZEND_NUM_ARGS() > 2) {
		convert_to_long_ex(mask);
		typ_mask = Z_LVAL_PP(mask) & (TMPL_TAG | TMPL_CONTEXT);
	}
	if(ZEND_NUM_ARGS() > 3) {
		convert_to_long_ex(mod);
		typ_mod = Z_LVAL_PP(mod) & (TMPL_LONG | TMPL_SHORT | TMPL_TREE);
	}

	if(!ZL(real_path)) {
		zval_dtor(real_path);
		ZVAL_STRINGL(real_path, "/", 1, 1);
	}
	if(!typ_mask) typ_mask = TMPL_TAG;
	if(!typ_mod) typ_mod = TMPL_LONG;

	MAKE_STD_ZVAL(result); array_init(result);
	php_tmpl_load_structure(&result, tmpl, real_path, typ_mask, NULL, typ_mod);

	if(!zend_hash_num_elements(Z_ARRVAL_P(result))) {
		RETVAL_FALSE;
	} else {
		zval_dtor(return_value);
		*return_value = *result;
		zval_copy_ctor(return_value);

	}
	zval_dtor(real_path); FREE_ZVAL(real_path);
}
/* }}} */

/* {{{ proto bool tmpl_unset(int id [, string path])
   Unsets context */
PHP_FUNCTION(tmpl_unset) {
	zval		**id, **path;
	zval		*real_path, *parent_path;
	t_template	*tmpl;
	zval		**iteration;
	ulong		i;

	MAKE_STD_ZVAL(real_path); ZVAL_EMPTY_STRING(real_path);

	if(
		(2 != ZEND_NUM_ARGS() || FAILURE == zend_get_parameters_ex(2, &id, &path))
		&&
		(1 != ZEND_NUM_ARGS() || FAILURE == zend_get_parameters_ex(1, &id))		) {
		zval_dtor(real_path); FREE_ZVAL(real_path);
		WRONG_PARAM_COUNT;
		RETURN_FALSE;
	}

	TMPL_GET_RESOURCE(tmpl, id);

	if(2 == ZEND_NUM_ARGS()) {
		convert_to_string_ex(path);
		php_tmpl_load_path(&real_path, Z_STRVAL_PP(path), Z_STRLEN_PP(path), tmpl->path);
	} else {
		zval_dtor(real_path);
		ZVAL_STRINGL(real_path, ZV(tmpl->path), ZL(tmpl->path), 1);
	}

	if(1 == ZL(real_path) && '/' == ZV(real_path)[0]) {

		zend_hash_clean(Z_ARRVAL_P(tmpl->data));
		tmpl->size = 0;
		RETVAL_TRUE;

	} else {

		for(i = ZL(real_path); i > 0 && '/' != ZV(real_path)[i]; i--);
		MAKE_STD_ZVAL(parent_path);
		ZVAL_STRINGL(parent_path, ZV(real_path), i+1, 1);
		ZV(parent_path)[i ? i : 1] = 0;
		ZL(parent_path) = strlen(ZV(parent_path));

		if(NULL == (iteration = php_tmpl_get_iteration(tmpl, parent_path, TMPL_ITERATION_CURRENT))) {
			RETVAL_FALSE;
		} else {
			zend_hash_del(Z_ARRVAL_PP(iteration), ZV(real_path)+i+1, ZL(real_path)-i);
			RETVAL_TRUE;
		}

		zval_dtor(parent_path); FREE_ZVAL(parent_path);

	}

	zval_dtor(real_path); FREE_ZVAL(real_path);
}

/* }}} */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 tw=78 fdm=marker
 * vim<600: sw=4 ts=4 tw=78
 */
