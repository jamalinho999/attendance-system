FROM dunglas/frankenphp:php8.4-bookworm

RUN install-php-extensions pdo_mysql mysqli

COPY . /app

ENV SERVER_NAME=:80