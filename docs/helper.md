# Helper

## Überblick

Helper sind zentrale Funktionen, die von mehreren Modulen genutzt werden.

Sie befinden sich im Verzeichnis:
core/

---

## Ziel der Helper

* Wiederverwendbare Logik bündeln
* Code-Duplikate vermeiden
* zentrale Berechnungen bereitstellen
* konsistente Systemlogik sicherstellen

---

## Vorhandene Helper

### hosting_helper.php

Funktion:

* Berechnung von Fälligkeiten
* Status „überfällig“
* Status „demnächst fällig“

Typische Funktionen:

* getHostingDueCount()
* getHostingOverdueCount()

---

### dashboard_helper.php

Funktion:

* Kennzahlen für Dashboard / Monitor

Typische Inhalte:

* Anzahl neuer Assets
* Systemstatus

---

### security_helper.php

Funktion:

* Sicherheitslogik
* Zugriffskontrolle
* Validierung

---

## Weitere Core-Komponenten (keine klassischen Helper)

### auth.php

* Login / Authentifizierung

### header_actions.php

* obere Button-Leiste

### form_actions.php

* Formular-Buttons (Speichern etc.)

### pagination.php

* Seitenaufteilung

### file_viewer.php

* Anzeige von Dokumenten (PDF)

---

## Aktuelle Situation

Helper sind aktuell verteilt:

* hosting_helper.php
* dashboard_helper.php
* security_helper.php

---

## Zielstruktur (zukünftig)

Empfohlene Struktur:

core/helpers/

* hosting_helper.php
* dashboard_helper.php
* security_helper.php
* asset_helper.php (optional)
* file_helper.php (optional)

---

## Wichtige Regel

* Helper greifen auf global $pdo zu
* keine doppelte Funktionsdefinition
* zentrale Einbindung (kein mehrfaches include)

---

## Best Practices

* klare Funktionsnamen
* keine HTML-Ausgabe im Helper
* nur Logik, keine Darstellung
* Wiederverwendbarkeit beachten

---

## Bedeutung für das System

Helper sind:

→ zentrale Logikschicht
→ Grundlage für UI, Module und Monitor
→ entscheidend für Konsistenz

---

## Ziel

* saubere Struktur
* klare Verantwortlichkeiten
* einfache Erweiterbarkeit
