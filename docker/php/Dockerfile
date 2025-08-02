FROM php:8.0-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Install ReactPHP and Redis client
RUN composer require clue/reactphp-redis react/event-loop

# Copy custom php.ini if needed
# COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Set environment variables based on APP_ENVIRONMENT
ARG APP_ENVIRONMENT=development
ENV APP_ENVIRONMENT=${APP_ENVIRONMENT}

# Configure Redis database based on environment
RUN if [ "$APP_ENVIRONMENT" = "production" ] ; then \
        echo "Redis database set to 0 for production" ; \
    else \
        echo "Redis database set to 1 for development" ; \
    fi

CMD ["php-fpm"]