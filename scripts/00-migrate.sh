#!/bin/sh

echo "====================================="
echo "Running automatic database migrations"
echo "====================================="

php artisan migrate --force

echo "====================================="
echo "Migrations completed successfully!"
echo "====================================="
