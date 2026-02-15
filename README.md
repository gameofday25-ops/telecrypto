# TeleCrypto (Telegram Bot + WebApp) for shared hosting

MVP на PHP 8.1+/MySQL без Docker/Node/Redis.

## Почему была ошибка `Unexpected end of JSON input`

На фронте это происходило, когда backend отдавал HTML/fatal вместо JSON (например, падение БД до формирования JSON-ответа).

Что исправлено:
- API теперь обернут в общий `try/catch` и даже при исключениях возвращает JSON формата `{ok:false,error:{...}}`.
- Frontend теперь безопасно парсит ответ и выводит понятное сообщение, если backend вернул не-JSON.

## Что добавлено по вашему запросу

- Вернул **тестовое пополнение** (`method=TEST`) в кабинете.
- Добавил **10+ тестовых тикеров** в сиды (`schema.sql`), их можно резервировать.
- Добавил оплату через **CryptoBot**:
  - создание инвойса через Crypto Pay API;
  - сохранение `invoice_id`/`pay_url` в `deposits`;
  - webhook `/cryptobot.php?secret=...` для авто-зачисления `invoice_paid`.

## Структура

- `public/bot.php` — Telegram webhook.
- `public/cryptobot.php` — CryptoBot webhook.
- `public/api/index.php` — API.
- `public/app/*` — WebApp статик.
- `app/migrations/schema.sql` — схема + сиды.

## Установка

1. Импортируйте `app/migrations/schema.sql`.
2. Залейте проект по FTP.
3. Рекомендуемый `DocumentRoot` = `public/`.
4. Заполните `.env` (или через `/install/install.php`).

## .env (дополнительно)

```env
CRYPTOBOT_API_TOKEN=
CRYPTOBOT_WEBHOOK_SECRET=change_me_secret
```

## Telegram webhook

```bash
https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://domain.com/bot.php
```

## CryptoBot webhook

Укажите в Crypto Pay API/кабинете webhook:

```text
https://domain.com/cryptobot.php?secret=YOUR_SECRET
```

## BotFather WebApp URL

- если `public` = DocumentRoot: `https://domain.com/app/`
- если подпапка: `https://domain.com/111/public/app/`

## Мини-чеклист после установки

1. `GET /health` -> `{ok:true,status:"up"}`
2. Открыть WebApp из Telegram.
3. Сделать тестовое пополнение (+100) и проверить в списке.
4. Создать инвойс CryptoBot и оплатить (статус должен перейти в `PAID` после webhook).
5. Создать резерв (order) из выбранных тикеров.
