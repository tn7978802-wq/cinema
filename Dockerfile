FROM php:8.2-cli

# Cài system packages + Node
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip \
    nodejs npm \
    && docker-php-ext-install pdo pdo_mysql zip

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working dir
WORKDIR /app

# Copy code
COPY . .

# Install backend
RUN composer install --no-dev --optimize-autoloader

# Install frontend
RUN npm install && npm run build

# Expose port
EXPOSE 10000

# Start app
CMD ["sh", "-c", "php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && exec php artisan serve --host=0.0.0.0 --port=10000"]
