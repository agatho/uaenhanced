<?php
/*
 * class_statistik.inc - Show Statistik
 * Copyright (c) 2007 David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Show Statistik.
 *
 * @author  David Unger
 * @package    ualogin
 * @subpackage includes
 */

class ualogin_statistik {

  var $gods, $halfgods, $SecretNames;
  var $gods_count, $halfgods_count, $units_count, $storage_count;
  var $UnitFieldsName, $ScienceFieldsName;
  var $updateTime;

  /**
   * create class
   */
  function ualogin_statistik() {

    $this->display();
  }

  /**
   * Display Page
   */
  function display() {

    $data = array();
    $disabled = ( variable_get("ualogin_disable_game", 1) == 1 ) ? 1 : variable_get("ualogin_disable_statistik", 1);
    if ( $disabled ) {
      $data['/DISABLED/message'] = 'Die Runde wurde beendet. Anmelden oder Spielen ist derzeit nicht möglich.';
    }
    else {
      if ( !file_exists(drupal_get_path('module', 'ualogin') .'/game_rules.php') ) {
        $data['/DISABLED/message'] = 'Es wurden keine Regeln gefunden.';
      }
      else {
        $this->set();

        $data['/STATISTIK/iterate']       = '';

        if ( $this->getGodStat() ) {
          $data['/STATISTIK/GODS/ITEM']     = $this->gods_count;
        }

        if ( $this->getHalfGodsStats() ) {
          $data['/STATISTIK/HALFGODS/ITEM'] = $this->halfgods_count;
        }

        if ( $this->getUnitStat() ) {
          $data['/STATISTIK/UNITS/ITEM']    = $this->units_count;
        }

        if ( $this->getStorageStat() ) {
          $data['/STATISTIK/STORAGE/ITEM']  = $this->storage_count;
        }
      }
    }

    /**
     * generate page
     */
    drupal_add_css(drupal_get_path('module', 'ualogin') .'/ualogin.css', 'module', 'all', FALSE);
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('Statistik');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }

  function set() {
    $this->gods              = explode(", ", variable_get('ualogin_statistik_gods', ''));
    $this->halfgods          = explode(", ", variable_get('ualogin_statistik_halfgods', ''));

    $this->gods_count        = array();
    $this->halfgods_count    = array();
    $this->units_count       = array();
    $this->storage_count     = array();

    $this->UnitFieldsName    = array();
    $this->ScienceFieldsName = array();

    $this->SecretNames       = array();
    $this->SecretCave        = array();

    $this->updateTime        = 14400;

    $this->getFieldsName();
    $this->getSecret();
  }

  function getFieldsName() {

    /**
     * get game rules
     */
    require_once(drupal_get_path('module', 'ualogin') .'/game_rules.php');
    global $unitTypeList, $scienceTypeList;
    init_Units();
    init_Sciences();

    foreach ( $unitTypeList AS $value ) {
      if ( !$value->nodocumentation ) {
        $this->UnitFieldsName[ $value->dbFieldName ] = utf8_encode($value->name);
      }
    }
    asort($this->UnitFieldsName);

    foreach ($scienceTypeList AS $value) {
      $this->ScienceFieldsName[ $value->dbFieldName ] = utf8_encode($value->name);
    }
    asort($this->ScienceFieldsName);
  }

  function getSecret() {
    global $db_game;

    $SecretNames = variable_get('ualogin_statistik_secret_names', '');

    if ( empty($SecretNames) ) {
      return TRUE;
    }

    /**
     * get secret names
     */
    foreach ( explode(", ", $SecretNames) AS $Player ) {
      $this->SecretNames[] = '\'' . $db_game->sql_escape_string($Player) . '\'';
    }

    /**
     * select secret caves
     */
    $query = 'SELECT caveID FROM Cave
               LEFT JOIN Player
                 ON Cave.playerID = Player.playerID
               WHERE Player.name IN (' . implode(', ', $this->SecretNames) . ')';
    $result = $db_game->query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror1'));
      return FALSE;
    }

    while ( $row = $db_game->fetchrow($result) ) {
      $this->SecretCave[ $row['caveID'] ] = TRUE;
    }

    return TRUE;
  }

  function getGodStat() {
    global $db_game;

    if ( empty($this->gods) ) {
      return TRUE;
    }

    /**
     * get god statistik
     */
    $last_update = variable_get("ualogin_statistik_halfgods_last_update", 0);

    if ( $last_update < (time() - $this->updateTime) ) {
      $where = '';
      if ( $this->SecretNames ) {
        $where = 'AND name NOT IN (' . implode(', ', $this->SecretNames) . ')';
      }

      foreach ( $this->gods as $name ) {
        $query = 'SELECT count(*) as count
                   FROM Player
                   WHERE ' . $db_game->sql_escape_string($name) . ' > 0 ' . $where;
        $result = $db_game->query($query);

        if ( $result === FALSE ) {
          drupal_set_message(t('ua_dberror2'));
          return FALSE;
        }

        $row = $db_game->fetchrow($result);

        $gods[ $name ] = $row['count'];
      }

      foreach ( $this->gods as $name ) {
        $gods_all = $gods_all + $gods[ $name ];

        $this->gods_count[] = array('name'  => $this->ScienceFieldsName[ $name ],
                                    'count' => $gods[ $name ]
                                   );
      }
      $this->gods_count[] = array('name'  => t('Insgesamt'),
                                  'count' => $gods_all
                                 );
      $this->cache($gods, 'gods');
    }
    else {
      $gods = $this->read_cache('gods');

      foreach ( $this->gods as $name ) {
        $gods_all = $gods_all + $gods[ $name ];

        $this->gods_count[] = array('name'  => $this->ScienceFieldsName[ $name ],
                                    'count' => $gods[ $name ]
                                   );
      }
      $this->gods_count[] = array('name'  => t('Insgesamt'),
                                  'count' => $gods_all
                                 );
    }

    return TRUE;
  }

  function getHalfGodsStats() {
    global $db_game;

    if ( empty($this->halfgods) ) {
      return TRUE;
    }

    /**
     * get halfgod statistik
     */
    $last_update = variable_get("ualogin_statistik_halfgods_last_update", 0);

    if ( $last_update < (time() - $this->updateTime) ) {
      $where = '';
      if ( $this->SecretNames ) {
        $where = 'AND name NOT IN (' . implode(', ', $this->SecretNames) . ')';
      }

      foreach ( $this->halfgods as $name ) {
        $query = 'SELECT count(*) as count
                   FROM Player
                   WHERE ' . $db_game->sql_escape_string($name) . ' > 0 ' . $where;
        $result = $db_game->query($query);

        // db error
        if ( $result === FALSE ) {
          drupal_set_message(t('ua_dberror3'));
          return FALSE;
        }

        // fetch row
        $row = $db_game->fetchrow($result);

        $halfgods[ $name ] = $row['count'];
      }

      foreach ( $this->halfgods as $name ) {
        $halfgods_all = $halfgods_all + $halfgods[ $name ];

        $this->halfgods_count[] = array('name'  => $this->ScienceFieldsName[ $name ],
                                        'count' => $halfgods[ $name ]
                                       );
      }
      $this->halfgods_count[] = array('name'  => t('Insgesamt'),
                                      'count' => $halfgods_all
                                     );

      $this->cache($halfgods, 'halfgods');
    }
    else {
      $halfgods = $this->read_cache('halfgods');

      foreach ( $this->halfgods as $name ) {
        $halfgods_all = $halfgods_all + $halfgods[ $name ];

        $this->halfgods_count[] = array('name'  => $this->ScienceFieldsName[ $name ],
                                        'count' => $halfgods[ $name ]
                                       );
      }
      $this->halfgods_count[] = array('name'  => t('Insgesamt'),
                                      'count' => $halfgods_all
                                     );
    }

    return TRUE;
  }

  function getUnitStat() {
    global $db_game;

    if ( empty($this->UnitFieldsName) ) {
      return TRUE;
    }

    /**
     * get unit statistik
     */
    $last_update = variable_get("ualogin_statistik_unit_last_update", 0);

    if ( $last_update < (time() - $this->updateTime) ) {
      /*
       * get unit from cave
       */
      $fields = array();
      foreach ( array_keys($this->UnitFieldsName) as $name ) {
        $name = $db_game->sql_escape_string($name);
        $fields[] = 'SUM(' . $name . ') as ' . $name;
      }

      $where = '';
      if ( $this->SecretCave ) {
        $where = 'WHERE caveID NOT IN (' . implode(', ', array_keys($this->SecretCave)) . ')';
      }

      $query = 'SELECT ' . implode(', ', $fields) . '
                 FROM Cave ' .
                 $where;
      $result = $db_game->query($query);

      if ( $result === FALSE ) {
        drupal_set_message(t('ua_dberror4'));
        return FALSE;
      }

      $CaveUnits = $db_game->fetchrow($result);

      /*
       * get unit from Event_movement
       */
      $fields = array();
      foreach ( array_keys($this->UnitFieldsName) as $name ) {
        $name = $db_game->sql_escape_string($name);
        $fields[] = 'SUM(' . $name . ') AS ' . $name;
      }

      $where = '';
      if ( $this->SecretCave ) {
        $where = 'WHERE caveID NOT IN (' . implode(', ', array_keys($this->SecretCave)) . ')';
      }

      $query = 'SELECT ' . implode(', ', $fields) . '
                 FROM Event_movement ' .
                 $where;
      $result = $db_game->query($query);

      if ( $result === FALSE ) {
        drupal_set_message(t('ua_dberror5'));
        return FALSE;
      }

      $MovementUnits = $db_game->fetchrow($result);

      /**
       * merge unit stats
       */
      foreach ( array_keys($this->UnitFieldsName) as $name ) {
        $units[ $name ] = $CaveUnits[ $name ] + $MovementUnits[ $name ];
        $units_all = $units_all + $CaveUnits[ $name ] + $MovementUnits[ $name ];

        $this->units_count[] = array('name'  => $this->UnitFieldsName[ $name ],
                                     'count' => $CaveUnits[ $name ] + $MovementUnits[ $name ]
                                    );
      }
      $this->units_count[] = array('name'  => t('Insgesamt'),
                                   'count' => $units_all
                                  );

      $this->cache($units, 'unit');
    }
    else {
      $units = $this->read_cache('unit');

      foreach ( array_keys($this->UnitFieldsName) as $name ) {
        $units_all = $units_all + $units[ $name ];

        $this->units_count[] = array('name'  => $this->UnitFieldsName[ $name ],
                                     'count' => $units[ $name ]
                                    );
      }
      $this->units_count[] = array('name'  => t('Insgesamt'),
                                   'count' => $units_all
                                  );
    }

    return TRUE;
  }

  function getStorageStat() {
    global $db_game;

    /**
     * get storage statistik
     */
    $last_update = variable_get("ualogin_statistik_storage_last_update", 0);

    if ( $last_update < (time() - $this->updateTime) ) {
      $where = '';
      if ( $this->SecretCave ) {
        $where = 'AND caveID NOT IN (' . implode(', ', array_keys($this->SecretCave)) . ')';
      }

      $query = 'SELECT count(*) AS Count, storage_cave
                 FROM Cave
                 WHERE playerID != 0 ' . $where . '
                 GROUP BY storage_cave';
      $result = $db_game->query($query);

      if ( $result === FALSE ) {
        drupal_set_message(t('ua_dberror6'));
        return FALSE;
      }

      while ( $row = $db_game->fetchrow($result) ) {
        $storage[ $row['storage_cave'] ] = $row['Count'];

        $this->storage_count[] = array('name'  => $row['storage_cave'],
                                       'count' => $row['Count']
                                      );
      }

      $this->cache($storage, 'storage');
    }
    else {
      $storage = $this->read_cache('storage');
      ksort($storage);

      foreach ( $storage as $name => $dummy ) {
        $this->storage_count[] = array('name'  => $name,
                                       'count' => $storage[ $name ]
                                      );
      }
    }

    return TRUE;
  }

  function cache($data, $type) {

    if ( !$data || !$type ) {
      return FALSE;
    }

    $db = new ualogin_db;

    /**
     * cache update
     */
    $query = 'DELETE FROM {ualogin_game_stats} WHERE type = \'' . $db->sql_escape_string($type) . '\'';
    db_query($query);

    foreach ( $data as $name => $dummy ) {
      if ( !is_numeric($name) ) {
        $name = $db->sql_escape_string($name);
      }

      $insert[] = "('" . $name . "', '" . intval($data[ $name ]) . "', '" . $db->sql_escape_string($type) . "')";
    }

    $query = 'INSERT INTO {ualogin_game_stats} (name, value, type) VALUES ' . implode(', ', $insert);
    if ( db_query($query) ) {
      variable_set("ualogin_statistik_" . $type . "_last_update", time());
    }
    unset($db);
  }

  function read_cache($type) {

    $db = new ualogin_db;
    /**
     * read cache
     */
    $query = 'SELECT *
               FROM {ualogin_game_stats}
               WHERE type = \'' .$db->sql_escape_string($type) . '\'';
    $result = db_query($query);

    if ( $result === FALSE ) {
      drupal_set_message(t('ua_dberror7'));
      return FALSE;
    }

    while ( $row = db_fetch_array($result) ) {
      $data[ $row['name'] ] = $row['value'];
    }
    unset($db);

    return $data;
  }
}
