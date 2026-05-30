---
name: project-favorites
description: Фича «Добавить в избранное» — архитектура и ключевые файлы
metadata:
  type: project
---

Реализована фича «Избранное» (2026-05-30).

**Хранение:** `localStorage`, ключ `cipher_favorites`, значение — массив слагов `["classical-ciphers/caesar"]`.

**API:** `GET /api/favorites/ciphers?slugs[]=category/cipher` — возвращает `{ciphers: [{slug, name, name_short, description, url}]}`. Контроллер: `App\Controller\Api\FavoritesController`.

**Web:** `GET /favorites` — страница избранного. Контроллер: `App\Controller\FavoritesController`.

**Репозиторий:** добавлен метод `CipherRepository::findPublishedBySlugsWithTranslation()`.

**JS:** `private/resources/js/pages/favorites.js` — экспортирует `initFavoriteButton()` (кнопка на странице шифра) и `initFavoritesPage()` (страница /favorites). Инициализируется в `app.js`.

**Кнопка:** в `cipher/show.tpl` добавлена кнопка `.ciphers-unified__btn-favorite` с `data-slug` и `data-name`. При клике обновляет иконку и показывает feedback в `#ciphers-feedback`.

**Why:** пользователи хотят быстро возвращаться к часто используемым инструментам.
**How to apply:** при добавлении новых инструментов/категорий — новые слаги автоматически поддерживаются без изменений кода.
