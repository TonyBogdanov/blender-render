#!/usr/bin/env bash

export DEBIAN_FRONTEND=noninteractive

echo "Enter your SheepIT username: "
read username
echo

echo "Enter your SheepIT password: "
read password
echo

add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get upgrade -y
apt-get install software-properties-common curl default-jre apache2 php7.2 php-zip -y
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

./update.sh

echo "@reboot cd /var/www/html && ~/blender-render/update.sh && nohup php render.php \"${username}\" \"${password}\" &" > cron
crontab cron
rm cron
