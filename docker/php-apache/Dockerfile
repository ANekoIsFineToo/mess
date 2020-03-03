FROM php:apache

COPY docker/php-apache/wait-for-it.sh /usr/bin/wait-for-it
RUN chmod +x /usr/bin/wait-for-it

RUN apt update
RUN apt install -y curl git software-properties-common libicu-dev libzip-dev unzip

RUN curl -sL https://deb.nodesource.com/setup_12.x | bash -
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list

RUN apt update
RUN apt install -y nodejs yarn

RUN docker-php-ext-install pdo_mysql intl zip

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN rm -rf /etc/apache2/sites-enabled
COPY docker/php-apache/sites/ /etc/apache2/sites-enabled

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /var/www
RUN chown -R www-data: /var/www

WORKDIR /var/www

CMD composer install ; \
    yarn install ; \
    yarn build ; \
    wait-for-it database:3306 -- bin/console doctrine:migrations:migrate --allow-no-migration -n ; \
    apache2-foreground
EXPOSE 80
