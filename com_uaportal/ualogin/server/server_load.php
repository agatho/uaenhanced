<?
/*
 * server_load.php
 * Copyright (c) 2007 David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Only need in php < 5.2.0
 */
if (!function_exists('sys_getloadavg')) {
   function sys_getloadavg() {
       $loadavg_file = '/proc/loadavg';
       if ( @file_exists($loadavg_file) ) {
           return explode(chr(32), @file_get_contents($loadavg_file));
       }
       return array(2 => 'error');
   }
}

/**
 *  Output load. Last 15min
 */
$load = sys_getloadavg();
echo $load[2];

?>