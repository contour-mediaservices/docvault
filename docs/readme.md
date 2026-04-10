# DocVault

## Überblick

DocVault ist ein webbasiertes Dokumentenmanagement-System (DMS), das auf einer Synology NAS läuft.
Es ermöglicht das automatische Verarbeiten, Archivieren und strukturierte Verwalten von Dokumenten.

## Hauptfunktionen

* Automatischer Scan-Import
* OCR- und PDF-Verarbeitung (Docker / Poppler)
* Strukturierte Ablage im Dateisystem
* Verwaltung über Webinterface
* Tagging-System
* Zentrale Suche
* Hosting- und Projektverwaltung
* Monitoring und Systemübersicht

## Systemaufbau

DocVault besteht aus mehreren klar getrennten Bereichen:

* **Core** → internes Framework
* **Modules** → Fachlogik
* **CLI** → Prozesse / Automatisierung
* **Archiv** → physische Dokumente
* **Logs** → Systemprotokolle

## Dokumenten-Workflow

1. Datei wird in `/scan/` abgelegt
2. Verarbeitung über `process_scans.php`
3. Verschiebung nach `/archiv/{jahr}/{kategorie}/`
4. Eintrag in Datenbank
5. Anzeige im Webinterface

## Module

* assets (Dokumente)
* projects
* passwords
* hosting
* search
* system (Monitor)

## Ziel

Ein einheitliches, erweiterbares System zur zentralen Verwaltung aller wichtigen Daten und Dokumente.
