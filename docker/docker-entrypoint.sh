#!/bin/sh
set -e

# Export APP_ENV from .env file if not already set in OS
source /usr/local/bin/exportvariable APP_ENV /srv/www/app/.env

if [ "$APP_DEBUG" = true ]; then
  sudo sed -i "s/;zend_extension=xdebug.so/zend_extension=xdebug.so/" "$PHP_INI_DIR/conf.d/xdebug.ini"
fi

if [ "$APP_ENV" != 'production' ]; then
  sudo sed -i "s/pinba.enabled=1/pinba.enabled=0/" "$PHP_INI_DIR/conf.d/pinba.ini"
fi

if [ $# -gt 0 ]; then
    exec "$@"
else
    vendor/bin/phing start ; \
    exec php-fpm -F
fi
