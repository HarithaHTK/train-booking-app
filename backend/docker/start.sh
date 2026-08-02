#!/bin/sh
set -eu

cd /var/www/html

mkdir -p storage/logs storage/framework/cache storage/framework/data storage/framework/sessions storage/framework/views bootstrap/cache database

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

php artisan migrate --force --no-interaction

exec php artisan serve --host 0.0.0.0 --port 8000