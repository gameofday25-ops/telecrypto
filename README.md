# TeleCrypto (Telegram Bot + WebApp) for shared hosting

Production-ready MVP на PHP 8.1+/MySQL без Docker/Node/Redis.

## Что пошло не так в вашем деплое (почему видите `Index of /111`)

Вы открываете **корень каталога с архивом**, а не front-controller приложения.

Обычно причина одна из двух:

1. `DocumentRoot` указывает на папку проекта (где `app/`, `public/`, `install/`), а должен указывать на `public/`.
2. В BotFather в WebApp URL указан путь типа `https://domain.com/111` вместо `https://domain.com/111/public/app/` (или просто `https://domain.com/app/`, если `public` уже DocumentRoot).

В этой версии добавлен fallback (`index.php` в корне + `.htaccess`), чтобы даже при DocumentRoot=repo-root проект не показывал directory listing, а отдавал приложение.

---

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
4. Рекомендуется настроить **DocumentRoot** на `public/`.
   - Если это невозможно — оставьте корень репозитория, `.htaccess`/`index.php` уже добавлены как fallback.
5. Откройте `https://domain.com/install/install.php`, заполните форму и сохраните `.env`.
6. Проверьте SSL: Telegram требует **https**.

## Настройка Telegram webhook

```bash
https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://domain.com/bot.php
```

> Если проект в подпапке (`/111/public`), то webhook: `https://domain.com/111/public/bot.php`.

## Настройка BotFather WebApp URL

Укажите реальный URL WebApp:

- если `public` = DocumentRoot: `https://domain.com/app/`
- если сайт в подпапке: `https://domain.com/111/public/app/`

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
- `POST /deposits/create` (только реальный метод, без TEST)

Admin:

- `POST /admin/token/create`
- `POST /admin/token/update`
- `POST /admin/collection/create`
- `POST /admin/collection/update`
- `POST /admin/order/status`
- `POST /admin/deposit/approve`

## Безопасность

- Проверка подписи `initData` Telegram WebApp (`HMAC SHA-256`) в `Security.php`.
- PDO prepared statements.
- Rate-limit (IP + endpoint) через таблицу `rate_limits`.
- Логи и ротация по размеру — `app/storage/logs/app.log`.
- `.env` защищен через `.htaccess`.

## Мини-чеклист после установки

1. `GET https://domain.com/health` → `{ok:true,status:"up"}`.
2. `POST https://domain.com/bot.php` (webhook) отвечает `ok`.
3. Открыть WebApp из Telegram: загрузились профиль, витрина, подборки.
4. Создать заявку на пополнение (PENDING), затем одобрить в `/admin/`.
5. Создать ордер из корзины и сменить статус через админку.
