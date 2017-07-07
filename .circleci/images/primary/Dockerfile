FROM drupaldocker/php:7.0-cli

RUN git clone https://github.com/pantheon-systems/terminus.git ~/terminus
RUN cd ~/terminus && composer install
RUN ln -s ~/terminus/bin/terminus /usr/local/bin/terminus
RUN mkdir -p ~/.terminus/plugins
RUN composer create-project -n -d ~/.terminus/plugins pantheon-systems/terminus-composer-plugin:^1
# Why doesn't the drupaldocker/php image have drush?
RUN composer global require drush/drush:8.*
