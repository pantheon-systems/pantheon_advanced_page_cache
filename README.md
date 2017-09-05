# Pantheon Advanced Page Cache

Pantheon Advanced Page Cache module is a bridge between [Drupal cache metadata via the Drupal 8 Cache Backport module](https://www.drupal.org/project/d8cache) and the [Pantheon Global CDN](https://pantheon.io/docs/global-cdn/).

Just by turning on this module your Drupal site will start emitting the HTTP headers necessary to make the Pantheon Global CDN aware of data underlying the response. Then, when the underlying data changes (nodes and taxonomy terms are updated, user permissions changed) this module will clear only the relevant pages from the edge cache.

This module has no configuration settings of its own, just enable it and it will start sending data. To control the data being sent, see [the Drupal 8 Cache Backport module](https://www.drupal.org/project/d8cache).

Also, be sure to follow the `settings.php` setup instructions for [Drupal 8 Cache Backport](http://cgit.drupalcode.org/d8cache/tree/README.txt).

### Feedback and collaboration

For real time discussion of the module find Pantheon developers in our [Power Users Slack channel](https://pantheon.io/docs/power-users/). Bug reports and feature requests should be posted in [the drupal.org issue queue.](https://www.drupal.org/project/issues/pantheon_advanced_page_cache?categories=All) For code changes, please submit pull requests against the [GitHub repository](https://github.com/pantheon-systems/pantheon_advanced_page_cache) rather than posting patches to drupal.org.
