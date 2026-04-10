# Konventionen

## Ziel

Diese Regeln stellen sicher, dass DocVault konsistent, stabil und erweiterbar bleibt.

---

## Allgemeine Regeln

* immer vollständige Dateien bearbeiten
* keine Funktionen entfernen
* keine Logik ändern ohne klare Absicht
* Änderungen nachvollziehbar halten

---

## Modulstruktur

Jedes Modul muss enthalten:

* index.php
* add.php
* edit.php
* view.php
* delete.php
* module_nav.php

---

## UI-Regeln

* Header links, Aktionen rechts
* Buttons einheitlich
* Tooltips für Action Icons
* Tabellen mit:

  * sticky header
  * sticky actions
* Tags einheitlich darstellen

---

## Helper-Regeln

* zentrale Funktionen nur in Helpern
* keine doppelten Funktionen
* Zugriff über global $pdo
* keine HTML-Ausgabe in Helpern

---

## Dateiverwaltung

* Dateien liegen im /archiv/
* Struktur:

archiv/{jahr}/{kategorie}/{unterkategorie}/

---

## Wichtige Regeln

* beim Ändern von Kategorie → Datei verschieben
* beim Löschen → Datei löschen
* keine verwaisten Dateien

---

## Prozesse

* keine parallele Verarbeitung
* Lockfile verwenden
* Logs schreiben

---

## Naming

* klare Namen verwenden
* keine Abkürzungen
* konsistente Schreibweise

---

## Sicherheit

* keine Zugangsdaten im Code
* keine direkte User-Eingabe ohne Prüfung
* Zugriffskontrolle beachten

---

## Git-Regeln

* keine Logs committen
* keine Archivdaten committen
* keine config.php committen

---

## Ziel

* einheitlicher Code
* weniger Fehler
* bessere Wartbarkeit
