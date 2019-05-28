#!/usr/bin/env bash

provisioningStartTime=`date +%s`

echo ""
echo "Provisioning started"

/vagrant/vagrant/helpers/swap.sh

sudo bash -c 'echo "Europe/Moscow" > /etc/timezone'
sudo dpkg-reconfigure -f noninteractive tzdata

cd /vagrant

sudo apt-get update
sudo apt-get dist-upgrade -y

sudo apt-get install mc -y
sudo apt-get install unzip -y
sudo apt-get install git-core -y
sudo apt-get install memcached -y
sudo apt-get install redis-server -y
sudo apt-get install curl -y
sudo apt-get install software-properties-common -y
sudo apt-get install apt-transport-https lsb-release ca-certificates -y

#nginx
sudo apt-get install nginx -y
sudo rm /etc/nginx/sites-enabled/default
sudo rm /etc/nginx/sites-available/default
sudo ln -s /vagrant/vagrant/configs/host.conf /etc/nginx/sites-enabled/vhosts.conf

#delete apache
sudo apt-get --purge remove apache2
sudo apt-get remove apache2-common

#yarn repo
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list

#php
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt-get update
sudo apt-get install -y php7.2 php7.2-cli php7.2-memcache php7.2-common php7.2-json php7.2-fpm php7.2-gd php7.2-mbstring php7.2-xml php7.2-curl php7.2-zip

sudo ln -s /vagrant/vagrant/configs/php.ini /etc/php/7.2/fpm/conf.d/00-php.ini
sudo ln -s /vagrant/vagrant/configs/php.ini /etc/php/7.2/cli/conf.d/00-php.ini

# php-cs-fixer is used to fix PHP code styles in our sources before commit.
wget http://get.sensiolabs.org/php-cs-fixer.phar -O php-cs-fixer
sudo chmod a+x php-cs-fixer
sudo mv php-cs-fixer /usr/local/bin/php-cs-fixer

# As of June 9, 2016 there's a bug on sensiolabs.org:
# instead of installing stable release of php-cs-fixer
# it installs 2.0-DEV version.
# Use php-cs-fixer selfupdate to install stable release.
# https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/1925#issuecomment-224208657
php-cs-fixer selfupdate

#phpunit
wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
sudo mv phpunit.phar /usr/local/bin/phpunit

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

#npm
curl -sL https://deb.nodesource.com/setup_9.x | sudo bash -
sudo apt-get install nodejs -y
sudo apt-get install npm -y

#yarn
sudo apt-get install yarn -y

#redis
#apt-get install build-essential tcl
#wget http://download.redis.io/releases/redis-stable.tar.gz
#tar xvzf redis-stable.tar.gz
#cd redis-stable
#make && make install

# Export some paths to $PATH env variable.
echo 'export PATH="$PATH:/usr/local/bin"' >> ~/.bashrc
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.bashrc
source ~/.bashrc

## Finish
# Cleanup unused packages.
sudo apt-get autoremove -y


# Restart services.
sudo service nginx restart
sudo service php7.2-fpm restart

# Ok, we're ready.
provisioningEndTime=`date +%s`
provisioningRunTime=$((provisioningEndTime-provisioningStartTime))
provisioningMinutes=$((provisioningRunTime/60))
echo ""
echo "Provision has been done in $provisioningMinutes minutes"