# Деплой в продакшн

## Содержание

- [Рекомендуемый стек](#рекомендуемый-стек)
- [Docker-деплой](#docker-деплой)
- [Ручной деплой (без Docker)](#ручной-деплой-без-docker)
- [Переменные окружения](#переменные-окружения)
- [Продакшн-чеклист](#продакшн-чеклист)

---

## Рекомендуемый стек

| Компонент  | Рекомендация     | Альтернатива |
|------------|------------------|--------------|
| PHP        | 8.3-FPM          | —            |
| Web-сервер | Nginx 1.27       | Apache 2.4   |
| БД         | MySQL 8.0        | SQLite (dev) |
| Сессии     | Redis 7          | Memcached    |
| Кеш        | Memcached 1.6    | Redis        |
| Очередь    | встроенная (БД)  | —            |

---

## Docker-деплой

### 1. Подготовка

```bash
# Собрать фронтенд на хосте (результат попадёт в public/build/)
make build

# Скопировать и настроить .env
cp private/.env.example private/.env
```

Обязательные переменные в `private/.env` для продакшна — см. [Переменные окружения](#переменные-окружения).

### 2. Сборка и запуск

```bash
docker compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  --profile mysql \
  --profile redis \
  up -d --build
```

`docker-compose.prod.yml` переключает образ на target `production`, отключает bind-mount исходников и устанавливает `APP_ENV=production`.

### 3. Миграции

```bash
docker compose exec app php bin/console migrate
```

### 4. Проверка

```bash
docker compose ps          # все сервисы Running
docker compose logs app    # ошибок нет
curl -I http://localhost:8080   # 200 OK
```

### Обновление приложения

```bash
git pull

# Пересобрать фронтенд
make build

# Пересобрать образ и перезапустить
docker compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  up -d --build app

# Применить новые миграции
docker compose exec app php bin/console migrate
```

---

## Ручной деплой (без Docker)

### Требования на сервере

- PHP 8.3-FPM с расширениями: `pdo`, `pdo_mysql`, `mbstring`, `curl`, `opcache`, `xmlwriter`, `redis` или `memcached`
- Nginx ≥ 1.24
- Composer 2
- Node.js ≥ 20 (только для сборки фронтенда, не нужен на сервере)

### Шаги

**1. Код и зависимости**

```bash
git clone <repo> /var/www/skeleton
cd /var/www/skeleton

composer install --no-dev --optimize-autoloader
```

**2. Настройка окружения**

```bash
cp private/.env.example private/.env
# Отредактировать private/.env — см. раздел «Переменные окружения»
```

**3. Сборка фронтенда**

Собирать локально или на CI, затем загружать `public/build/` на сервер:

```bash
npm ci && npm run build
```

**4. Права доступа**

```bash
chown -R www-data:www-data /var/www/skeleton/private/storage
chmod -R 775 /var/www/skeleton/private/storage
```

**5. Миграции**

```bash
php bin/console migrate
```

**6. Nginx**

```nginx
server {
    listen 80;
    server_name example.com;

    # Принудительный редирект на HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com;

    ssl_certificate     /etc/ssl/certs/example.com.crt;
    ssl_certificate_key /etc/ssl/private/example.com.key;

    root /var/www/skeleton/public;
    index index.php;

    # Запрет доступа к приватным директориям
    location ~ ^/private/ {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Кеширование статики
    location ~* \.(css|js|woff2?|ttf|ico|png|jpg|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Скрыть версию сервера
    server_tokens off;
}
```

**7. PHP-FPM**

В `/etc/php/8.3/fpm/pool.d/www.conf` (или аналог):

```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 8
```

В `php.ini` для продакшна:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.validate_timestamps=0   ; не перечитывать файлы (перезагрузка при деплое)
display_errors=Off
log_errors=On
error_log=/var/www/skeleton/private/storage/logs/php.log
```

После деплоя сбросить OPcache:

```bash
php bin/console config:clear   # сбрасывает кеш конфигурации
# или перезапустить FPM:
systemctl reload php8.3-fpm
```

**8. Воркер очереди (опционально)**

```ini
# /etc/supervisor/conf.d/skeleton-worker.conf
[program:skeleton-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/skeleton/bin/console queue:work --sleep=3 --max-time=3600
directory=/var/www/skeleton
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/skeleton/private/storage/logs/worker.log
```

```bash
supervisorctl reread && supervisorctl update
supervisorctl start skeleton-worker:*
```

---

## Переменные окружения

Файл `private/.env` (не попадает в репозиторий).

### Обязательные

```dotenv
APP_NAME=MyApp
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=myapp
DB_PASSWORD=secret
```

### Безопасность

```dotenv
APP_FORCE_HTTPS=true         # принудительный редирект на HTTPS
TRUSTED_PROXIES=10.0.0.0/8  # IP/CIDR обратных прокси; * — доверять всем
SESSION_SECURE=true          # cookie SESSION только по HTTPS
ADMIN_PATH=/your-secret-path # нестандартный URL панели администратора
ADMIN_IDS=1,2                # ID администраторов из таблицы users
```

### Сессии

```dotenv
SESSION_DRIVER=redis         # file | memcached | redis
SESSION_TTL=86400            # TTL в секундах (для Memcached / Redis)

# Redis
SESSION_REDIS_HOST=127.0.0.1
SESSION_REDIS_PORT=6379
SESSION_REDIS_PASSWORD=secret
SESSION_REDIS_DB=1

# Memcached
SESSION_MEMCACHED_HOST=127.0.0.1
SESSION_MEMCACHED_PORT=11211
```

### Кеш

```dotenv
CACHE_DRIVER=memcache        # null | memcache
CACHE_PREFIX=app_
CACHE_TTL=3600
MEMCACHE_HOST=127.0.0.1
MEMCACHE_PORT=11211
```

### Логирование

```dotenv
LOG_LEVEL=warning            # debug | info | notice | warning | error | critical
LOG_FORMAT=json              # text | json
LOG_WEBHOOK_PROD=https://hooks.slack.com/...  # вебхук Slack / Discord (опционально)
```

### Почта

```dotenv
MAIL_DSN=smtp://user:pass@smtp.mailjet.com:587
MAIL_FROM=no-reply@example.com
MAIL_FROM_NAME=MyApp
```

### CORS (для API)

```dotenv
CORS_ALLOWED_ORIGINS=https://example.com,https://app.example.com
```

---

## Продакшн-чеклист

### Безопасность

- [ ] `APP_DEBUG=false` — стектрейсы не отображаются пользователям
- [ ] `APP_FORCE_HTTPS=true` — HTTP редиректит на HTTPS
- [ ] `SESSION_SECURE=true` — SESSION-cookie только по HTTPS
- [ ] `ADMIN_PATH` изменён с `/admin` на непредсказуемый путь
- [ ] `CORS_ALLOWED_ORIGINS` содержит конкретные домены, не `*`
- [ ] `TRUSTED_PROXIES` ограничен IP-диапазоном вашего балансировщика
- [ ] Nginx скрывает `X-Powered-By` и `Server`
- [ ] Директория `private/` недоступна через веб (`deny all` в Nginx)
- [ ] Права `private/storage/` — `www-data:www-data 775`
- [ ] `private/.env` исключён из репозитория (`.gitignore`)

### Производительность

- [ ] `opcache.enable=1`, `opcache.validate_timestamps=0`
- [ ] Фронтенд собран (`make build`), `public/build/` залит на сервер
- [ ] Кеш конфигурации: `php bin/console config:cache`
- [ ] Кеш маршрутов: `php bin/console route:cache`
- [ ] `CACHE_DRIVER=memcache` настроен (если Memcached доступен)
- [ ] `SESSION_DRIVER=redis` — сессии в Redis (для multi-node)

### Наблюдаемость

- [ ] `LOG_LEVEL=warning` или выше
- [ ] `LOG_WEBHOOK_PROD` указывает на реальный вебхук оповещений
- [ ] Мониторинг процесса FPM (Supervisor / systemd)
- [ ] Воркер очереди запущен под Supervisor (если используется очередь)

### Деплой

- [ ] Миграции применены: `php bin/console migrate`
- [ ] OPcache сброшен после деплоя: `systemctl reload php8.3-fpm`
- [ ] Smoke-тест: `curl -I https://example.com` возвращает 200
