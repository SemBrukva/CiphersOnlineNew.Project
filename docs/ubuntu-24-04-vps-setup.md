# Рекомендации по настройке VPS для Ciphers Online

Документ описывает рекомендуемую production-настройку проекта на чистой Ubuntu 24.04 LTS.

Проект — PHP 8.3 MVC-приложение без фреймворка, с публичной точкой входа `public/index.php`, Smarty-шаблонами, Vite-сборкой ассетов, MySQL/SQLite через PDO, файловым storage, Memcached-кешем, встроенной DB-очередью и health-check endpoint-ом `/healthz`.

## Рекомендуемый подход

Для первого VPS рекомендуется классический деплой без Docker:

- Nginx как публичный web-сервер.
- PHP 8.3-FPM для выполнения приложения.
- MySQL 8.0 как production-БД.
- Memcached для кеша и rate limit.
- Файловые сессии на одном сервере.
- Certbot для TLS-сертификата.
- systemd для queue worker, если используется асинхронная почта.
- cron для очистки файловых сессий.

Docker-конфигурация в проекте есть, но для первого production-запуска ручной деплой проще, прозрачнее и надежнее. Перед использованием Docker в production стоит отдельно доработать передачу собранной статики в nginx-контейнер.

## Размер VPS

Минимально для старта:

- 1 vCPU
- 1 GB RAM
- 20 GB SSD

Комфортно:

- 2 vCPU
- 2 GB RAM
- 40 GB SSD

Если MySQL, Memcached, очередь и nginx находятся на одной машине, вариант 2 GB RAM предпочтительнее.

## Базовая подготовка сервера

```bash
sudo apt update
sudo apt upgrade -y

sudo apt install -y \
  nginx \
  mysql-server \
  memcached \
  git \
  unzip \
  curl \
  composer \
  certbot \
  python3-certbot-nginx \
  php8.3-fpm \
  php8.3-cli \
  php8.3-mysql \
  php8.3-mbstring \
  php8.3-curl \
  php8.3-xml \
  php8.3-memcached \
  php8.3-opcache
```

Node.js на сервере не обязателен, если `public/build/` собирается локально или в CI и затем загружается на сервер. Если сборка выполняется на VPS, установите Node.js 20+.

## Пользователь и директория проекта

Рекомендуемая структура:

```text
/var/www/ciphers-online
```

Пример:

```bash
sudo mkdir -p /var/www/ciphers-online
sudo chown -R "$USER":"$USER" /var/www/ciphers-online

git clone <repo-url> /var/www/ciphers-online
cd /var/www/ciphers-online
```

## MySQL

Создайте отдельную БД и пользователя:

```sql
CREATE DATABASE ciphers_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ciphers_online'@'localhost' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON ciphers_online.* TO 'ciphers_online'@'localhost';
FLUSH PRIVILEGES;
```

Для публичного VPS не открывайте порт MySQL наружу. Доступ приложению нужен только через `127.0.0.1` или `localhost`.

## Production .env

Создайте файл:

```bash
cp private/.env.example private/.env
nano private/.env
```

Рекомендуемые значения:

```dotenv
APP_NAME="Ciphers Online"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_TIMEZONE=UTC
APP_FORCE_HTTPS=true

APP_MULTILANG=true
APP_LOCALE=en
USER_REGISTRATION=false
USER_VERIFICATION=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ciphers_online
DB_USERNAME=ciphers_online
DB_PASSWORD=strong-password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_STRICT_MODE=true

ADMIN_IDS=1
ADMIN_PATH=/change-this-admin-path

SESSION_DRIVER=file
SESSION_SECURE=true

CACHE_DRIVER=memcache
CACHE_PREFIX=ciphers_
CACHE_TTL=3600
MEMCACHE_HOST=127.0.0.1
MEMCACHE_PORT=11211

CORS_ALLOWED_ORIGINS=https://example.com

LOG_LEVEL=warning
LOG_FORMAT=json

MAIL_DSN=null://null
MAIL_FROM=no-reply@example.com
MAIL_FROM_NAME="Ciphers Online"
```

Важно: `CACHE_DRIVER=memcache` нужен не только для ускорения. В проекте rate limit хранит счетчики через `CacheInterface`; при `CACHE_DRIVER=null` лимиты фактически не накапливаются между запросами.

## Зависимости и сборка

На сервере:

```bash
cd /var/www/ciphers-online
composer install --no-dev --optimize-autoloader --no-interaction
```

Если фронтенд собирается на сервере:

```bash
npm ci
npm run build
```

Если фронтенд собирается локально или в CI, на сервер нужно доставить готовую директорию:

```text
public/build/
```

В production не должно быть файла:

```text
public/build/hot
```

## Права на файлы

Приложению нужна запись в `private/storage`.

```bash
sudo chown -R www-data:www-data /var/www/ciphers-online/private/storage
sudo chmod -R 775 /var/www/ciphers-online/private/storage
```

Остальной код можно оставить владельцем deploy-пользователя, а nginx/php-fpm должен читать его через стандартные права.

## Миграции и кеши приложения

```bash
cd /var/www/ciphers-online

php bin/console migrate
php bin/console config:cache
php bin/console route:cache
```

После каждого деплоя с изменениями PHP-кода нужно перезагружать PHP-FPM, чтобы сбросить OPcache:

```bash
sudo systemctl reload php8.3-fpm
```

## Nginx

Создайте конфиг:

```bash
sudo nano /etc/nginx/sites-available/ciphers-online
```

Пример:

```nginx
server {
    listen 80;
    server_name example.com www.example.com;

    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name example.com www.example.com;

    root /var/www/ciphers-online/public;
    index index.php;

    client_max_body_size 16M;
    server_tokens off;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ /\.(env|git|htaccess) {
        deny all;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_hide_header X-Powered-By;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|webp|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }
}
```

Активируйте сайт:

```bash
sudo ln -s /etc/nginx/sites-available/ciphers-online /etc/nginx/sites-enabled/ciphers-online
sudo nginx -t
sudo systemctl reload nginx
```

## TLS

После настройки DNS:

```bash
sudo certbot --nginx -d example.com -d www.example.com
```

Проверьте автообновление:

```bash
sudo certbot renew --dry-run
```

## PHP-FPM и OPcache

Создайте отдельный ini-файл:

```bash
sudo nano /etc/php/8.3/fpm/conf.d/99-ciphers-online.ini
```

Рекомендуемые значения:

```ini
expose_php=Off
display_errors=Off
log_errors=On
error_log=/var/www/ciphers-online/private/storage/logs/php.log

opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
```

Для небольшого VPS можно оставить стандартный pool `www`. Если нужен отдельный pool, настройте его под пользователя `www-data` и сокет `/run/php/php8.3-fpm.sock`.

Перезапуск:

```bash
sudo systemctl restart php8.3-fpm
```

## Memcached

Memcached должен слушать только локальный интерфейс. Проверьте `/etc/memcached.conf`:

```text
-l 127.0.0.1
-m 64
```

Перезапуск:

```bash
sudo systemctl restart memcached
sudo systemctl enable memcached
```

## Cron для файловых сессий

Если используется `SESSION_DRIVER=file`, добавьте cron:

```bash
sudo nano /etc/cron.d/ciphers-online
```

```cron
0 * * * * www-data php /var/www/ciphers-online/private/crons/cleanup_old_sessions.php
```

Файлу `/etc/cron.d/ciphers-online` нужен перенос строки в конце.

Если позже перейти на Redis или Memcached-сессии, этот cron станет не нужен.

## Queue worker

Очередь в проекте хранится в БД и нужна прежде всего для асинхронной отправки писем. Если `USER_REGISTRATION=false`, `USER_VERIFICATION=false` и почта не используется, worker можно не запускать.

Если асинхронная почта нужна, создайте systemd unit:

```bash
sudo nano /etc/systemd/system/ciphers-online-worker.service
```

```ini
[Unit]
Description=Ciphers Online queue worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/ciphers-online
ExecStart=/usr/bin/php /var/www/ciphers-online/bin/console queue:work --sleep=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Запуск:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now ciphers-online-worker
sudo systemctl status ciphers-online-worker
```

После деплоя с изменениями кода worker тоже нужно перезапускать:

```bash
sudo systemctl restart ciphers-online-worker
```

## Firewall

Минимальный UFW:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

MySQL и Memcached не должны быть открыты наружу.

## Smoke-тесты после запуска

```bash
curl -I https://example.com
curl https://example.com/healthz
```

Ожидаемо:

- Главная страница возвращает `200`.
- `/healthz` возвращает JSON.
- `checks.db` равен `ok`.
- `checks.storage` равен `ok`.
- `checks.cache` равен `ok`, если Memcached включен.

## Типовой деплой обновления

```bash
cd /var/www/ciphers-online

git pull
composer install --no-dev --optimize-autoloader --no-interaction

# Если сборка выполняется на сервере:
npm ci
npm run build

php bin/console migrate
php bin/console config:cache
php bin/console route:cache

sudo chown -R www-data:www-data private/storage
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

# Только если worker включен:
sudo systemctl restart ciphers-online-worker
```

## Бэкапы

Минимум:

- ежедневный дамп MySQL;
- архив `private/.env`;
- архив `private/storage`, если там появятся пользовательские или runtime-данные, которые нельзя восстановить из миграций.

Пример дампа:

```bash
mkdir -p /var/backups/ciphers-online
mysqldump ciphers_online | gzip > /var/backups/ciphers-online/db-$(date +%F).sql.gz
```

Для production лучше настроить удаленное хранение бэкапов, например S3-совместимое хранилище или отдельный backup-сервер.

## Production-чеклист

- DNS указывает на VPS.
- TLS-сертификат выпущен через Certbot.
- Nginx root указывает на `/var/www/ciphers-online/public`.
- `private/` не доступна из web.
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_FORCE_HTTPS=true`.
- `SESSION_SECURE=true`.
- `CACHE_DRIVER=memcache`.
- `CORS_ALLOWED_ORIGINS` содержит реальный домен.
- `ADMIN_PATH` изменен на нестандартный путь.
- `private/.env` не попадает в git.
- `public/build/` собран и загружен.
- `public/build/hot` отсутствует.
- Миграции применены.
- `config:cache` и `route:cache` выполнены.
- `private/storage` доступен на запись пользователю `www-data`.
- Cron для файловых сессий установлен.
- Queue worker включен только если реально нужна асинхронная почта.
- `/healthz` возвращает успешный статус.
- Настроены бэкапы MySQL.
