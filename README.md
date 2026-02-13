# TeleCrypto (Telegram Bot + WebApp) for shared hosting

Production-ready MVP на PHP 8.1+/MySQL без Docker/Node/Redis.

## Структура

- `app/src` — backend-код (конфиг, DB, сервисы, репозитории, безопасность).
- `public/index.php` — роутер.
- `public/bot.php` — Telegram webhook endpoint.
- `public/api/index.php` — JSON API.
- `public/app/*` — статический WebApp.
- `public/admin/index.php` — админка.
- `app/migrations/schema.sql` — схема и сиды.
- `install/install.php` — веб-мастер установки.

## Установка по шагам (FTP + SQL)

1. **Создайте БД MySQL** в cPanel/ISPmanager.
2. **Импортируйте SQL** из `app/migrations/schema.sql` через phpMyAdmin.
3. **Залейте файлы по FTP** в директорию сайта.
4. Настройте **DocumentRoot** на папку `public/`.
   - Если нельзя сменить root: оставьте как есть и убедитесь, что `public/.htaccess` включен.
5. Откройте `https://domain.com/install/install.php`, заполните форму и сохраните `.env`.
   - Либо вручную создайте `.env` на основе `.env.example`.
6. Проверьте SSL: Telegram требует **https**.

## Настройка Telegram webhook

Подставьте токен и домен:

```bash
https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://domain.com/bot.php
```

## Настройка BotFather WebApp URL

- В BotFather откройте вашего бота.
- `Menu Button` или команду `setmenubutton`.
- Укажите URL: `https://domain.com/app/`.

## Доступные команды бота

- `/start` — регистрация и кнопка “Открыть приложение”.
- `/help`
- `/orders`
- `/wallet`
- `/support` — уведомление админам из `ADMIN_IDS`.

## API endpoints

Все под `/api`:

- `GET /me`
- `GET /tokens?search=&active=1`
- `GET /tokens/{id}`
- `GET /collections`
- `POST /favorites/toggle`
- `POST /orders/create`
- `GET /orders/list`
- `GET /deposits/list`
- `POST /deposits/create`

Admin:

- `POST /admin/token/create`
- `POST /admin/token/update`
- `POST /admin/collection/create`
- `POST /admin/collection/update`
- `POST /admin/order/status`
- `POST /admin/deposit/approve`

Единый JSON-формат:

```json
{ "ok": true, "data": {} }
```
или
```json
{ "ok": false, "error": { "code": "...", "message": "..." } }
```

## Безопасность

- Проверка подписи `initData` Telegram WebApp (`HMAC SHA-256`) в `Security.php`.
- PDO prepared statements везде.
- Rate-limit (IP + endpoint) через таблицу `rate_limits`.
- Логи и ротация по размеру — `app/storage/logs/app.log`.
- `.env` вынесен из `public` + защита `.htaccess`.

## Мини-чеклист после установки

1. `GET https://domain.com/health` → `{ok:true,status:"up"}`.
2. `POST https://domain.com/bot.php` (через Telegram webhook) отвечает `ok`.
3. Открыть WebApp из Telegram, убедиться что `/api/me` возвращает пользователя.
4. Создать тестовый депозит, затем одобрить в `/admin/`.
5. Создать ордер и сменить статус через админку.

