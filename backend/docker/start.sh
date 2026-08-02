#!/bin/sh
set -eu

cd /var/www/html

mkdir -p storage/logs storage/framework/cache storage/framework/data storage/framework/sessions storage/framework/views bootstrap/cache database

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if grep -q '^APP_KEY=$' .env 2>/dev/null; then
  php artisan key:generate --force --no-interaction
fi

until php -r '
$host = getenv("DB_HOST") ?: "mysql";
$port = getenv("DB_PORT") ?: "3306";
$database = getenv("DB_DATABASE") ?: "train_booking_app";
$username = getenv("DB_USERNAME") ?: "train_booking_user";
$password = getenv("DB_PASSWORD") ?: "train_booking_password";

try {
    new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
'; do
  sleep 2
done

php artisan migrate --force --no-interaction --seed

exec php artisan serve --host 0.0.0.0 --port 8000