#!/usr/bin/env bash

# Init swap
/vagrant/vagrant/helpers/swap.sh

# Enable nginx vhosts
# Reload is required because on system startup /vagrant directory may not be initialized yet,
# so nginx may be missing our vhosts configs.
sudo service nginx reload
