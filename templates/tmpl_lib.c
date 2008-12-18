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

#include "php.h"
#include "php_ini.h"
#include "php_templates.h"
#include <stdlib.h>

#include "search.h"
#include "tmpl_lib.h"

/* {{{ php_tmpl_dtor_tag */
inline void php_tmpl_dtor_tag(zval** zval_ptr) {
t_tmpl_tag* tag = (t_tmpl_tag*)Z_STRVAL_PP(zval_ptr);
	TAG_DESTROY(tag);
	FREE_ZVAL(*zval_ptr);
}
/* }}} */

/* {{{ php_tmpl_line_num
	Returns line number in template. Used in error messages. */
ulong php_tmpl_line_num(t_template* tmpl, char* ptr) {
ulong	line_num;
char	*p;

	p = ZV(tmpl->original);
	if(ptr < p || ptr > (p + ZL(tmpl->original))) 
		return 0;

	for(line_num = 1; p < ptr; p++) 
		if(*p == '\n') line_num++;

	return line_num;
}
/* }}} */

/* {{{ ULONG_PAIR_COMPARE */
inline static int ULONG_PAIR_COMPARE(const void* v1, const void* v2) {
ulong_pair	*p1 = (ulong_pair*)v1;
ulong_pair	*p2 = (ulong_pair*)v2;
	return (p1->l == p2->l) ? 0 : ((p1->l > p2->l) ? 1 : -1);
}
/* }}} */

/* {{{ php_tmpl_pre_parse_config 
	Parse the <template> config tag */
#define TMPL_PRE_PARSE_CONFIG_CLEANUP	{	\
	efree(nam);								\
	efree(val);								\
	zval_dtor(zparam); FREE_ZVAL(zparam);	\
}											\

short int php_tmpl_pre_parse_config(t_template* tmpl) {
zval		*zparam;

char	*nam, *val;
uint	nam_len, val_len;

uchar	quote;
register char	*p;
register char	*start;

	nam = (char*)emalloc(TMPL_MAX_TAG_LEN); nam_len = 0;
	val = (char*)emalloc(TMPL_MAX_TAG_LEN); val_len = 0;
	MAKE_STD_ZVAL(zparam); array_init(zparam);

	sprintf(nam, "<%s", TMPL_CONFIG_TAG_NAME);
	nam_len = strlen(nam);

	if(!(p = strstrl_ex(ZV(tmpl->original), ZL(tmpl->original), nam, nam_len))) {
		TMPL_PRE_PARSE_CONFIG_CLEANUP;
		return SUCCESS;
	}

	start = p;
	p += nam_len;

	while(1) {

		/* skip delimiters and check for end of the tag */
		while(*p && '>' != *p && IS_DELIM(*p)) p++;
		if(!(*p) || '>' == *p) {	/* end of tag */
			if('>' == *p) {			/* hide this tag from result output */
				for(++p; *p;) *(start++) = *(++p);
				*start = 0;
				ZL(tmpl->original) -= (p-start);
			}
			break;
		}

		/* get parameter name */
		for(nam_len=0; *p && nam_len < TMPL_MAX_TAG_LEN && !IS_DELIM(*p) && '=' != *p; p++) nam[nam_len++] = *p;
		if(!(*p)) break; else nam[nam_len] = 0;

		if('=' != *p) {
			php_error(E_ERROR, "Invalid configuration tag parameter in template (line:%d)", php_tmpl_line_num(tmpl, p));
			TMPL_PRE_PARSE_CONFIG_CLEANUP;
			return FAILURE;
		}

		/* check if the value is quoted and get the value */
		p++;
		quote = IS_QUOTE(*p) ? *(p++) : 0;

		for(val_len=0; *p && val_len < TMPL_MAX_TAG_LEN && quote ? quote != *p : !IS_DELIM(*p); p++) val[val_len++] = *p;
		if(!(*p)) break; else val[val_len] = 0;

		if(quote && quote != *p) {
			php_error(E_ERROR, "Invalid parameter value in configuration tag in template (line:%d)", php_tmpl_line_num(tmpl, p));
			TMPL_PRE_PARSE_CONFIG_CLEANUP;
			return FAILURE;
		}
		if(quote) p++;

		add_assoc_stringl(zparam, nam, val, val_len, 1);
	}

	php_tmpl_process_param_array(tmpl, zparam);

	TMPL_PRE_PARSE_CONFIG_CLEANUP;
	return SUCCESS;
}
/* }}} */

/* {{{ php_tmpl_pre_parse_search */
inline void php_tmpl_pre_parse_search(t_template* tmpl, ulong_pair** point, const short typ, uchar* buf, ulong len) {
register ulong	i, j, k, shift;
register ulong	cmp;
ulong			bmBc[256];

	if(ZL(tmpl->original) < (int)len) return;

	/* Using "Tuned Boyer-Moore" searching algorythm */
	/* Preprocessing */
	for(i=0; i < 256; ++i) bmBc[i] = len;
	for(i=0; i < len-1; ++i) bmBc[buf[i]] = len - i - 1;

	shift = bmBc[ buf[len-1] ];
	bmBc[ buf[len-1] ] = 0;
	memset(ZV(tmpl->original) + ZL(tmpl->original), buf[len-1], len);
	/* Searching */
	j = 0;
	while(j < (ulong)ZL(tmpl->original)) {
		k = bmBc[ ZV(tmpl->original)[j + len - 1] ];
		while(k != 0) {
			j += k; k = bmBc[ ZV(tmpl->original)[j + len - 1] ];
			j += k; k = bmBc[ ZV(tmpl->original)[j + len - 1] ];
			j += k; k = bmBc[ ZV(tmpl->original)[j + len - 1] ];
		}
		if(/* memcmp(buf, ZV(tmpl->original) + j, len-1) == 0 && */ j < (ulong)ZL(tmpl->original) 
			&& (((tmpl->config_start || tmpl->config_end) && (j < tmpl->config_start || j > tmpl->config_end)) ||
			(!tmpl->config_start && !tmpl->config_end))) {

			for(cmp = 0; cmp < len; cmp++)
				if(tolower(buf[cmp]) != tolower(ZV(tmpl->original)[j+cmp])) break;
			if(cmp == len) {
				PAIR_CHECK(*point);
				(*point)[++((*point)[0].r)].l = j;
				(*point)[(*point)[0].r].r = typ;
			}
		}
		j += shift;
	}
}
/* }}} */

/* {{{ php_tmpl_pre_parse */
#define TMPL_PRE_PARSE_CLEANUP {		\
	PAIR_DESTROY(point);				\
	efree(buf);							\
}										\

#define TMPL_PRE_PARSE_GET_LEN(typ, tag_len) {																			\
	for(close_idx = i+1; close_idx <= point[0].r && point[close_idx].r != (typ); close_idx++);							\
	if(close_idx > point[0].r || (typ) != point[close_idx].r) continue;													\
	for(len = 0; *p && len < MIN(TMPL_MAX_TAG_LEN, point[close_idx].l - point[i].l) && IS_TAG_CHAR(*p); p++, len++);	\
	if(len < 1 || point[close_idx].l-len-(tag_len) != point[i].l) continue;												\
}

short int php_tmpl_pre_parse(t_template* tmpl) {
ulong_pair		*point;
register uchar	*p;

uchar			*buf;
uint			buf_len, buf_alloc, buf_this;

uint			i, j, len, close_idx;
t_tmpl_tag		*tag, *context;
zval			**ztag;

	if(FAILURE == php_tmpl_pre_parse_config(tmpl)) return FAILURE;

	/* Initialize variables */
	PAIR_INIT(point);
	buf_alloc = TMPL_MAX_TAG_LEN+4;
	buf = (uchar*)emalloc(buf_alloc);
	/* Searching algorythm will require larger buffer */
	ZV(tmpl->original) = (char*)erealloc(ZV(tmpl->original), 
		ZL(tmpl->original) + MAX(
			ZL(tmpl->tag_left), MAX(
				ZL(tmpl->tag_right), MAX(
					ZL(tmpl->ctx_ol), MAX(
						ZL(tmpl->ctx_or), MAX(
							ZL(tmpl->ctx_cl), ZL(tmpl->ctx_cr)
						)
					)
				)
			)
		)
	);

	/* Obtain positions of all tags and contexts */
	php_tmpl_pre_parse_search(tmpl, &point, TMPL_TAG, ZV(tmpl->tag_left), ZL(tmpl->tag_left));
	php_tmpl_pre_parse_search(tmpl, &point, TMPL_TAG_END, ZV(tmpl->tag_right), ZL(tmpl->tag_right));
	php_tmpl_pre_parse_search(tmpl, &point, TMPL_CONTEXT_OPEN_LEFT, ZV(tmpl->ctx_ol), ZL(tmpl->ctx_ol));
	php_tmpl_pre_parse_search(tmpl, &point, TMPL_CONTEXT_OPEN_RIGHT, ZV(tmpl->ctx_or), ZL(tmpl->ctx_or));
	php_tmpl_pre_parse_search(tmpl, &point, TMPL_CONTEXT_CLOSE_LEFT, ZV(tmpl->ctx_cl), ZL(tmpl->ctx_cl));
	if(ZL(tmpl->ctx_cr)) php_tmpl_pre_parse_search(tmpl, &point, TMPL_CONTEXT_CLOSE_RIGHT, ZV(tmpl->ctx_cr), ZL(tmpl->ctx_cr));

	if(0 == point[0].r) { TMPL_PRE_PARSE_CLEANUP; return SUCCESS; }

	qsort(&point[1], point[0].r, sizeof(ulong_pair), ULONG_PAIR_COMPARE);

	strcpy(buf, "/"); buf_len = buf_this = 1;

	/* Add root context */
	TAG_INIT(tag);
 	tag->loff = 0;
	tag->roff = ZL(tmpl->original); 
	tag->typ = TMPL_CONTEXT;
	ZVAL_STRINGL(tag->name, "/", 1, 1);
	add_assoc_stringl(tmpl->tags, buf, (char*)tag, sizeof(t_tmpl_tag), 0);
	context = tag;
	context->size = ZL(tmpl->original);

	/* Pre parse template */
	for(i=1; i <= point[0].r; i++) {
		switch(point[i].r) {
		case TMPL_TAG:
			p = ZV(tmpl->original) + point[i].l + ZL(tmpl->tag_left);

			TMPL_PRE_PARSE_GET_LEN(TMPL_TAG_END, ZL(tmpl->tag_left));

			if(buf_alloc < buf_len+len+1) {
				while(buf_alloc < buf_len+len+1) buf_alloc += TMPL_MAX_TAG_LEN;
				buf = (char*)erealloc(buf, buf_alloc);
			}
			if(buf_len > 1) buf[buf_len++] = '/';
			for(j=0; j < len; j++) buf[buf_len++] = tolower(*(p-len+j));
			buf[buf_len] = 0;

			TAG_INIT(tag);
			tag->loff = point[i].l;
			tag->roff = point[close_idx].l + ZL(tmpl->tag_right);
			tag->size = (tag->roff - tag->loff);
			tag->typ = TMPL_TAG;
			tag->tag_num = 1;
			tag->ctx = context;
			ZVAL_STRINGL(tag->name, buf+buf_len-len, len, 1);

			if(FAILURE == zend_hash_find(Z_ARRVAL_P(tmpl->tags), buf, buf_len+1, (void*)&ztag)) {
				/* There's no the tag defined in the current context. Creating one */
				add_assoc_stringl(tmpl->tags, buf, (char*)tag, sizeof(t_tmpl_tag), 0);
				context->tag_num++;
			} else {	/* add another instance of the tag in the same context */
				(Z_TMPL_TAG(ztag)->tag_num)++;
				add_next_index_stringl(tmpl->dup_tag, (char*)tag, sizeof(t_tmpl_tag), 0);
			}
			context->size -= tag->size;

			while(buf_len > 1 && buf[buf_len-1] != '/') buf[--buf_len] = 0;
			if(buf_len > 1) buf[--buf_len] = 0;

			i = close_idx;
			break;

		case TMPL_CONTEXT_OPEN_LEFT:
			p = ZV(tmpl->original) + point[i].l + ZL(tmpl->ctx_ol);

			TMPL_PRE_PARSE_GET_LEN(TMPL_CONTEXT_OPEN_RIGHT, ZL(tmpl->ctx_ol));

			if(buf_alloc < buf_len+len+1) {
				while(buf_alloc < buf_len+len+1) buf_alloc += TMPL_MAX_TAG_LEN;
				buf = (char*)erealloc(buf, buf_alloc);
			}
			if(buf_len > 1) buf[buf_len++] = '/';
			buf_this = buf_len;
			for(j=0; j < len; j++) buf[buf_len++] = tolower(*(p-len+j));
			buf[buf_len] = 0;

			if(SUCCESS == zend_hash_find(Z_ARRVAL_P(tmpl->tags), buf, buf_len+1, (void*)&ztag)) {
				php_error(E_ERROR, "Duplicate context \"%s\" in template (line: %d)", buf, php_tmpl_line_num(tmpl, p));
				TMPL_PRE_PARSE_CLEANUP;
				return FAILURE;
			}

			TAG_INIT(tag);
			tag->loff = point[i].l;
			tag->typ = TMPL_CONTEXT;
			tag->ctx = context;
			ZVAL_STRINGL(tag->name, buf+buf_len-len, len, 1);
			add_assoc_stringl(tmpl->tags, buf, (char*)tag, sizeof(t_tmpl_tag), 0);
			context->tag_num++;
			context = tag;

			i = close_idx;
			break;

		case TMPL_CONTEXT_CLOSE_LEFT:
			p = ZV(tmpl->original) + point[i].l + ZL(tmpl->ctx_cl);

			if(ZL(tmpl->ctx_cr)) {
	
				TMPL_PRE_PARSE_GET_LEN(TMPL_CONTEXT_CLOSE_RIGHT, ZL(tmpl->ctx_cl));

				for(j=0; j < len; j++)
					if(buf[buf_this+j] != tolower(*(p-len+j))) break;
				if(j < len) continue;

			}

			tag = context;
			tag->roff = ZL(tmpl->ctx_cr) ? point[close_idx].l + ZL(tmpl->ctx_cr) : point[i].l + ZL(tmpl->ctx_cl);
			tag->size += (tag->roff - tag->loff);

			while(buf_len > 1 && buf[buf_len-1] != '/') buf[--buf_len] = 0;
			if(buf_len > 1) buf[--buf_len] = 0;
			buf_this = buf_len;
			while(buf_this > 1 && buf[buf_this-1] != '/') --buf_this;
			if(FAILURE == zend_hash_find(Z_ARRVAL_P(tmpl->tags), buf, buf_len+1, (void*)&ztag)) {
				php_error(E_ERROR, "Can't find parent context in template. You should not see this message");
				TMPL_PRE_PARSE_CLEANUP;
				return FAILURE;
			}
			context = (t_tmpl_tag*)Z_STRVAL_PP(ztag);
			context->size -= tag->size;

			if(ZL(tmpl->ctx_cr)) i = close_idx;
			break;
		}
	}
	if(buf_len != 1) {
		php_error(E_ERROR, "Can't continue with an unterminated context \"%s\" in template (line:%d)", buf, php_tmpl_line_num(tmpl, ZV(tmpl->original) + context->loff));
		TMPL_PRE_PARSE_CLEANUP;
		return FAILURE;
	}
	tmpl->size = context->size;

	TMPL_PRE_PARSE_CLEANUP;
	return SUCCESS;
}
/* }}} */

/* {{{ php_tmpl_load_path */
void php_tmpl_load_path(zval** dest, char* local, int local_len, zval* global) {
	char			*buf;
	int				buf_len;
	register char	*p, *q;

	if(local_len && local[0] == '/') {
		buf = (char*)emalloc(local_len + 1);
		memcpy(buf, local, local_len+1);
		buf_len = local_len;
	} else {
		buf = (char*)emalloc(local_len + ZL(global) + 2);
		memcpy(buf, ZV(global), ZL(global));
		buf[ZL(global)] = '/';
		memcpy(buf + 1 + ZL(global), local, local_len+1);
		buf_len = local_len + 1 + ZL(global);
	}

	while((p = strstr(buf, "//"))) {
		for(q = p+1; *q; q++) *(q-1) = *q;
		*(q-1) = 0;
		--buf_len;
	}

	/* check for `..` in the path */
	/* first, remove path elements to the left of `..` */
	for(p = buf; p <= (buf+buf_len-3); p++) {
		if(memcmp(p, "/..", 3) != 0 || (*(p+3) != '/' && *(p+3) != 0)) continue;
		for(q = p-1; q >= buf && *q != '/'; q--, buf_len--);
		--buf_len;
		if(*q == '/') {
			p += 3;
			while(*p) *(q++) = *(p++);
			*q = 0;
			buf_len -= 3;
			p = buf;
		}
	}
	/* second, clear all `..` in the begining of the path
	   because `/../` = `/` */
	while(buf_len > 2 && memcmp(buf, "/..", 3) == 0) {
		for(p = buf+3; *p; p++) *(p-3) = *p;
		*(p-3) = 0;
		buf_len -= 3;
	}
	/* clear `/` at the end of the path */
	while(buf_len > 1 && buf[buf_len-1] == '/') buf[--buf_len] = 0;
	if(!buf_len) { memcpy(buf, "/", 2); buf_len = 1; }

	for(p=buf; *p; p++) *p = tolower(*p);

	zval_dtor(*dest);
	ZVAL_STRINGL(*dest, buf, buf_len, 0);
}
/* }}} */

/* {{{ php_tmpl_set */
int php_tmpl_set(t_template* tmpl, zval* path, zval** data) {
zval		**iteration, *cp_data, **ztag;
t_tmpl_tag	*tag;
char		*p;

	if(FAILURE == zend_hash_find(Z_ARRVAL_P(tmpl->tags), ZV(path), ZL(path)+1, (void*)&ztag)) {
		/* php_error(E_NOTICE, "Can't set value for tag/context \"%s\" which doesn't exist", ZV(path)); */
		return FAILURE;
	}
	tag = Z_TMPL_TAG(ztag);

	if(TMPL_TAG == tag->typ) {
		if((iteration = (zval**)php_tmpl_get_iteration(tmpl, path, TMPL_ITERATION_CURRENT)) == NULL) {
			return FAILURE;
		}
	} else {
		for(p = ZV(path)+ZL(path); p >= ZV(path) && *p != '/'; p--);
		*(p > ZV(path) ? p++ : ++p) = 0;
		ZL(path) = strlen(ZV(path));
		if((iteration = (zval**)php_tmpl_get_iteration(tmpl, path, TMPL_ITERATION_CURRENT)) == NULL) {
			return FAILURE;
		}
	}

	convert_to_string_ex(data); 
	MAKE_STD_ZVAL(cp_data); 
	ZVAL_STRINGL(cp_data, Z_STRVAL_PP(data), Z_STRLEN_PP(data), 1);

	if(SUCCESS == zend_hash_find(Z_ARRVAL_PP(iteration), ZV(tag->name), ZL(tag->name)+1, (void*)&ztag)) {
		if(IS_ARRAY == Z_TYPE_PP(ztag)) {
			/* MEMORY LEAK CAUSED BY THE NEXT LINE !!! */
			zend_hash_del(Z_ARRVAL_PP(iteration), ZV(tag->name), ZL(tag->name)+1);
		} else {
			tmpl->size -= (Z_STRLEN_PP(ztag) * tag->tag_num);
		}
	}
	zend_hash_update(Z_ARRVAL_PP(iteration), ZV(tag->name), ZL(tag->name)+1, (void*)&cp_data, sizeof(zval**), NULL);
	tmpl->size += (ZL(cp_data) * tag->tag_num);

	return SUCCESS;
}
/* }}} */

/* {{{ php_tmpl_get_iteration */
#define TMPL_GET_ITERATION_FAILED {	\
	zval_dtor(new_val);				\
	FREE_ZVAL(new_val);				\
	return NULL;					\
}

#define TMPL_GET_ITERATION_ADD_SIZE(path, len) {														\
	if(SUCCESS == zend_hash_find(Z_ARRVAL_P(tmpl->tags), (path), (len)+1, (void*)&ztag)) {				\
		tmpl->size += ((t_tmpl_tag*)Z_STRVAL_PP(ztag))->size;											\
	} else php_error(E_ERROR, "Unable to increment template's size for \"%s\". You should not see this message", (path));	\
}

zval** php_tmpl_get_iteration(t_template* tmpl, zval* path, int need_new) {
	zval			**cur_data, **new_data, *new_val, **ztag;
	register uchar	*p, *q;
	t_tmpl_tag		*tag;

	if(FAILURE == zend_hash_find(Z_ARRVAL_P(tmpl->tags), ZV(path), ZL(path)+1, (void*)&ztag)) {
		php_error(E_ERROR, "Undefined tag/context \"%s\"", ZV(path));
		return NULL;
	}
	tag = (t_tmpl_tag*)Z_STRVAL_PP(ztag);
	if(TMPL_TAG == tag->typ && (need_new & ~TMPL_ITERATION_EXISTING)) {
		php_error(E_ERROR, "Can't realize context operation on a tag");
		return NULL;
	}

	cur_data = &tmpl->data; q = ZV(path);
	while(1) {
		p = ++q;
		if(!(*p)) break;
		if((q = strstr(p, "/")))
			*q = 0; 
		else {
			if(TMPL_TAG == tag->typ) 
				break; 
			else 
				q = ZV(path) + ZL(path);
		}

		/* Get into current iteration or create one */
		if(0 == zend_hash_num_elements(Z_ARRVAL_PP(cur_data))) {

			if(TMPL_ITERATION_EXISTING & need_new) return NULL;

			MAKE_STD_ZVAL(new_val);
			if(array_init(new_val) == FAILURE) { TMPL_GET_ITERATION_FAILED; }
			zend_hash_next_index_insert(Z_ARRVAL_PP(cur_data), &new_val, sizeof(zval**), NULL);

			TMPL_GET_ITERATION_ADD_SIZE(ZV(path), q-ZV(path));

		}
		cur_data = Z_ARRVAL_PP(cur_data)->pListTail->pData;

		/* Get into context or create one */
		if(FAILURE == zend_hash_find(Z_ARRVAL_PP(cur_data), p, q-p+1, (void*)&cur_data)) {

			if(TMPL_ITERATION_EXISTING & need_new) return NULL;

			MAKE_STD_ZVAL(new_val);
			if(array_init(new_val) == FAILURE) { TMPL_GET_ITERATION_FAILED; }
			zend_hash_add(Z_ARRVAL_PP(cur_data), p, q-p+1, &new_val, sizeof(zval**), NULL);
			cur_data = Z_ARRVAL_PP(cur_data)->pListTail->pData;

			TMPL_GET_ITERATION_ADD_SIZE(ZV(path), q-ZV(path));

		}

		if(IS_ARRAY != Z_TYPE_PP(cur_data)) {
			return NULL;
			/* php_error(E_ERROR, "Unable to iterate context \"%s\" which has been converted to tag", ZV(path)); */
		}

		if(q && q != (ZV(path)+ZL(path))) *q = '/'; else break;
	}

	if(TMPL_ITERATION_PARENT & need_new) return cur_data;
	new_data = cur_data;

	if(IS_ARRAY != Z_TYPE_PP(cur_data)) { 
		if(TMPL_TAG == tag->typ) {
			php_error(E_ERROR, "\"%s\" is inaccessible due to conversion of one of its parent contexts to a tag", ZV(path));
		} else {
			php_error(E_ERROR, "The context \"%s\" has been converted to tag", ZV(path));
		}
		TMPL_GET_ITERATION_FAILED;
	}

	/* Change to the last iteration */
	if(0 == zend_hash_num_elements(Z_ARRVAL_PP(cur_data))) {
		if(TMPL_ITERATION_EXISTING & need_new) return NULL;

		MAKE_STD_ZVAL(new_val);
		if(FAILURE == array_init(new_val)) { TMPL_GET_ITERATION_FAILED; }
		zend_hash_next_index_insert(Z_ARRVAL_PP(cur_data), &new_val, sizeof(zval**), NULL);

		if(TMPL_TAG == tag->typ) {
			for(q=ZV(path)+ZL(path); q > ZV(path) && *q != '/'; q--);
		} else q = ZV(path)+ZL(path);
		*q = 0;
		TMPL_GET_ITERATION_ADD_SIZE(ZV(path), q-ZV(path));
		if(q != ZV(path)+ZL(path)) *q = '/';
	}
	cur_data = Z_ARRVAL_PP(cur_data)->pListTail->pData;

	/* Iterate if necessary */
	if((TMPL_ITERATION_NEW & need_new) && zend_hash_num_elements(Z_ARRVAL_PP(cur_data)) > 0) {
		if(TMPL_ITERATION_EXISTING & need_new) return NULL;

		MAKE_STD_ZVAL(new_val);
		if(FAILURE == array_init(new_val)) { TMPL_GET_ITERATION_FAILED; }
		zend_hash_next_index_insert(Z_ARRVAL_PP(new_data), &new_val, sizeof(zval**), NULL);
		cur_data = Z_ARRVAL_PP(new_data)->pListTail->pData;

		TMPL_GET_ITERATION_ADD_SIZE(ZV(path), ZL(path));
	}

	return cur_data;
}
/* }}} */

/* {{{ php_tmpl_parse */
#define TMPL_PARSE_CLEANUP {					\
	zval_dtor(new_path); FREE_ZVAL(new_path);	\
}

#define TMPL_PARSE_DEST_ADD(off, l) {												\
	if((l) > 0) {																	\
		memcpy(Z_STRVAL_PP(dest)+Z_STRLEN_PP(dest), ZV(tmpl->original)+(off), (l));	\
		Z_STRLEN_PP(dest) += (l);													\
		Z_STRVAL_PP(dest)[Z_STRLEN_PP(dest)] = 0;									\
	}																				\
}

int php_tmpl_parse(zval** dest, t_template*	tmpl, zval* path, HashPosition* pos, zval** data) {
uint			tag_num;
int				i;
zval			**ztag;
t_tmpl_tag		*tag, *ctx;
char			*buf;
ulong			buf_alloc;
zval			*new_path;
zval			**tag_data;
HashPosition	cur_pos, saved_pos, dup_tag_pos;
uint			offset;
unsigned short	need_skip;

zval		**iteration;

char		*key_tag_key;
uint		key_tag_len;
ulong		key_tag_index;

	/* Initialize variables */
	buf_alloc = TMPL_MAX_TAG_LEN;
	buf = (char*)emalloc(buf_alloc);
	MAKE_STD_ZVAL(new_path); ZVAL_STRINGL(new_path, buf, 0, 0);
	dup_tag_pos = NULL;

	/* Get the context's info from tags array */
	if(!pos) {	/* This is not a recursion call. Look for the context's last non-empty iteration */
		zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(tmpl->tags), &cur_pos);
		i = 0;	/* we'll set this flag when found the context */

		do {
			if(HASH_KEY_IS_STRING != zend_hash_get_current_key_ex(Z_ARRVAL_P(tmpl->tags), &key_tag_key, &key_tag_len, &key_tag_index, 0, &cur_pos)) break;
			if(SUCCESS != zend_hash_get_current_data_ex(Z_ARRVAL_P(tmpl->tags), (void**)&ztag, &cur_pos)) break;
			tag = ctx = (t_tmpl_tag*)Z_STRVAL_PP(ztag);
			if(TMPL_CONTEXT != ctx->typ) continue;

			/* get out of the loop if we're inside of the context we need */
			if((uint)ZL(path) == key_tag_len-1 && !memcmp(ZV(path), key_tag_key, ZL(path))) {
				i = 1;
				break;
			}

		} while(SUCCESS == zend_hash_move_forward_ex(Z_ARRVAL_P(tmpl->tags), &cur_pos));

		if(!i) { TMPL_PARSE_CLEANUP; return FAILURE; }
		tag_data = php_tmpl_get_iteration(tmpl, path, TMPL_ITERATION_PARENT);
	} else {
		cur_pos = *pos;
		tag = ctx = (t_tmpl_tag*)Z_STRVAL_PP((zval**)(cur_pos->pData));
		tag_data = data;
	}

	saved_pos = cur_pos;

	/* Check all iterations in the opened context */
	zend_hash_internal_pointer_reset(Z_ARRVAL_PP(tag_data));
	do {
		if(FAILURE == zend_hash_get_current_data(Z_ARRVAL_PP(tag_data), (void*)&iteration)) break;
		/* Uncomment the following line to avoid parsing of empty iterations */
		/* if(pos && !zend_hash_num_elements(Z_ARRVAL_PP(iteration))) break; */

		/* Initialize the offset from the template's content begining */
		offset = (1 == ZL(ctx->name) && '/' == ZV(ctx->name)[0]) ? 0 : ctx->loff + ZL(tmpl->ctx_ol) + ZL(ctx->name) + ZL(tmpl->ctx_or);

		/* We only need tags which are inside of the context */
		cur_pos = saved_pos;
		for(tag_num = 0; tag_num < ctx->tag_num; tag_num++) {
			if(FAILURE == zend_hash_move_forward_ex(Z_ARRVAL_P(tmpl->tags), &cur_pos)) break;
			if(FAILURE == zend_hash_get_current_data_ex(Z_ARRVAL_P(tmpl->tags), (void**)&ztag, &cur_pos)) break;
			tag = (t_tmpl_tag*)Z_STRVAL_PP(ztag);

			if(NULL == dup_tag_pos && zend_hash_num_elements(Z_ARRVAL_P(tmpl->dup_tag))) {
				zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(tmpl->dup_tag), &dup_tag_pos);
				do {
					if(FAILURE == zend_hash_get_current_data_ex(Z_ARRVAL_P(tmpl->dup_tag), (void**)&ztag, &dup_tag_pos)) break;
				} while(Z_TMPL_TAG(ztag)->loff < offset && SUCCESS == zend_hash_move_forward_ex(Z_ARRVAL_P(tmpl->dup_tag), &dup_tag_pos));
			}

			php_tmpl_parse_check_memory(tmpl, &dup_tag_pos, tag, TMPL_TAG, iteration, dest, &offset);

			TMPL_PARSE_DEST_ADD(offset, tag->loff - offset);
			offset = tag->roff;

			need_skip = (FAILURE == zend_hash_find(Z_ARRVAL_PP(iteration), ZV(tag->name), ZL(tag->name)+1, (void*)&ztag));

			if(!need_skip) {

				if(TMPL_CONTEXT == tag->typ && IS_ARRAY == Z_TYPE_PP(ztag)) {	/* Processing a context */

					/* Make a recursive call */
					if(buf_alloc <= (unsigned)(ZL(path)+1+ZL(tag->name)+1)) {
						while(buf_alloc <= (unsigned)(ZL(path)+1+ZL(tag->name)+1)) buf_alloc <<= 1;
						ZV(new_path) = (char*)erealloc(ZV(new_path), buf_alloc);
					}
					sprintf(ZV(new_path), (1 == ZL(path) && '/' == ZV(path)[0]) ? "%s%s" : "%s/%s", ZV(path), ZV(tag->name));
					ZL(new_path) = ZL(path) + ZL(tag->name) + ((1 == ZL(path) && '/' == ZV(path)[0]) ? 0 : 1);
					php_tmpl_parse(dest, tmpl, new_path, &cur_pos, ztag);

				} else {	/* Processing a tag */

					TMPL_PARSE_DEST_ADD(Z_STRVAL_PP(ztag)-Z_STRVAL_P(tmpl->original), Z_STRLEN_PP(ztag));
					if(TMPL_CONTEXT == tag->typ) need_skip = 1;

				}
			}

			if(need_skip && TMPL_CONTEXT == tag->typ) {
				for(i=0; i < (int)tag->tag_num; i++) {
					if(FAILURE == zend_hash_move_forward_ex(Z_ARRVAL_P(tmpl->tags), &cur_pos)) break;
					if(FAILURE == zend_hash_get_current_data_ex(Z_ARRVAL_P(tmpl->tags), (void**)&ztag, &cur_pos)) break;
					i -= (TMPL_CONTEXT == Z_TMPL_TAG(ztag)->typ) ? Z_TMPL_TAG(ztag)->tag_num : 0;
				}
			}
		}

		php_tmpl_parse_check_memory(tmpl, &dup_tag_pos, ctx, TMPL_CONTEXT, iteration, dest, &offset);

		if(1 != ZL(path) || '/' != ZV(path)[0]) {
			TMPL_PARSE_DEST_ADD(offset, ctx->roff - offset - ZL(tmpl->ctx_cl) - ZL(ctx->name)*(ZL(tmpl->ctx_cr)?1:0) - ZL(tmpl->ctx_cr));
		} else {
			TMPL_PARSE_DEST_ADD(offset, ZL(tmpl->original)-offset);
		}

	} while(SUCCESS == zend_hash_move_forward(Z_ARRVAL_PP(tag_data)));

	if(pos) *pos = cur_pos;

	TMPL_PARSE_CLEANUP;
	return SUCCESS;
}
/* }}} */

/* {{{ php_tmpl_parse_check_memory */
inline void php_tmpl_parse_check_memory(t_template* tmpl, HashPosition *dup_tag_pos, t_tmpl_tag* tag, uint tag_mod, zval** iteration, zval** dest, uint* offset) {
zval			**dup_ztag;
t_tmpl_tag		*dup_tag;

	if(NULL == *dup_tag_pos || !zend_hash_num_elements(Z_ARRVAL_P(tmpl->dup_tag))) return;
	/* The next line has been added to avoid skiping of duplicate tags in 
	   some circumstances. This is sort of a dirty fix and needs to be 
	   optimized for speed. */
	zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(tmpl->dup_tag), dup_tag_pos);

	do {
		if(FAILURE == zend_hash_get_current_data_ex(Z_ARRVAL_P(tmpl->dup_tag), (void*)&dup_ztag, dup_tag_pos)) break;
		dup_tag = Z_TMPL_TAG(dup_ztag);

		if(*offset > dup_tag->loff) continue;
		if(TMPL_TAG == tag_mod) {
			if(dup_tag->ctx != tag->ctx && dup_tag->loff < tag->loff) continue;
			if(dup_tag->ctx != tag->ctx || dup_tag->loff >= tag->loff) break;
		} else {
			if(dup_tag->ctx != tag && dup_tag->loff < tag->roff) continue;
			if(dup_tag->ctx != tag || dup_tag->loff > tag->roff) break;
		}

		TMPL_PARSE_DEST_ADD(*offset, dup_tag->loff - *offset);
		*offset = dup_tag->roff;

		if(FAILURE == zend_hash_find(Z_ARRVAL_PP(iteration), ZV(dup_tag->name), ZL(dup_tag->name)+1, (void*)&dup_ztag)) continue;

		TMPL_PARSE_DEST_ADD(Z_STRVAL_PP(dup_ztag)-Z_STRVAL_P(tmpl->original), Z_STRLEN_PP(dup_ztag));

	} while(SUCCESS == zend_hash_move_forward_ex(Z_ARRVAL_P(tmpl->dup_tag), dup_tag_pos));

}
/* }}} */

/* {{{ php_tmpl_set_array() */
int php_tmpl_set_array(t_template* tmpl, zval* path, zval** data TSRMLS_DC) {
zval			*local_path, **tag;
t_template		*inner_template;
zval			*inr_dest, *inr_path;

int				key_typ;
char			*key;
uint			key_len;
ulong			key_index;

int				return_value = FAILURE;

	if(!zend_hash_num_elements(Z_ARRVAL_PP(data))) return FAILURE;

	MAKE_STD_ZVAL(local_path); ZVAL_EMPTY_STRING(local_path);

	zend_hash_internal_pointer_reset(Z_ARRVAL_PP(data));
	while(1) {
		if(FAILURE == zend_hash_get_current_data(Z_ARRVAL_PP(data), (void*)&tag)) break;
		key_typ = zend_hash_get_current_key_ex(Z_ARRVAL_PP(data), &key, &key_len, &key_index, 0, NULL);
		zend_hash_move_forward(Z_ARRVAL_PP(data));

		if(HASH_KEY_NON_EXISTANT == key_typ) break;
		else if(HASH_KEY_IS_STRING == key_typ)
			php_tmpl_load_path(&local_path, key, key_len-1, path);

		if(IS_ARRAY == Z_TYPE_PP(tag)) {

			if(HASH_KEY_IS_LONG == key_typ) {
				php_tmpl_get_iteration(tmpl, path, TMPL_ITERATION_NEW);
				if(SUCCESS == php_tmpl_set_array(tmpl, path, tag TSRMLS_CC)) return_value = SUCCESS;
			} else if(HASH_KEY_IS_STRING == key_typ) {
				if(SUCCESS == php_tmpl_set_array(tmpl, local_path, tag TSRMLS_CC)) return_value = SUCCESS;
			}

		} else if(IS_RESOURCE == Z_TYPE_PP(tag)) {

			if((inner_template = (t_template*)zend_list_find(Z_LVAL_PP(tag), &le_templates)) == NULL) { 
				php_error(E_WARNING, "Supplied argument is not a valid Template handle resource");
				zval_dtor(local_path); FREE_ZVAL(local_path);
				return FAILURE; 
			}

			MAKE_STD_ZVAL(inr_dest); ZVAL_EMPTY_STRING(inr_dest);
			MAKE_STD_ZVAL(inr_path); ZVAL_STRINGL(inr_path, ZV(inner_template->path), ZL(inner_template->path), 1);

			php_tmpl_parse(&inr_dest, inner_template, path, NULL, NULL);
			if(SUCCESS == php_tmpl_set(tmpl, local_path, &inr_dest)) return_value = SUCCESS;

			zval_dtor(inr_path); FREE_ZVAL(inr_path);
			zval_dtor(inr_dest); FREE_ZVAL(inr_dest);

		} else {
			convert_to_string_ex(tag);
			if(SUCCESS == php_tmpl_set(tmpl, local_path, tag)) return_value = SUCCESS;
		}
	}

	zval_dtor(local_path); FREE_ZVAL(local_path);
	return return_value;
}
/* }}} */

/* {{{ php_tmpl_load_structure() */
void php_tmpl_load_structure(zval** result, t_template* tmpl, zval* path, long mask, HashPosition* pos, int mod) {
t_tmpl_tag		*tag;
zval			**ztag, *new_value, *new_path;
char			*key;
uint			key_len;
ulong			key_index;
char			*p;
HashPosition	cur_pos;
int				tag_max, tag_cur;

	if(!pos) {
		if(zend_hash_num_elements(Z_ARRVAL_P(tmpl->tags)) < 2) return;
		zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(tmpl->tags), &cur_pos);
	} else cur_pos = *pos;

	while(SUCCESS == zend_hash_move_forward_ex(Z_ARRVAL_P(tmpl->tags), &cur_pos)) {

		zend_hash_get_current_key_ex(Z_ARRVAL_P(tmpl->tags), &key, &key_len, &key_index, 0, &cur_pos);
		if(key_len-1 < (uint)ZL(path) || (ZL(path) && memcmp(key, ZV(path), ZL(path)))) {
			if(pos) 
				break; 
			else 
				continue;
		}

		if(FAILURE == zend_hash_get_current_data_ex(Z_ARRVAL_P(tmpl->tags), (void*)&ztag, &cur_pos)) break;
		tag = (t_tmpl_tag*)Z_STRVAL_PP(ztag);
		tag_max = tag->tag_num; tag_cur = 0;
		if(!(tag->typ & mask)) continue;

		MAKE_STD_ZVAL(new_value); 

		if(TMPL_LONG == mod || TMPL_SHORT == mod) {
			if(TMPL_SHORT == mod) {
				for(p = key+key_len-2; p > key && *p != '/'; p--);
				p++;
			} else p = key;
			ZVAL_STRINGL(new_value, p, (TMPL_LONG == mod) ? key_len-1 : key_len-1-(p-key), 1);
			if((TMPL_TAG | TMPL_CONTEXT) == mask && TMPL_CONTEXT == tag->typ) {
				ZV(new_value) = (char*)erealloc(ZV(new_value), ZL(new_value)+2);
				memcpy(ZV(new_value)+ZL(new_value), "/", 2);
				ZL(new_value) += 1;
			}
			zend_hash_next_index_insert(Z_ARRVAL_PP(result), (zval**)&new_value, sizeof(zval*), NULL);
			continue;
		}

		if(TMPL_CONTEXT == tag->typ) {

			array_init(new_value);
			zend_hash_add(Z_ARRVAL_PP(result), ZV(tag->name), ZL(tag->name)+1, (zval**)&new_value, sizeof(zval*), NULL);

			MAKE_STD_ZVAL(new_path); ZVAL_EMPTY_STRING(new_path);
			php_tmpl_load_path(&new_path, ZV(tag->name), ZL(tag->name), path);

			php_tmpl_load_structure((zval**)(Z_ARRVAL_PP(result)->pListTail->pData), tmpl, new_path, mask, &cur_pos, mod);

			zval_dtor(new_path); FREE_ZVAL(new_path);

		} else {
			ZVAL_STRINGL(new_value, ZV(tag->name), ZL(tag->name), 1);
			zend_hash_next_index_insert(Z_ARRVAL_PP(result), (zval**)&new_value, sizeof(zval*), NULL);
		}
	}
	if(pos) *pos = cur_pos;
}
/* }}} */

/* {{{ php_tmpl_process_param_array() */
#define TMPL_SET_PARAM(s) {										\
	zval_dtor(s);												\
	ZVAL_STRINGL((s), Z_STRVAL_PP(val), Z_STRLEN_PP(val), 1);	\
	param_set = 1;												\
	break;														\
}

void php_tmpl_process_param_array(t_template *tmpl, zval *zparam) {
char*	param[] =		{"left",	"right",	"ctx_ol",	"ctx_or",	"ctx_cl",	"ctx_cr", NULL};
uint	param_len[] =	{4,			5,			6,			6,			6,			6};
short	i;
short	param_set;

HashPosition	pos;
zval			**val;
char			*nam;
ulong			nam_idx;
uint			nam_len;

	if(IS_ARRAY != Z_TYPE_P(zparam) || !zend_hash_num_elements(Z_ARRVAL_P(zparam))) return;

	zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(zparam), &pos);
	while(1) {
		if(FAILURE == zend_hash_get_current_data_ex(Z_ARRVAL_P(zparam), (void*)&val, &pos)) break;
		if(HASH_KEY_IS_STRING != zend_hash_get_current_key_ex(Z_ARRVAL_P(zparam), &nam, &nam_len, &nam_idx, 0, &pos)) break;
		--nam_len;

		for(param_set=0, i=0; 0 == param_set && param[i]; i++) {
			if(nam_len >= TMPL_MAX_TAG_LEN || nam_len != param_len[i] || strncasecmp(nam, param[i], param_len[i])) continue;

			switch(i) {
				case 0 : TMPL_SET_PARAM(tmpl->tag_left);
				case 1 : TMPL_SET_PARAM(tmpl->tag_right);
				case 2 : TMPL_SET_PARAM(tmpl->ctx_ol);
				case 3 : TMPL_SET_PARAM(tmpl->ctx_or);
				case 4 : TMPL_SET_PARAM(tmpl->ctx_cl);
				case 5 : TMPL_SET_PARAM(tmpl->ctx_cr);
			}

		}

		if(0 == param_set) {
			php_error(E_WARNING, "Ignoring unknown template configuration parameter \"%s\"", nam);
		} else if(!Z_STRLEN_PP(val) && i != 6) {
			php_error(E_ERROR, "Can't continue with empty configuration parameter \"%s\"", nam);
			return;
		}

		if(FAILURE == zend_hash_move_forward_ex(Z_ARRVAL_P(zparam), &pos)) break;
	}
}
/* }}} */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
