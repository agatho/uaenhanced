<?php

function time_timestampToTime($timestamp) {
  $time=
    substr($timestamp, 0,4)."-".
    substr($timestamp, 4,2)."-".
    substr($timestamp, 6,2)." ".
    substr($timestamp, 8,2).":".
    substr($timestamp,10,2).":".
    substr($timestamp,12,2);

  return strtotime($time);     // NOT TESTED !!!!
}

function time_formatDuration($seconds) {
  return sprintf("%02d:%02d:%02d",
		 ($seconds/3600),
		 ($seconds/60)%60,
		 $seconds%60);
}


define("STARTING_YEAR",    1);
define("MONTHS_PER_YEAR", 10); // nicht Null
define("DAYS_PER_MONTH",  28); // nicht Null
define("HOURS_PER_DAY",   24); // nicht Null
define("MOONPHASE",       13); // nicht Null

define("RATIO",            3); // UGA DAYS PER REAL DAYS

define("NEUMOND",        'n');
define("ZUNEHMEND",      'z');
define("VOLLMOND",       'v');
define("ABNEHMEND",      'a');

/* getUgaAggaTime returns an associative array
* containig the following keys
*
* year
* month
* day
* hour
* moon
*/
function getUgaAggaTime($time){
    $now = getdate();

    $stundenSeitStart = ($time - mktime (0, 0, 0, 9, 2, 2002, 1)) * RATIO  / (60 * 60);

    $result = array();

    // D = {0 .. (HOURS_PER_DAY - 1)}
    $result['hour'] = $stundenSeitStart % HOURS_PER_DAY;

    // D = {NEUMOND, ZUNEHMEND, VOLLMOND, ABNEHMEND}
    $mondphase = ($stundenSeitStart % (MOONPHASE * HOURS_PER_DAY)) / (MOONPHASE * HOURS_PER_DAY);
    if ($mondphase < 0.25)
      $result['moon'] = NEUMOND;
    else if ($mondphase < 0.5)
      $result['moon'] = ZUNEHMEND;
    else if ($mondphase < 0.75)
      $result['moon'] = VOLLMOND;
    else
      $result['moon'] = ABNEHMEND;

    $tageSeitStart  = floor($stundenSeitStart / HOURS_PER_DAY);

    // D = {1 .. DAYS_PER_MONTH}
    $result['day']   = ($tageSeitStart % DAYS_PER_MONTH) + 1;
    $monateSeitStart = floor($tageSeitStart / DAYS_PER_MONTH);

    // D = {1 .. MONTHS_PER_YEAR}
    $result['month'] = $monateSeitStart % MONTHS_PER_YEAR + 1;
    $jahreSeitStart  = floor($monateSeitStart / MONTHS_PER_YEAR);

    // D = {STARTING_YEAR ... }
    $result['year'] = STARTING_YEAR + $jahreSeitStart ;

    return $result;
}

function getUgaAggaTimeFromTimeStamp($timestamp){

  $time = mktime(substr($timestamp, 8,2),
                 substr($timestamp,10,2),
                 substr($timestamp,12,2),
                 substr($timestamp, 4,2),
                 substr($timestamp, 6,2),
                 substr($timestamp, 0,4));

  return getUgaAggaTime($time);
}


/*
 * getMonthName() returns the name of the specified month number
 * $month is a number between 1 and MONTHS_PER_YEAR
 */

function getMonthName($month){
  $monthNames = array("0" => "Krystahll", "Eisigkeit", "Schnehbrandh", "Binenschtich", "Brrunfhd",
                             "Gedeyh",  "Ernte", "D&uuml;sternis", "Verderb", "Frrost");

  return $monthNames[($month - 1) % sizeof($monthNames)];
}

/*
 * isDay() returns true if time is day
 * $time an assoc array see getUgaAggaTime()
 */

function isDay($time){
  return ($time['hour'] >=   HOURS_PER_DAY/4) && ($time['hour'] <  3*HOURS_PER_DAY/4);

}
?>