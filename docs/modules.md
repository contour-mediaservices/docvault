# Module

## Überblick

Alle Module befinden sich unter:
modules/

Jedes Modul folgt einer einheitlichen Struktur.

---

## Standardstruktur eines Moduls

* index.php → Übersicht
* add.php → neu anlegen
* edit.php → bearbeiten
* view.php → Detailansicht
* delete.php → löschen
* module_nav.php → Navigation

---

## Vorhandene Module

### assets

Verwaltung von Dokumenten

Besonderheiten:

* Kategorien / Unterkategorien
* Dateipfade
* Status „neu“
* Dateioperationen (verschieben/löschen)

---

### asset_tags

Tag-System für Dokumente

---

### projects

Projektverwaltung

* Dateien pro Projekt
* Beschreibung
* Tags

---

### project_tags

Tag-System für Projekte

---

### passwords

Passwortverwaltung

* verschlüsselte Speicherung
* Felder: Passwort, PIN, Domain, Benutzer

---

### password_tags

Tag-System für Passwörter

---

### hosting

Verwaltung von Domains und Services

Besonderheiten:

* Service-Typen
* Abrechnungslogik
* Fälligkeitsberechnung
* Status (fällig / überfällig)

---

### search

Zentrale Suche

* Volltextsuche (Assets)
* LIKE-Suche (andere Module)

---

### system

Systemtools und Monitoring

* monitor.php
* Statusanzeigen
* Logs
* Scan-Status

---

## Modulprinzip

* jedes Modul ist unabhängig
* nutzt Core für Layout
* nutzt Helper für Logik
* folgt einheitlichem UI-Standard

---

## Ziel

* Erweiterbarkeit
* klare Trennung
* Wiederverwendbarkeit
