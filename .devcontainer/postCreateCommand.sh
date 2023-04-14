#!/bin/bash

composer install

# chmod a+x /workspaces/php-tail/example
sudo rm -rf /var/www/html
sudo ln -s /workspaces/php-tail/example /var/www/html

sudo apache2ctl start

# php /workspaces/php-tail/example/write-test.log.php &
