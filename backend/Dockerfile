FROM php:8.3-cli-alpine

# Instalar dependencias del sistema y extensiones de PHP necesarias
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql zip pcntl bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Dar permisos a los directorios de almacenamiento y caché de Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Exponer el puerto de la API (8000)
EXPOSE 8000

# Iniciar el servidor de desarrollo de Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
