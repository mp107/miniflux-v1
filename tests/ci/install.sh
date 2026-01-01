#!/bin/sh

sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf

if [ "$TRAVIS_PHP_VERSION" = "7.0" -a -n "$(ls -A ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d)" ]; then
    sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/www.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/www.conf
fi

PHPINI=/etc/php/$(php -v | grep -oP "(?<=PHP )[\d]+\.[\d]+\.[\d]+")/fpm/php.ini

echo "cgi.fix_pathinfo = 1" >> "$PHPINI"
echo "opcache.enable = 0" >> "$PHPINI"

~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

sudo a2enmod rewrite actions fcgid alias ssl

sudo cp -f tests/ci/apache_vhost.conf /etc/apache2/sites-available/000-default.conf

echo "define('DEBUG_MODE', false);" | sudo tee -a config.php
echo "define('DEBUG_FILENAME', '/var/www/app/data/debug.log')" | sudo tee -a config.php

sudo mkdir -p /var/www/app
sudo cp -r . /var/www/app
sudo chown -R www-data:www-data /var/www/app

sudo service apache2 restart

cp assets/img/

ls -al
