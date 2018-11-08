#!/bin/bash

# Create a drush alias file so that Behat tests can be executed against Pantheon.
# terminus aliases
# Drush Behat driver fails without this option.
# echo "\$options['strict'] = 0;" >> ~/.drush/pantheon.aliases.drushrc.php

export TERMINUS_SITE=d7papc
export TERMINUS_ENV=679

export BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension" : {"base_url" : "https://'$TERMINUS_ENV'-'$TERMINUS_SITE'.pantheonsite.io/"}, "Drupal\\DrupalExtension" : {"drush" :   {  "alias":  "@pantheon.'$TERMINUS_SITE'.'$TERMINUS_ENV'" }}}}'
./vendor/bin/behat --config=behat/behat-pantheon.yml --tags="deadlock"
