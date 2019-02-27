#!/usr/bin/env bash

git pull origin master

cp index.html /var/www/html
cp render.php /var/www/html
cp log.php /var/www/html
