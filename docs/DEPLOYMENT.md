# Deployment

## Grundsätze

- Webserver zeigt ausschließlich auf `public/`.
- `data/`, `config/config.local.yaml`, `vendor/` und `bin/` sind nicht öffentlich
  erreichbar.
- Für den Produktivbetrieb `composer install --no-dev --optimize-autoloader` verwenden.
- Logs landen in `data/logs/ltolabel.log` (Level in `config/config.yaml`).

## Docker

Das Image basiert auf dem offiziellen PHP-Apache-Image, installiert die
PHP-Erweiterungen (`pdo_sqlite`, `pdo_mysql`, `mbstring`, `fileinfo`) und nutzt
`public/` als DocumentRoot. Ein HEALTHCHECK prüft die Erreichbarkeit des
Webservers unter `/`.

```bash
docker compose up --build
```

Die Anwendung ist danach unter <http://localhost:8080> erreichbar. Persistente Daten
(SQLite) und Logs liegen in benannten Volumes.

### Konfiguration im Container

Standardwerte sind im Image enthalten. Für eigene Werte die Datei
`config/config.local.yaml` in das Volume `config` legen oder die Umgebungsvariablen
verwenden (siehe `docker-compose.yml`).

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
- **Monitoring**: HTTP-Healthcheck z. B. auf `/` (200 OK) oder `/api/labels`.
