#!/usr/bin/env bash

export DEBIAN_FRONTEND=noninteractive

echo "Enter your SheepIT username: "
read username
echo

echo "Enter your SheepIT password: "
read password
echo

apt-get install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get upgrade -y
apt-get install -y curl default-jre apache2 php7.2 php-zip
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

./update.sh

echo "@reboot cd ~/blender-render && ./update.sh && cd /var/www/html && nohup php render.php \"${username}\" \"${password}\" &" > cron
crontab cron
rm cron

cd /var/www/html
nohup php render.php "${username}" "${password}" &
