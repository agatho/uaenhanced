<?php
/*
 * class_download.inc - Show Download
 * Copyright (c) 2007 David Unger
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * Show form.
 *
 * @author  David Unger
 * @package    ualogin
 * @subpackage includes
 */

class ualogin_download {

  /**
   * create class
   */
  function ualogin_download() {

    $this->display();
  }

  /**
   * Display Page
   */
  function display() {

    /**
     * download items
     */
    $items[]  = array('name'   => utf8_encode('Grafikpack'),
                      'desc'   => utf8_encode('Damit der Server etwas entlastet wird, besteht nun die M�glichkeit die Grafiken von der eigenen Festplatte zu laden. '.
                                  'Das spart euch auch einiges an Geld, da eure Verbindung sehr viel weniger Bytes �bertragen muss. '.
                                  'Um dieses Feature zu nutzen, kann man sich nun ein Grafikpack herunterladen.<br /><br />'.
                                  'Den Pfad zum eigenen Grafikpack kann man dann InGame im Kompendium/Profil einstellen.<br /><br />'.
                                  '<b>Hier eine <a href="http://www.uga-agga.com/mwiki/index.php/Grafikpack">genauere Anleitung</a></b>.'),
                      'link'   => 'http://srv05.uga-agga.de/upload/uga-agga-grafikpack.zip'
                    );

    $items[]  = array('name'   => utf8_encode('Uga Agga Quellcode (CVS)'),
                      'desc'   => utf8_encode('Neben den offiziellen Release-Versionen gibt es in regelm��igen Abst�nden Ver�ffentlichungen der aktuellen Quellcode-Dateien aus unserem CVS. '.
                                  'Diese "Snapshots" stellen den jeweils aktuellen Entwicklunsstand dar, sind daf�r aber nicht immer in einem funktionsf�higen Zustand. '.
                                  'Eventuelle Probleme k�nnen im entsprechenden Bereich im Forum diskutiert werden.'),
                      'link'   => 'http://srv04.uga-agga.de/upload/cvs/'
                    );

    $items[]  = array('name'   => utf8_encode('"Uga-Agga" - Schriftart'),
                      'desc'   => utf8_encode('Die von uns verwendetet Schriftart "Junior + Stinky" stammt von der Seite www.robotjohnny.com <br /> '.
                                  'Aus Lizenzgr�nden k�nnen wir euch diese Schriftart nicht direkt anbieten. '.
                                  'Es handelt sich zwar grunds�tzlich um einen "free font", achtet aber bitte darauf, dass dieser bei kommerziellem Gebrauch zu "toyware" wird.<br />'.
                                  'Genaueres entnehmt einfach der Internetseite...'),
                      'link'   => 'http://www.robotjohnny.com/fontpage.html'
                    );

    $items[]  = array('name'   => utf8_encode('Uga Agga Karte'),
                      'desc'   => utf8_encode('Ab und zu wird die Karte von Uga Agga in dieser Datei abgelegt und zwar mit folgendem Format: <br /><br />'.
                                  'caveID, xCoord, yCoord, \'cave_name\', terrain, \'player_name\', \'tribe\', rank <br /><br />'.
                                  'Die einzelnen Felder sind dabei Tab getrennt und jede Zeile wird mit einem Newline abgeschlossen.'),
                      'link'   => 'http://srv05.uga-agga.de/upload/caves.gz'
                    );

    $items[]  = array('name'   => utf8_encode('Uga Agga Players'),
                      'desc'   => utf8_encode('Ab und zu werden die Informationen der Spieler von Uga Agga in dieser Datei abgelegt und zwar mit folgendem Format:<br /><br />'.
                                  '\'player_name\', platzierung, punktzahl, \'stamm\', gott, h�hlenanzahl<br /><br />'.
                                  'Die einzelnen Felder sind dabei Tab getrennt und jede Zeile wird mit einem Newline abgeschlossen.'),
                      'link'   => 'http://srv05.uga-agga.de/upload/players.gz');

    $items[]  = array('name'   => utf8_encode('Uga Agga Relations'),
                      'desc'   => utf8_encode('Ab und zu werden die Informationen der Beziehungen der St�mme von Uga Agga in dieser Datei abgelegt und zwar mit folgendem Format:<br /><br />'.
                                  'stamm, zielstamm, beziehungsart<br /><br />'.
                                  'Die einzelnen Felder sind dabei Tab getrennt und jede Zeile wird mit einem Newline abgeschlossen.'),
                      'link'   => 'http://srv05.uga-agga.de/upload/relations.gz');

    $data = array();
    $data['/DOWNLOAD/ITEM'] = $items;

    /**
     * generate page
     */
    require_once(drupal_get_path('module', 'ualogin') .'/includes/class_template.inc');
    $template = new ViewTemplate('Download');
    $template->setAttribute('/', $data);
    $this->output = $template->toString();
  }
}
