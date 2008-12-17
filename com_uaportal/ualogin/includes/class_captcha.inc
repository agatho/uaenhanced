<?php
/*
 * captcha.inc - Creates a CAPTCHA
 * Copyright (c) 2005  Marcus Lunzenauer
 * Copyright (c) 2007  David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

define('CAPTCHA_TIME', 180);
define('CAPTCHA_LEN', 4);

class uaLogin_captcha {

  var $captcha, $captcha_id, $ip;

  function uaLogin_captcha() {

    $this->ip = $this->encode_ip($_SERVER['REMOTE_ADDR']);
  }

  function check($captcha, $captcha_id) {

    if ( empty($captcha) || empty($captcha_id) ) {
      return FALSE;
    }

    $db = new ualogin_db;
    $query = 'SELECT * FROM {ualogin_captcha}
               WHERE captcha = \'' . $db->sql_escape_string($captcha) . '\'
                 AND captcha_id = \'' . $db->sql_escape_string($captcha_id) . '\'
                 AND time > ' . (time() - CAPTCHA_TIME);
    unset($db);
    $result = db_query($query);
    $row = db_fetch_array($result);

    if ( empty($row) || substr($row['ip'], 0, 6) != substr($this->ip, 0, 6) ) {
      return FALSE;
    }

    return TRUE;
  }

  function delete($captcha_id='') {

    $db = new ualogin_db;

    if ( !empty($captcha_id) ) {
      $where = 'OR captcha_id = \'' . $db->sql_escape_string($captcha_id) . '\'';
    }

    $query = 'DELETE FROM {ualogin_captcha}
               WHERE time < ' . (time() - CAPTCHA_TIME) . '
                 OR ip = \'' . $db->sql_escape_string($this->ip) . '\'' .
                 $where;
    unset($db);
    db_query($query);
  }

  function generate() {

    $solution = '';
    $chars = "ABDEFGHKLMPSTUYZ234568";

    /**
     * generate x chars
     */
    for ( $i = 0; $i < CAPTCHA_LEN; ++$i ) {
      $solution .= $chars{rand(0, strlen($chars) - 1)};
    }

    srand((double)microtime() * 1000000);

    $this->captcha = $solution;
    $this->captcha_id = md5(uniqid(rand()));

    $db = new ualogin_db;
    $fields = array(
      'captcha'     => $this->captcha,
      'captcha_id'  => $this->captcha_id,
      'ip'          => $this->ip,
      'time'        => time(),
    );
    $db->sql_statement($fields);
    $query = 'REPLACE INTO {ualogin_captcha}
               (' . $db->sql_fields . ') VALUES(' . $db->sql_values . ')';
    unset($db);
    db_query($query);
  }

  function get_captcha() {
    global $input;

    $captcha_id = $input->read('captcha_id', TYPE_NO_HTML);

    if ( empty($captcha_id) ) {
      return 'error';
    }

    $db = new ualogin_db;
    $query = 'SELECT * FROM {ualogin_captcha}
               WHERE captcha_id = \'' . $db->sql_escape_string($captcha_id) . '\'';
    unset($db);
    $result = db_query($query);
    $row = db_fetch_array($result);

    if ( empty($row) || substr($row['ip'], 0, 6) != substr($this->ip, 0, 6) || empty($row['captcha']) ) {
      return 'error';
    }

    return $row['captcha'];
  }

  function show() {

    /**
     * get captcha code
     */
    $captcha = $this->get_captcha();
    $captcha_count = strlen($captcha);
    $img_width = 30 * $captcha_count;

    /**
     * Step 1. Create a new blank image
     */
    $textimg = imagecreate($img_width, 60);

    /**
     * Step 2. Set background to 'color'
     */
    $bgcolor = imageColorAllocate($textimg, 0, 0, 0);

    /**
     * Set the Text color
     */
    $textcolor = imageColorAllocate($textimg, 255, 255, 255);

    /**
     * put the Text into the Picture
     */
    $font = drupal_get_path('module', 'ualogin') .'/includes/texs.ttf';
    for ( $i = 0; $i < $captcha_count; $i++ ) {
      $angle     = mt_rand( -8, 8);
      $DeltaX    = mt_rand( -3, 3);
      $DeltaY    = mt_rand(-10, 0);
      $DeltaSize = mt_rand( -2, 2);
      imageTTFText($textimg, 30 + $DeltaSize, $angle, 5 + ($i * 28) + $DeltaX, 55 + $DeltaY, $textcolor, $font, $captcha{$i});
    }

    /**
     * Set Disturb Colors
     */
    $disturbColors = array();
    $disturbColors[] = $textcolor;
    $disturbColors[] = $bgcolor;
    $disturbColors[] = $bgcolor;
    $disturbColors[] = $bgcolor;

    /**
     * Disturb the txt image with lines
     */
    $x = mt_rand(5, 10);
    while ( $x < $img_width ) {
      $color = $disturbColors[ mt_rand(0, 2) ];
      $DeltaX = mt_rand(-10, 10);
      imageLine($textimg, $x - $DeltaX, 0, $x + $DeltaX, 60, $color);
      $x += mt_rand(3, 7);
    }

    $y = mt_rand(5, 10);
    while ( $y < 60 ) {
      $color = $disturbColors[ mt_rand(0, 2) ];
      $DeltaY = mt_rand(-10, 10);
      imageLine($textimg, 0, $y - $DeltaY, $img_width, $y + $DeltaY, $color);
      $y += mt_rand(3, 7);
    }

    /**
     * Step 3. Send the headers (at last possible time)
     */
    header('Content-type: image/jpeg');

    /**
     * Step 4. Output the image as a PNG
     */
    imageJPEG($textimg);

    /**
     * Step 5. Delete the image from memory
     */
    imageDestroy($textimg);
  }

  function encode_ip($dotquad_ip) {
    $ip_sep = explode('.', $dotquad_ip);
    return sprintf('%02x%02x%02x%02x', intval($ip_sep[0]), intval($ip_sep[1]), intval($ip_sep[2]), intval($ip_sep[3]));
  }
}
