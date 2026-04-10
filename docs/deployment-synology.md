# Deployment (Synology)

## Überblick

DocVault läuft auf einer Synology NAS.

Basis:

* WebStation (nginx)
* PHP 8.x
* MariaDB
* Docker

---

## Verzeichnis

/volume1/web/docvault/

---

## Wichtige Dienste

* WebStation
* PHP
* MariaDB
* Docker
* Aufgabenplaner

---

## Rechte

Wichtig:

chown -R http:users /volume1/web/docvault
chmod -R 775 /volume1/web/docvault

---

## Docker

Verwendung:

* Poppler (PDF Verarbeitung)

Beispiel:
minidocks/poppler

---

## Aufgabenplaner

Verwendung:

* process_scans.php

Intervall:

* regelmäßig (z. B. alle paar Sekunden)

---

## Logs

Verzeichnis:

/docvault/logs/

Wichtig:

* scan.log
* synoscheduler/

---

## Log-Rotation

Empfehlung:

* Logs nach 10 Tagen löschen
* automatischer Cleanup

---

## Zugriff

Typisch:

* http://NAS-IP/docvault
* https://docvault.domain.de

---

## Reverse Proxy

Optional:

* Subdomain
* SSL-Zertifikat

---

## Typische Probleme

* falsche Dateirechte
* Docker nicht erreichbar
* falsche Pfade
* MIME-Type Probleme (PDF.js)

---

## Best Practices

* Rechte regelmäßig prüfen
* Logs bereinigen
* Backup erstellen
* Updates kontrolliert durchführen

---

## Ziel

* stabiler Betrieb
* sichere Umgebung
* reproduzierbares Setup
