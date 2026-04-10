# Architektur

## Gesamtstruktur

DocVault ist in mehrere Schichten aufgebaut:

SCAN → CLI → ARCHIV → DATENBANK → MODULE → CORE → UI

## Schichten im Detail

### 1. Scan-Ebene

Verzeichnis:

* /scan/
* /processing/

Funktion:

* Aufnahme neuer Dateien
* Übergabe an Verarbeitungsprozess

---

### 2. Prozess-Ebene (CLI)

Verzeichnis:

* /cli/

Wichtige Dateien:

* process_scans.php
* repair_assets.php

Funktion:

* OCR / PDF Verarbeitung
* Klassifizierung
* Verschiebung ins Archiv
* Fehlerbehandlung

---

### 3. Archiv (Dateisystem)

Struktur:
archiv/{jahr}/{kategorie}/{unterkategorie}/datei.pdf

Beispiel:
archiv/2025/Versicherung/Ergo/dokument.pdf

Funktion:

* physische Ablage der Dokumente
* zentrale Quelle für Dateien

---

### 4. Datenbank

Funktion:

* Metadatenverwaltung
* Zuordnung von Dateien
* Statusverwaltung (z. B. „neu“)

---

### 5. Module (Business-Logik)

Verzeichnis:

* /modules/

Aufgabe:

* Fachliche Logik
* CRUD-Funktionalität
* Datenanzeige

---

### 6. Core (Framework)

Verzeichnis:

* /core/

Aufgabe:

* Layout
* Sicherheit
* Helper-Funktionen
* UI-Komponenten

---

### 7. UI (Frontend)

Bestandteile:

* Bootstrap 5
* docvault.css
* Icons

Funktion:

* Darstellung
* Benutzerinteraktion

---

## Datenfluss

1. Scan → Datei kommt rein
2. CLI verarbeitet Datei
3. Datei wird archiviert
4. DB-Eintrag wird erstellt
5. Modul zeigt Daten an
6. Core stellt UI bereit

---

## Besonderheiten

* Trennung von Datei und Datenbank
* zentrale Helper-Struktur
* modulare Erweiterbarkeit
* NAS-optimierte Architektur
