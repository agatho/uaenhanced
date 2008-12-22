Dieses Pack enthält sämtliche _geänderten_ Dateien (und nur diese) nach dem aktuellen Stand
der Modifizierungen auf tntchris.dyndns.org/ugaagga.
Dieses Sammlung enthällt keine Grafiken oder Regeldateien.
Beachtet, dass sie für das Windowssystem modifiziert wurden und unter Unix/Linux nicht lauffähig sind.
Dies betrifft insbesondere den Ticker.
Dieser Mod basiert auf der Source von Runde 4 und er funktioniert nicht mit Sourcedateien aus einer anderen Runde!

Uga-Agga könnt ihr euch unter www.uga-agga.org downloaden.
Ich gebe keine Hilfe und keinen Support, weder zur Installation von Uga-Agga selbst, noch zur Installation dieses
Mods. Weiterhin übernehme ich keinerlei Verantwortung für Schäden oder Datenverlust, die durch den Einsatz dieses Mods möglicherweise
entstehen können. Ich empfehle vor dem Einsatz ein Backup durchzuführen!


ÄNDERUNGEN GAME:

Sortierung der Rankings (implemented)
Anzeige der Einzelpunkteverteilung im Ranking (für jeweiligen Spieler) (implemented)



Bewegungsmenu Auswahlen: Alle Einheiten, voll beladen
Anzeige des letzten Logins
Grafische Anzeige des Punkteverlaufs der letzten 10 Tage (Spieler)
Farmschutz (erweiterter Anfängerschutz)
Siedlungsliste
Adressbuch 
Urlaubsmodus 
Terminkalender:
Eigene Bewegungen, fremde Bewegungen und die ausstehenden Termine können nun eingeklappt werden. Nützlich bei vielen Einträgen. Läuft einwandfrei wies scheint, könnte allerdings mit bestimmten Browsern Probleme geben.
Ausserdem werden jetzt dort auch Rückkehrbewegungen angezeigt.
Baumenüs 
Siedlungsreihenfolge
Artefakte (können beim Einweihen zerstört werden)
Statistiken 
Nachrichten-Ticker 
Quests 
Götter 
Anführer 
Terraineffekte 


ÄNDERUNGEN TICKER:

ticker.c allgemein etwas geändert (zur einfacheren Handhabung, beachtet das config)
Memoryleak behoben (Problem in den original SQL Funktionen, niemand weiß warum, durch eigene Funktion ersetzt)
Gaussfunktion für Wunder hinzugefügt
Funktionen für Anführer hinzugefügt
Wunderberichte geändert (bewunderte bekommen jetzt genaue Berichte)
diverse Funktionen für Götter (Angriffe, Wunder)
Funktionen für Urlaubsmodus
Funktionen für Artefaktzerstörung
Funktionen zur Änderung der Spezialgebäude (beim Entdecken einer Religion)
Funktionen für Statistiken
Questhandler (unvollständig, beeinträchtig aber den Ticker nicht, sollte nicht entfernt werden)
Funktionen für Monster entfernt (beeinträchtigte Ticker)




INSTALLATION:

1. Uga Agga normal installieren
2. Datenbanken und Tabellen anlegen mittels db_complete.sql
3. Datenbank an eure Regeln anpassen (mit der Datei "game_rules.db" aus /src/dbs des original UAs)
4. Sämtliche Dateien mit denen im Ordner changed ersetzen
5. Ticker aus ticker_src_win kompilieren (eure Regeldateien noch einfügen)
6. Höhlen anlegen etc (wie original)
7. Scripte einrichten (zusätzliche Scripte aus Utilities nicht vergessen)

Zusätzlich sind noch diverse Änderungen bzw Konfigurationen zu machen (muß man beim original auch). Darunter z.B.
der Götterstamm und die beiden Götterwissenschaften im Ticker. Außerdem müssen noch einige Scripte angepaßt werden.



Die jeweils aktuellen Dateien, sowie evt Hilfe ist zu Finden unter http://tntchris.dyndns.org/comawiki/
Zum Spiel mit den neusten Änderungen: http://tntchris.dyndns.org/ugaagga/
Zum original UgaAgga: www.uga-agga.org


chris--- (tntchris88@gmx.de)