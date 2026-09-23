# Installation

## Voraussetzungen

- PHP **8.3+**
- PHP-Erweiterungen:
  - `pdo_sqlite` (Standard) oder `pdo_mysql` (MySQL-Betrieb)
  - `mbstring`
  - `json`
  - `session`
  - `fileinfo` (empfohlen)
- [Composer](https://getcomposer.org/)

## Abhängigkeiten installieren

```bash
composer install --no-dev          # Produktion
composer install                   # Entwicklung (inkl. PHPUnit)
```

## Konfiguration

1. Standardwerte liegen in `config/config.yaml`.
2. Für lokale Anpassungen:

```bash
cp config/config.local.example.yaml config/config.local.yaml
```

3. Datenbank wählen (Standard: SQLite unter `data/ltolabel.sqlite`; das Verzeichnis
   wird automatisch angelegt). Für MySQL den `database:`-Block in
   `config/config.local.yaml` überschreiben.

## Web-Server

### Entwicklung (PHP Built-in Server)

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

### Apache

`DocumentRoot` auf das `public/`-Verzeichnis zeigen lassen und alle Anfragen auf
`public/index.php` routen (Fallback). Beispiel `.htaccess` in `public/`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

### Nginx

```nginx
server {
    listen 80;
    root /var/www/ltolabel/public;
    index index.php;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

> Wichtig: `data/` und `config/config.local.yaml` dürfen nicht öffentlich erreichbar sein.

## Verzeichnisrechte

Der Webserver-Prozess benötigt Schreibrechte auf:

- `data/` (SQLite-Datei)
- `data/logs/` (Logdatei)
