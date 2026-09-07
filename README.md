# SafeDeal API

Laravel 13 backend для эскроу-сделок (Flutter Web клиент).

## Стек

- PHP 8.4 / Laravel 13
- MySQL 8 (prod) / SQLite (local & tests)
- Sanctum access + refresh tokens
- Sandbox PaymentProvider (заменяется реальным банком без смены бизнес-логики)

## Быстрый старт (local)

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

API: `http://127.0.0.1:8000/api/v1`

### Тестовые пользователи

| Email / логин | Password | Role |
|-------|----------|------|
| admin (или admin@safedeal.test) | admin | admin |
| customer@safedeal.test | Password123 | customer |
| contractor@safedeal.test | Password123 | contractor |

## Flutter

1. Base URL: `{APP_URL}/api/v1`
2. Header: `Authorization: Bearer {access_token}`
3. Header: `Accept: application/json`
4. Оплата: `Idempotency-Key: <uuid>`
5. Статусы сделки — snake_case (`awaiting_executor`)
6. Суммы — `amount_tenge` integer
7. Ошибки: `{ "error": { "code", "message", "details" } }`

После sandbox-оплаты статус сделки обычно `in_progress` (автостарт работы, `ESCROW_AUTO_START_WORK=true`).

## Документация

- `docs/openapi.yaml` — OpenAPI 3
- `docs/STATE_MACHINE.md` — переходы статусов
- `docs/PAYMENT_PROVIDER.md` — decision record
- `docs/ERROR_CODES.md` — коды ошибок
- `docs/DEPLOY.md` — деплой

## Docker (staging/VPS)

```bash
# на сервере
cd /var/www/safedeal
# остановить старый artisan serve, если был
pkill -f "artisan serve" || true

# установить Docker (один раз)
apt-get update
apt-get install -y docker.io docker-compose-v2
systemctl enable --now docker

git pull
docker compose down
docker compose up -d --build
docker compose logs -f --tail=50
```

- API / админка: `http://194.32.141.214:8001`
- Flutter Base URL: `http://194.32.141.214:8001/api/v1`
- Логин админки: `admin` / `admin`
- Health: `GET /up`

Данные SQLite и storage живут в volume `safedeal_data` и переживают rebuild.

## Админ-панель (веб)

```bash
php artisan serve
# открыть http://127.0.0.1:8000/admin/login
```

Логин: `admin` / `admin`

Разделы: дашборд, сделки, пользователи, споры, финансы, документы, аудит-лог.

## Тесты

```bash
php artisan test
```
