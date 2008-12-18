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

#ifndef TMPL_SEARCH
#define TMPL_SEARCH "Quick Search"

#ifndef NULL
#define NULL (uchar*)0
#endif

#ifndef uchar
#define uchar unsigned char
#endif
#ifndef ulong
#define ulong unsigned long
#endif

uchar*	search_qs(uchar*, unsigned long, uchar*, unsigned long);

#define strstr_ex(haystack, needle) search_qs(haystack, strlen(haystack), needle, strlen(needle))
#define strstrl_ex(haystack, haystackLen, needle, needleLen) search_qs(haystack, haystackLen, needle, needleLen)

#endif	/* TMPL_SEARCH */
