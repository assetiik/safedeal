# Deploy

## Требования

- PHP 8.3+
- MySQL 8
- Composer
- Nginx / Caddy
- Supervisor (queue, опционально при `QUEUE_CONNECTION=database`)

## Шаги

```bash
composer install --no-dev -o
cp .env.example .env
# заполнить APP_KEY, DB_*, MAIL_*, PAYMENT_*
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

Document root: `public/`.

HTTPS обязателен. CORS — домены Flutter Web в `config/cors.php`.

## Рекомендуемые env (prod)

```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
HASH_DRIVER=argon2id
QUEUE_CONNECTION=database
PAYMENT_PROVIDER=sandbox   # пока нет банка
PAYMENT_SANDBOX_AUTO_COMPLETE=false
ESCROW_AUTO_START_WORK=true
```

## Health

`GET /up`
