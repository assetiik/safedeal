#!/bin/sh
set -e

cd /app

mkdir -p /data/storage/framework/cache \
         /data/storage/framework/sessions \
         /data/storage/framework/views \
         /data/storage/logs \
         /data/storage/app/private/documents \
         /data/bootstrap-cache

# point Laravel storage to persistent volume
rm -rf storage
ln -sfn /data/storage storage
rm -rf bootstrap/cache
ln -sfn /data/bootstrap-cache bootstrap/cache

if [ ! -f .env ]; then
  cp .env.example .env
fi

php -r '
$path = ".env";
$env = file_exists($path) ? file_get_contents($path) : "";
$pairs = [
  "APP_ENV" => "production",
  "APP_DEBUG" => "false",
  "APP_URL" => getenv("APP_URL") ?: "http://194.32.141.214:8001",
  "DB_CONNECTION" => "sqlite",
  "DB_DATABASE" => "/data/database.sqlite",
  "SESSION_DRIVER" => "file",
  "CACHE_STORE" => "file",
  "QUEUE_CONNECTION" => "sync",
  "MAIL_MAILER" => "log",
  "FILESYSTEM_DISK" => "local",
  "PAYMENT_PROVIDER" => "sandbox",
  "PAYMENT_SANDBOX_AUTO_COMPLETE" => "true",
  "ESCROW_AUTO_START_WORK" => "true",
];
foreach ($pairs as $key => $value) {
  if (preg_match("/^{$key}=.*/m", $env)) {
    $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
  } else {
    $env .= "\n{$key}={$value}\n";
  }
}
foreach (["DB_HOST","DB_PORT","DB_USERNAME","DB_PASSWORD"] as $key) {
  $env = preg_replace("/^{$key}=/m", "#{$key}=", $env);
}
file_put_contents($path, $env);
'

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

touch /data/database.sqlite
chmod -R ug+rwx /data

php artisan config:clear
php artisan migrate --force

if [ ! -f /data/.seeded ]; then
  php artisan db:seed --force
  touch /data/.seeded
fi

php artisan storage:link || true

exec php artisan serve --host=0.0.0.0 --port=8001
