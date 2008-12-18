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

#include <stdlib.h>
#include "search.h"

/* Chartable size */
#define ASIZE	256

static void preQsBc(uchar *x, ulong m, ulong qsBc[]) {
register ulong i;

	for(i=0; i < ASIZE; ++i) qsBc[i] = m + 1;
	for(i=0; i < m; ++i) qsBc[x[i]] = m - i;
}

/*
y - haystack
n - haystackLen
x - needle
m - needleLen
*/
unsigned char* search_qs(unsigned char* y, unsigned long n, unsigned char* x, unsigned long m) {
register ulong	j;
register ulong	cmp;
ulong			qsBc[ASIZE];

	if(n < m) return NULL;
	preQsBc(x, m, qsBc);

	j = 0;
	while(j <= n-m) {

/*		if(memcmp(x, y+j, m-1) == 0) return y + j; */

/*		case insensitive search */
		for(cmp = 0; cmp < m; cmp++)
			if(tolower(x[cmp]) != tolower(y[j+cmp])) break;
		if(cmp == m) return y+j;

		j += qsBc[ y[j+m] ];
	}
	return NULL;
}
