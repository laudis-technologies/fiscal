FROM php:8.0-cli
RUN apt-get update \
    && apt-get install -y \
        zip \
        unzip \
        git \
        wget \
    && docker-php-ext-install -j$(nproc)  pdo_mysql \
    && pecl install ds \
    && docker-php-ext-enable ds \
    && wget https://codeclimate.com/downloads/test-reporter/test-reporter-latest-linux-amd64 \
    && mv test-reporter-latest-linux-amd64 /usr/bin/cc-test-reporter  \
    && chmod +x /usr/bin/cc-test-reporter

ARG WITH_XDEBUG=false

RUN if [ $WITH_XDEBUG = "true" ] ; then \
        pecl install xdebug-2.9.3 && \
        docker-php-ext-enable xdebug; \
fi;
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /opt/project

COPY composer.json composer.lock ./
RUN composer install

COPY phpunit.xml.dist phpunit.coverage.xml.dist psalm.xml .php-cs-fixer.php ./
COPY src/ src/
COPY tests/ tests/
COPY .git/ .git/





