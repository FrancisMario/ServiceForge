FROM php:8.1-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libcurl4-openssl-dev \
    protobuf-compiler \
    && docker-php-ext-install curl \
    && pecl install grpc && docker-php-ext-enable grpc

# Set working directory
WORKDIR /var/www

# Copy composer.lock and composer.json
COPY composer.lock composer.json /var/www/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application
COPY . /var/www

# Autoload dependencies
RUN composer dump-autoload --optimize

# Expose gRPC port
EXPOSE 50051

# Start gRPC server
CMD ["php", "grpc_server.php"]
