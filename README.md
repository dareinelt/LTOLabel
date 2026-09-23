# LTOLabel

Produktionsreife PHP-Webanwendung zum Erzeugen und Drucken von **LTO-Tape-Barcode-Labels**
für Bandbibliotheken, insbesondere für die **Dell EMC PowerVault ML3**.

Die Labels entsprechen der IBM-LTO-Ultrium-Label-Spezifikation: **Code 39 (USS-39)**, genau
8 Zeichen, Vektor-Ausgabe mit exakten physikalischen Maßen (keine Skalierung, keine
Bitmap-/JPEG-Kompression).

## Funktionen

- **Datenbänder** (`DATA`): 6 Zeichen VOLSER + Medienkennung, z. B. `ABC123L8`.
- **Reinigungskassetten** (`CLEANING`): universelles `CLN`-Label, z. B. `CLN001L1`.
- **Code 39** mit korrekten LTO-Parametern (Modulbreite 0,423 mm, Ratio 2,75:1,
  Mindest-Balkenhöhe 11,1 mm).
- **Vektor-Barcode** als PDF (FPDF) und als SVG-Vorschau im Browser.
- **Exakte physikalische Maße** – das PDF wird in mm aufgebaut, nicht in CSS-Pixeln,
  und niemals automatisch skaliert.
- **Validierung pro Medientyp** mit Fehlern und Qualitätswarnungen (z. B. Modulbreite
  unter Nennwert, Barcode breiter als das Label).
- **Profil-basiert**: `dell_ml3` und `generic_lto` sind konfigurierbar
  (`config/config.yaml`); weitere Libraries lassen sich ergänzen.
- **Web-UI**, **REST-API** (`/api/labels`) und **CLI** (`bin/console`) teilen sich
  dieselbe Fachlogik.
- **Persistenz**: SQLite (Standard) oder MySQL, vorbereitete Statements, Migrationsschema.
- **Druckhistorie** mit Druckzähler, Blattdruck (Grid) und Einzeldruck (Label = Seitengröße).
- **Docker**-Container für den Betrieb (siehe unten).

## Voraussetzungen

- PHP **8.3+** mit den Erweiterungen `pdo_sqlite` (oder `pdo_mysql`), `mbstring`,
  `json`, `session`.
- [Composer](https://getcomposer.org/) (zur Installation der Abhängigkeiten).

## Schnellstart

```bash
composer install
php bin/console label:batch ARCHIVE 1 100 L8          # 100 Datenband-Labels
php bin/console label:batch --type=CLEANING --start=1 --end=10
php bin/console print:test --output=test.pdf           # Testseite rendern
```

Web-Server (Entwicklung):

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Danach <http://127.0.0.1:8080> öffnen.

## Konfiguration

Alle Werte liegen in `config/config.yaml`; lokale Überschreibungen in
`config/config.local.yaml` (wird nicht eingecheckt). Wichtigste Abschnitte:

- `profiles.<name>.label` – Labelmaße in mm (`width_mm`, `height_mm`).
- `profiles.<name>.barcode` – Code-39-Parameter (Modulbreite, Ratio, Balkenhöhe, Quiet Zone).
- `profiles.<name>.media_types.DATA|CLEANING` – Regeln pro Medientyp (Länge, Zeichensatz,
  Medienkennungen, Präfix/Suffix).
- `page` – Seitengeometrie für den Blattdruck.
- `database` – SQLite- oder MySQL-Verbindung.

> **Wichtig:** Der Standard verwendet die LTO-Nennwerte. Eine Labelbreite unter ≈ 73 mm
> ist mit der nominalen Modulbreite 0,423 mm für 8 Zeichen **nicht scannbar** – die
> Anwendung blockiert dann die Erzeugung bzw. warnt.

## Verwendung

### Web-UI

| Route | Beschreibung |
|---|---|
| `/` | Dashboard (Anzahl, zuletzt erstellt, Druckhistorie) |
| `/labels` | Labelübersicht mit Filter und Mehrfachdruck |
| `/labels/create` | Neuen Stapel erzeugen (mit Vorschau) |
| `/print/history` | Druckhistorie |
| `/print/single?id=N` | Einzellabel als PDF |
| `/print/test` | Testseite (Daten + Cleaning) als PDF |
| `/api/labels` | REST-API (GET listet, POST erzeugt) |

### CLI

```bash
php bin/console label:create ARCHIVE01L8
php bin/console label:batch ARCHIVE 1 100 L8
php bin/console label:batch --type=CLEANING --start=1 --end=10
php bin/console label:list
php bin/console print:test --output=test.pdf
```

## Tests

```bash
composer test
```

## Projektstruktur

```
src/
  Application/    Fachlogik (LabelService, PrintService, BatchRequest)
  Barcode/        Code-39-Generator, Validierung, SVG-Renderer, Registrierung
  Config/         YAML-Konfiguration
  Database/       PDO-Wrapper, Repository, Migration
  Http/           Frontcontroller-Router, Request/Response, Controller, View
  Label/          Label-Domäne, Validatoren, Medien-Typen, ID-Erzeugung
  Pdf/            FPDF-Rendering (Blatt- und Einzelmodus)
  Printing/       Seitengeometrie
  Profile/        Bibliotheksprofile und Medientyp-Profile
  Support/        Einheitenumrechnung, ValidationResult
config/           config.yaml (+ config.local.yaml)
public/           Frontcontroller und Assets
templates/        PHP-Templates
bin/console       CLI
tests/            PHPUnit-Tests
docs/             Recherche, Installation, Druck, Deployment
```

## Dokumentation

- [docs/RESEARCH.md](docs/RESEARCH.md) – Recherche und abgeleitete Parameter.
- [docs/INSTALL.md](docs/INSTALL.md) – Installation im Detail.
- [docs/PRINTING.md](docs/PRINTING.md) – ML3-kompatibler Druck (Papier, Drucker, kein Skalieren).
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) – Produktivbetrieb (Apache/Nginx, Docker).

## Docker

Der Container enthält Apache als Webserver (`php:8.3-apache`) inklusive aller
Abhängigkeiten. Start:

```bash
docker compose up --build
```

Die Anwendung ist danach unter <http://localhost:8080> erreichbar (Container-Port 80).
SQLite-Datenbank und Logs liegen im benannten Volume `ltolabel-data`; ein
HEALTHCHECK überwacht die Erreichbarkeit unter `/`. Details zu Konfiguration,
MySQL und Betrieb: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Lizenz

MIT.
