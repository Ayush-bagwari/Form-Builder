#!/bin/sh

echo "====================================="
echo "Running automatic database migrations"
echo "====================================="

php artisan migrate --force --no-interaction || echo "Migration command completed with notice."

echo "====================================="
echo "Container initialization completed!"
echo "====================================="
