# Image de l'API Symfony 5.4 (PHP 8.2) du projet Quai Antique.
FROM php:8.2-cli

# Extensions PHP requises : pdo_mysql (MariaDB) et mongodb (compteur de réservations).
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Composer est copié depuis l'image officielle.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/api

# Les dépendances sont installées avant le code pour profiter du cache Docker.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p var && chmod -R 777 var

EXPOSE 8000

# Serveur HTTP intégré de PHP : suffisant pour un environnement de développement et de démonstration.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
