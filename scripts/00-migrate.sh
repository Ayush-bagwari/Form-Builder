#!/bin/sh

echo "================================================="
echo "🚀 Form Builder Container Startup Initialization"
echo "================================================="

# 1. Check & Generate APP_KEY if not set in .env
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "🔑 APP_KEY is not set. Generating application encryption key..."
    php artisan key:generate --force --no-interaction
fi

# 2. Ensure public/storage symlink exists
if [ ! -L /var/www/html/public/storage ]; then
    echo "🔗 Creating storage symlink (public/storage)..."
    php artisan storage:link || true
fi

# 3. Heal permissions for storage and bootstrap/cache
echo "🔒 Ensuring correct folder permissions for www-data..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Wait for Database to become ready (avoids startup race condition)
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "⏳ Waiting for MySQL ($DB_HOST:$DB_PORT) to accept connections..."
    MAX_TRIES=30
    COUNT=0

    until php -r "new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: 3306), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
        COUNT=$((COUNT + 1))
        if [ $COUNT -ge $MAX_TRIES ]; then
            echo "⚠️ Warning: Database connection timed out after $MAX_TRIES retries. Proceeding with migration attempt..."
            break
        fi
        echo "   Database not ready yet. Retrying in 2s... ($COUNT/$MAX_TRIES)"
        sleep 2
    done

    echo "✅ Database connection established!"
fi

# 5. Run Database Migrations
echo "📦 Running database migrations..."
php artisan migrate --force --no-interaction

# 6. Optimize route/view/event caching if in production
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Caching Laravel configuration and routes for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "================================================="
echo "🎉 Container initialization completed successfully!"
echo "================================================="

