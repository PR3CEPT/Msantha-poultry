#!/bin/bash
set -e

# Default to 10000 for Render if PORT is not set
PORT="${PORT:-10000}"

echo "Starting Msantha Poultry Management System on port ${PORT}..."

# Update Apache port dynamically for Render
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/*.conf

# Ensure storage directories exist and are writable
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/cache/sessions \
         /var/www/html/storage/cache/throttling \
         /var/www/html/storage/backups \
         /var/www/html/storage/imports

chown -R www-data:www-data /var/www/html/storage /var/www/html/public
chmod -R 775 /var/www/html/storage

exec "$@"
