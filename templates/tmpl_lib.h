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

#ifndef PHP_TMPL_LIB_H
#define PHP_TMPL_LIB_H

#include "php_templates.h"

extern void *tmpl_cache;

inline void php_tmpl_dtor_tag(zval**);
ulong php_tmpl_line_num(t_template*, char*);
short int php_tmpl_pre_parse(t_template*);
void php_tmpl_load_path(zval**, char*, int, zval*);
int php_tmpl_set(t_template*, zval*, zval**);
zval** php_tmpl_get_iteration(t_template*, zval*, int);
int php_tmpl_parse(zval**, t_template*, zval*, HashPosition*, zval**);
inline void php_tmpl_parse_check_memory(t_template*, HashPosition*, t_tmpl_tag*, uint, zval**, zval**, uint*);
int php_tmpl_set_array(t_template*, zval*, zval** TSRMLS_DC);
void php_tmpl_load_structure(zval**, t_template*, zval*, long, HashPosition*, int);
void php_tmpl_process_param_array(t_template*, zval*);

#endif	/* PHP_TMPL_LIB_H */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
