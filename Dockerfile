FROM php:8.3-apache

# 1. Instalar dependencias del sistema necesarias para Excel (Zip, GD)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    libpng-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd \
    && a2enmod rewrite

# 2. Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Configurar directorio de trabajo
WORKDIR /var/www/html