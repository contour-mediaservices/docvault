# Konfiguration

## Überblick

Die zentrale Konfiguration befindet sich in:

/docvault/config.php

Diese Datei enthält:

* Datenbankverbindung
* Basis-Pfade
* globale Einstellungen

---

## Wichtige Bestandteile

### Datenbank

Typisch:

* Host
* Datenbankname
* Benutzer
* Passwort

Zugriff erfolgt global über:
$pdo

---

### Basis-Pfade

Wichtige Verzeichnisse:

* /scan/
* /processing/
* /archiv/
* /logs/

Diese Pfade werden in den Prozessen verwendet.

---

### CLI-Pfade

Beispiel:

* PHP CLI Pfad
* Docker Binary
* Docker Image (Poppler)

---

## Beispielstruktur (vereinfacht)

$basePath = '/volume1/web/docvault/';
$scanDir = $basePath . 'scan/';
$archiveDir = $basePath . 'archiv/';

---

## Wichtige Regeln

* config.php darf NICHT in GitHub
* keine Zugangsdaten veröffentlichen
* nur lokal vorhanden

---

## Empfehlung

Zusätzlich erstellen:

config.example.php

Inhalt:

* gleiche Struktur
* aber ohne echte Zugangsdaten

---

## Verwendung im System

config.php wird eingebunden in:

* Module
* CLI-Skripte
* Core-Dateien

---

## Best Practices

* zentrale Pfade definieren
* keine Hardcodes in Modulen
* Zugriff nur über config
* konsistente Nutzung im gesamten System

---

## Ziel

* zentrale Steuerung
* einfache Anpassbarkeit
* sichere Konfiguration
