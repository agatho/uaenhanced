dnl $Id: config.m4,v 1.4 2003/11/26 15:11:32 su1d Exp $
dnl config.m4 for extension templates

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

dnl PHP_ARG_WITH(templates, for templates support,
dnl Make sure that the comment is aligned:
dnl [  --with-templates             Include templates support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(templates, whether to enable templates support,
dnl Make sure that the comment is aligned:
[  --enable-templates           Enable templates support])

if test "$PHP_TEMPLATES" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-templates -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/templates.h"  # you most likely want to change this
  dnl if test -r $PHP_TEMPLATES/; then # path given as parameter
  dnl   TEMPLATES_DIR=$PHP_TEMPLATES
  dnl else # search default path list
  dnl   AC_MSG_CHECKING(for templates files in default path)
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       TEMPLATES_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$TEMPLATES_DIR"; then
  dnl   AC_MSG_RESULT(not found)
  dnl   AC_MSG_ERROR(Please reinstall the templates distribution)
  dnl fi

  dnl # --with-templates -> add include path
  dnl PHP_ADD_INCLUDE($TEMPLATES_DIR/include)

  dnl # --with-templates -> chech for lib and symbol presence
  dnl LIBNAME=templates # you may want to change this

  LIBSYMBOL=templates # you most likely want to change this 

  dnl old_LIBS=$LIBS
  dnl LIBS="$LIBS -L$TEMPLATES_DIR/lib -lm -ldl"
  dnl AC_CHECK_LIB($LIBNAME, $LIBSYMBOL, [AC_DEFINE(HAVE_TEMPLATESLIB,1,[ ])],
  dnl			[AC_MSG_ERROR(wrong templates lib version or lib not found)])
  dnl LIBS=$old_LIBS
  dnl
  dnl PHP_SUBST(TEMPLATES_SHARED_LIBADD)
  dnl PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $TEMPLATES_DIR/lib, TEMPLATES_SHARED_LIBADD)

  dnl PHP_EXTENSION($LIBSYMBOL, $ext_shared)
  PHP_NEW_EXTENSION(templates, templates.c tmpl_lib.c search.c, $ext_shared)
fi
