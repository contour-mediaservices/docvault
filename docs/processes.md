# Prozesse

## Überblick

DocVault nutzt automatisierte Prozesse zur Verarbeitung von Dokumenten.

Diese befinden sich im Verzeichnis:
cli/

---

## Hauptprozesse

### process_scans.php

Funktion:

* verarbeitet neue Dateien aus dem Scan-Ordner

Ablauf:

1. Datei liegt in /scan/
2. Verschiebung nach /processing/
3. Analyse / OCR
4. Zuordnung zu Kategorie
5. Verschiebung nach /archiv/
6. Eintrag in Datenbank
7. Status = „neu“

---

### repair_assets.php

Funktion:

* Reparatur von fehlerhaften Einträgen

Typische Aufgaben:

* fehlende Dateien finden
* falsche Pfade korrigieren
* Dateien neu zuordnen
* Bereinigung

---

## Wichtige Verzeichnisse

### /scan/

* Eingang neuer Dateien

---

### /processing/

* temporäre Verarbeitung

---

### /archiv/

Struktur:

archiv/{jahr}/{kategorie}/{unterkategorie}/

→ endgültiger Speicherort

---

### /logs/

Enthält:

* scan.log
* synoscheduler/

---

### /process.lock

Funktion:

* verhindert parallele Ausführung
* wichtig für Stabilität

---

## Ablaufdiagramm

SCAN → PROCESSING → ARCHIV → DB → UI

---

## Trigger

Die Prozesse werden gestartet durch:

* Synology Aufgabenplaner
* regelmäßige Ausführung (z. B. alle 5 Sekunden)
* optional manuell über Monitor

---

## Fehlerbehandlung

Fehler werden protokolliert in:

logs/scan.log

---

## Typische Probleme

* fehlende Dateirechte
* falsche Pfade
* doppelte Verarbeitung
* Lockfile bleibt bestehen
* Docker nicht erreichbar

---

## Abhängigkeiten

* Docker (Poppler)
* PHP CLI
* Dateisystemrechte (http:users)
* Datenbankverbindung

---

## Best Practices

* Lockfile immer korrekt löschen
* Logs regelmäßig bereinigen
* Fehler sichtbar im Monitor anzeigen
* Prozesse nicht parallel starten

---

## Ziel

* stabile Automatisierung
* nachvollziehbare Abläufe
* einfache Fehleranalyse
