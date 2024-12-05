FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install gRPC PHP extension
RUN pecl install grpc \
    && docker-php-ext-enable grpc

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Install PHP dependencies
RUN composer install

# Expose port for gRPC
EXPOSE 50051

# Start the gRPC server
CMD ["php", "grpc_server.php"]
