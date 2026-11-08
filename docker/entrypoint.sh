#!/bin/sh
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY belum diatur. Isi environment variable APP_KEY di dashboard Render." >&2
  exit 1
fi

# Pengaman: jangan pernah menyalakan mode debug di production.
if [ "$APP_ENV" = "production" ] && [ "$APP_DEBUG" = "true" ]; then
  echo "APP_DEBUG=true tidak diizinkan di production. Ubah menjadi false." >&2
  exit 1
fi

php artisan config:clear >/dev/null 2>&1 || true

# Neon bisa butuh beberapa detik untuk bangun; coba lagi sebelum menyerah.
attempt=1
until php artisan migrate --force; do
  if [ "$attempt" -ge 5 ]; then
    echo "Migrasi gagal setelah 5 percobaan. Periksa variabel DB_* dan koneksi ke Neon." >&2
    exit 1
  fi
  echo "Database belum siap (percobaan $attempt/5). Mencoba lagi dalam 5 detik..."
  attempt=$((attempt + 1))
  sleep 5
done

php artisan vanora:bootstrap --no-interaction

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
