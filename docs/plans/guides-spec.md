# ТЗ: Гайды и статьи (файловые, без БД)

> Раздел `/guides` — длинные обучающие статьи, разборы и история по шифрам и кодам.
> Контент генерируется LLM и хранится **в JSON-файлах** (Git = источник истины).
> **Без БД, без миграций, без админ-CRUD, без команд export/import.**

Связано с: [product-growth-roadmap.md](product-growth-roadmap.md) (Опора A, инициатива A1, Фаза 1),
[glossary-spec.md](glossary-spec.md) (тот же файловый подход).
Контракт JSON: [../guide-article-json.md](../guide-article-json.md).

---

## 1. Решение об архитектуре

Полностью зеркалит глоссарий (см. §1 [glossary-spec.md](glossary-spec.md)): **чисто файловый** read-mostly
контент авторства LLM, кэш с длинным TTL, версионирование через Git/PR. Отличие от глоссария —
формат страницы (длинная статья с `Article` schema вместо короткого определения с `DefinedTerm`).

**Почему отдельный раздел, а не расширение глоссария:**
- Разный intent: глоссарий ловит «что такое X» (короткий ответ), гайды — «как сделать X / история X / топ X» (лонгрид).
- Разная разметка: `Article` (+ `datePublished`, `author`, `reading_time`) против `DefinedTerm`.
- Разная перелинковка: гайд → инструменты + термины + другие гайды.

---

## 2. Цели, не-цели, scope v1

**Цели:**
- Верх воронки: информационные и «how-to» запросы, которые ведут в transactional tool-страницы.
- E-E-A-T и внутренний PageRank: гайд → 3–6 инструментов, инструмент → 1–2 гайда, гайд → термины глоссария.
- Нулевой операционный оверхед: LLM пишет файл → PR → деплой.

**Не-цели (v1):**
- Админ-редактор статей (правки — через файлы/PR).
- Комментарии, лайки, авторские профили.
- Полнотекстовый поиск по гайдам (на индексе есть клиентский фильтр по заголовку).

**Scope v1:**
- `/guides` (индекс, сгруппирован по категориям, клиентский фильтр) и `/guides/{slug}` (статья).
- Полное мультиязычие (8 локалей) с правилом «нет fallback → 404» (как глоссарий).
- SEO: meta, canonical, hreflang, `Article` + `BreadcrumbList` + `FAQPage`, хлебные крошки, sitemap (XML + HTML), llms.txt.
- Перелинковка guide↔guide, guide→tool, guide→term; tool→guide через `config/guide_related.php`.
- Команда `guides:validate` для CI.

---

## 3. Реализация (сделано)

| Компонент | Файл |
|---|---|
| Репозиторий (файлы + кэш) | `App\Guide\GuideRepository` |
| Контроллер (index/show) | `App\Controller\GuidesController` |
| Маршруты (выше catch-all) | `config/routes.php` — `/guides`, `/guides/{slug}` |
| Шаблоны | `views/guides/index.tpl`, `views/guides/show.tpl` |
| Стили | блок «Guides» в `resources/css/app.css` (переиспользует классы Glossary) |
| Переводы UI | `GUIDES_*`, `MENU_GUIDES` во всех `translates/*.php` |
| Категории | `how-to`, `deep-dive`, `history`, `lists` |
| Sitemap | ветка гайдов в `SitemapController` (XML + HTML) |
| llms.txt | секция «Guides & Articles» в `LlmsController` |
| Обратные ссылки tool→guide | `config/guide_related.php` + `CipherController::buildGuideLinks` |
| Валидация | `guides:validate` (`GuidesValidateCommand`) |
| Навигация | пункт в футере (`MENU_GUIDES`) |

Категории (`config` порядок): `how-to` → `deep-dive` → `history` → `lists`.

---

## 4. План по выписыванию статей (зафиксировано)

**Правила:**
- **`slug` канонический и неизменный** — имя папки и значение `meta.guide_slug`. Менять после публикации нельзя (только через 301 в менеджере редиректов админки).
- Названия ниже — целевые значения `guide.title` для каждой локали. `excerpt`/`meta_*`/`body`/`faq` LLM пишет по контракту ([../guide-article-json.md](../guide-article-json.md)); `en.json` — эталон, затем перевод на 7 локалей.
- **Batch 1** (⭐) — приоритетные статьи из роадмапа; пишутся первыми.
- **Порядок реализации контента:** сначала `en.json` для статьи (эталон + перелинковка), затем перевод на остальные 7 локалей, `guides:validate`, PR.

### 4.1. How-to — Практика (`how-to`)

| ⭐ | slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|---|
| ⭐ | caesar-cipher-manual-decryption | How to Decrypt a Caesar Cipher by Hand | Как расшифровать шифр Цезаря вручную | Eine Caesar-Chiffre von Hand entschlüsseln | Cómo descifrar un cifrado César a mano | Déchiffrer un chiffre de César à la main | Come decifrare un cifrario di Cesare a mano | Como decifrar uma cifra de César à mão | Sezar şifresi elle nasıl çözülür |
| | decrypt-cipher-without-key | How to Decrypt a Message Without the Key | Как расшифровать сообщение без ключа | Eine Nachricht ohne Schlüssel entschlüsseln | Cómo descifrar un mensaje sin la clave | Déchiffrer un message sans la clé | Come decifrare un messaggio senza chiave | Como decifrar uma mensagem sem a chave | Anahtar olmadan bir mesaj nasıl çözülür |
| | solve-substitution-cipher | How to Solve a Substitution Cipher | Как взломать шифр простой замены | Eine Substitutions­chiffre lösen | Cómo resolver un cifrado de sustitución | Résoudre un chiffre par substitution | Come risolvere un cifrario a sostituzione | Como resolver uma cifra de substituição | Yerine koyma şifresi nasıl çözülür |
| | read-and-write-morse-code | How to Read and Write Morse Code | Как читать и писать азбукой Морзе | Morsecode lesen und schreiben | Cómo leer y escribir código Morse | Lire et écrire le code Morse | Come leggere e scrivere il codice Morse | Como ler e escrever código Morse | Mors alfabesi nasıl okunur ve yazılır |
| | understand-and-decode-base64 | How to Understand and Decode Base64 | Как понять и раскодировать Base64 | Base64 verstehen und dekodieren | Cómo entender y decodificar Base64 | Comprendre et décoder le Base64 | Come capire e decodificare Base64 | Base64 nasıl anlaşılır ve çözülür |

### 4.2. Deep-dive — Разборы (`deep-dive`)

| ⭐ | slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|---|
| ⭐ | vigenere-cipher-complete-guide | The Vigenère Cipher: A Complete Guide | Шифр Виженера: полный разбор | Die Vigenère-Chiffre: der vollständige Leitfaden | El cifrado Vigenère: guía completa | Le chiffre de Vigenère : guide complet | Il cifrario di Vigenère: guida completa | A cifra de Vigenère: guia completo | Vigenère şifresi: eksiksiz rehber |
| | caesar-cipher-explained | The Caesar Cipher Explained | Шифр Цезаря: как он работает | Die Caesar-Chiffre erklärt | El cifrado César explicado | Le chiffre de César expliqué | Il cifrario di Cesare spiegato | A cifra de César explicada | Sezar şifresi açıklandı |
| | playfair-cipher-explained | The Playfair Cipher Explained | Шифр Плейфера: подробный разбор | Die Playfair-Chiffre erklärt | El cifrado Playfair explicado | Le chiffre de Playfair expliqué | Il cifrario Playfair spiegato | A cifra Playfair explicada | Playfair şifresi açıklandı |
| | one-time-pad-perfect-secrecy | The One-Time Pad and Perfect Secrecy | Одноразовый блокнот и совершенная секретность | Das One-Time-Pad und perfekte Geheimhaltung | El cuaderno de un solo uso y el secreto perfecto | Le masque jetable et le secret parfait | Il one-time pad e la segretezza perfetta | O bloco de uso único e o sigilo perfeito | Tek kullanımlık şerit ve kusursuz gizlilik |
| | rsa-explained-simply | RSA Encryption Explained Simply | RSA простыми словами | RSA einfach erklärt | El cifrado RSA explicado de forma sencilla | Le chiffrement RSA expliqué simplement | La crittografia RSA spiegata in modo semplice | Criptografia RSA explicada de forma simples | RSA şifreleme basitçe açıklandı |
| | aes-explained | How AES Encryption Works | Как устроено шифрование AES | Wie AES-Verschlüsselung funktioniert | Cómo funciona el cifrado AES | Comment fonctionne le chiffrement AES | Come funziona la crittografia AES | Como funciona a criptografia AES | AES şifreleme nasıl çalışır |

### 4.3. History — История (`history`)

| ⭐ | slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|---|
| ⭐ | history-of-the-enigma-machine | The History of the Enigma Machine | История машины «Энигма» | Die Geschichte der Enigma-Maschine | La historia de la máquina Enigma | L'histoire de la machine Enigma | La storia della macchina Enigma | A história da máquina Enigma | Enigma makinesinin tarihi |
| | brief-history-of-cryptography | A Brief History of Cryptography | Краткая история криптографии | Eine kurze Geschichte der Kryptografie | Breve historia de la criptografía | Brève histoire de la cryptographie | Breve storia della crittografia | Breve história da criptografia | Kriptografinin kısa tarihi |
| | history-of-the-caesar-cipher | The History of the Caesar Cipher | История шифра Цезаря | Die Geschichte der Caesar-Chiffre | La historia del cifrado César | L'histoire du chiffre de César | La storia del cifrario di Cesare | A história da cifra de César | Sezar şifresinin tarihi |
| | zodiac-killer-ciphers | The Zodiac Killer Ciphers | Шифры Зодиакального убийцы | Die Chiffren des Zodiac-Killers | Los cifrados del asesino del Zodíaco | Les chiffres du tueur du Zodiaque | I cifrari del killer dello Zodiaco | As cifras do assassino do Zodíaco | Zodiac katilinin şifreleri |

### 4.4. Lists — Подборки (`lists`)

| ⭐ | slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|---|
| ⭐ | top-15-escape-room-ciphers | Top 15 Ciphers for Escape Rooms | Топ-15 шифров для escape-room | Die 15 besten Chiffren für Escape Rooms | Los 15 mejores cifrados para escape rooms | Top 15 des chiffres pour escape games | I 15 migliori cifrari per escape room | Os 15 melhores cifras para escape rooms | Escape room için en iyi 15 şifre |
| | best-ciphers-for-kids | The Best Ciphers for Kids and Classrooms | Лучшие шифры для детей и уроков | Die besten Chiffren für Kinder und den Unterricht | Los mejores cifrados para niños y aulas | Les meilleurs chiffres pour les enfants et l'école | I migliori cifrari per bambini e scuola | As melhores cifras para crianças e escolas | Çocuklar ve okullar için en iyi şifreler |
| | ciphers-in-movies-and-games | Ciphers in Movies and Video Games | Шифры в кино и видеоиграх | Chiffren in Filmen und Videospielen | Cifrados en películas y videojuegos | Les chiffres dans les films et les jeux vidéo | Cifrari nei film e nei videogiochi | Cifras em filmes e videogames | Filmlerde ve video oyunlarında şifreler |
| | famous-unsolved-ciphers | Famous Unsolved Ciphers | Знаменитые нераскрытые шифры | Berühmte ungelöste Chiffren | Cifrados famosos sin resolver | Les célèbres chiffres non résolus | Famosi cifrari irrisolti | Cifras famosas não resolvidas | Ünlü çözülmemiş şifreler |

**Итого v1: 18 статей** (5 how-to + 5 deep-dive + 4 history + 4 lists), из них **Batch 1 — 4** (⭐).

> Названия из таблиц — стартовые; при написании `body` LLM может уточнить формулировку, но `slug` остаётся неизменным. Расширение списка (v2) — по семантике из `storage/semantic-core/*`.

---

## 5. Критерии приёмки (Definition of Done)

- [ ] `/guides` и `/guides/{slug}` открываются на всех 8 локалях; корректный локальный префикс.
- [ ] Маршруты не перехватываются catch-all'ами шифров/категорий.
- [ ] Отсутствующая/черновая статья → 404; `draft` вне индекса/sitemap/llms.
- [ ] canonical, hreflang (только для существующих локалей), x-default, OG корректны.
- [ ] `Article` + `BreadcrumbList` (+ `FAQPage` при FAQ) валидны в Rich Results Test.
- [ ] Статьи и `/guides` присутствуют в `sitemap.xml` (кроме draft) с корректным lastmod.
- [ ] Перелинковка guide↔guide, guide→tool, guide→term и tool→guide работает; битые ссылки скрыты.
- [ ] `guides:validate` проходит на опубликованном наборе; подключён в CI.
- [ ] Контент читается только из файлов — ни одной таблицы БД, миграции или import-команды.

---

## 6. Принятые решения

1. **URL сегмент:** `/guides` для всех локалей (локализованные slug'и — возможный v2).
2. **Перевод:** полный на все локали — обязательная часть разработки статьи; fallback нет (404). Незавершённая статья держится во всех локалях в `status: draft` до готовности.
3. **Навигация:** только футер (в главное меню не выносим, как глоссарий).
4. **Схема:** `guide-article.v1`; `Article` schema (не `BlogPosting`) — материалы вечнозелёные, авторство — организация.
5. **Категории:** `how-to`, `deep-dive`, `history`, `lists` (зафиксировано в §4).
