---
name: project-geo-ads
description: Гео-сегментация рекламы — РСЯ для RU/BY/KZ, Adsense для остальных
metadata:
  type: project
---

Реализована гео-сегментация рекламных сетей через MaxMind GeoLite2-Country.

**Why:** Разные рекламные сети для разных регионов: РСЯ эффективнее для RU/BY/KZ, Adsense — для остальных.

**How to apply:** При вопросах о рекламе/трекинге учитывать эту разбивку.

## Ключевые файлы

- `private/app/Geo/GeoIpService.php` — сервис геолокации (GeoIp2\Database\Reader), lazy init, singleton
- `private/config/geoip.php` — `enabled`, `db_path` (STORAGE_PATH/geo/GeoLite2-Country.mmdb), `rsya_countries`
- `private/storage/geo/GeoLite2-Country.mmdb` — база данных (не в git, обновлять вручную ежемесячно)
- `private/app/Http/Middleware/ShareViewDataMiddleware.php` — инжектирует GeoIpService, метод `trackingConfig(string $ip)` выбирает сеть по стране

## Логика выбора сети

- `isRsyaUser = countryCode in rsya_countries` (RU, BY, KZ)
- РСЯ: `yandex_rsya_enabled = true && TRACKING_YANDEX_RSYA_ENABLED=true`, `adsense_client_id = ''`
- Adsense: `adsense_client_id = TRACKING_ADSENSE_CLIENT_ID`, `yandex_rsya_enabled = false`
- Fallback (IP неизвестен/приватный): показывается Adsense

## Зависимость

Composer: `geoip2/geoip2 ^3.3`
