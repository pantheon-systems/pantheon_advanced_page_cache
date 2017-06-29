FROM circleci/php:7.1-node-browsers

RUN composer --working-dir=$HOME  require pantheon-systems/terminus "^1"
RUN mkdir -p ~/.terminus/plugins
RUN composer create-project -n -d ~/.terminus/plugins pantheon-systems/terminus-composer-plugin:^1
