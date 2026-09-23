# Deployment

## Grundsätze

- Webserver zeigt ausschließlich auf `public/`.
- `data/`, `config/config.local.yaml`, `vendor/` und `bin/` sind nicht öffentlich
  erreichbar.
- Für den Produktivbetrieb `composer install --no-dev --optimize-autoloader` verwenden.
- Logs landen in `data/logs/ltolabel.log` (Level in `config/config.yaml`).

## Docker

Das Image basiert auf dem offiziellen PHP-Apache-Image (`php:8.3-apache`), das
`pdo_sqlite`, `mbstring` und `fileinfo` bereits fest einkompiliert enthält.
Zusätzlich installiert werden `gd` (für FPDF) und `pdo_mysql` (optionale
MySQL-Anbindung). Als DocumentRoot dient `public/`. Ein HEALTHCHECK prüft die
Erreichbarkeit des Webservers unter `/`.

```bash
docker compose up --build
```

Die Anwendung ist danach unter <http://localhost:8080> erreichbar. Persistente Daten
(SQLite) und Logs liegen in benannten Volumes.

### Konfiguration im Container

Standardwerte sind im Image enthalten. Eigene Werte werden über
`config/config.local.yaml` gesetzt, das beim Start über `config/config.yaml`
gelegt wird. Die Datei ist bewusst nicht Teil des Images (`.dockerignore`),
daher per Bind-Mount einbinden:

```yaml
services:
  app:
    volumes:
      - ltolabel-data:/var/www/html/data
      - ./config/config.local.yaml:/var/www/html/config/config.local.yaml:ro
```

Die Anwendung liest ihre Konfiguration nicht aus Umgebungsvariablen; einzige
Ausnahme ist `TZ` (System-Zeitzone des Containers).

## MySQL/MariaDB

In `config/config.local.yaml`:

```yaml
database:
  driver: mysql
  mysql:
    host: 127.0.0.1
    port: 3306
    database: ltolabel
    user: ltolabel
    password: <passwort>
    charset: utf8mb4
```

Das Schema wird beim Start automatisch angelegt (idempotente Migration).

## Reverse Proxy / TLS

Ein vorgeschalteter Reverse Proxy (z. B. Nginx, Caddy, Traefik) sollte TLS terminieren.
Die Anwendung selbst setzt keine eigenen Zertifikate.

## Sicherheit

- CSRF-Schutz für alle ändernden Anfragen (Web und API).
- Vorbereitete Statements (keine SQL-Injection).
- Ausgabe-Escaping in Templates (`e()`).
- Keine Secrets im Log oder im Repository; lokale Werte nur in `config.local.yaml`.

## Betrieb

- **CLI-Aufgaben** laufen unabhängig vom Webserver, z. B.:
  ```bash
  php bin/console label:batch ARCHIVE 1 100 L8
  ```
- **Backup**: SQLite-Datei (`data/ltolabel.sqlite`) bzw. die MySQL-Datenbank sichern.
- **Monitoring**: Der Container bringt einen HEALTHCHECK mit (prüft `/` auf 2xx).
  Für externes Monitoring eignen sich `/` (200 OK) oder `/api/labels`.
