<?php

namespace Drupal\pantheon_advanced_page_cache_test\Plugin\views\cache;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsCache;
use Drupal\views\Plugin\views\cache\Tag;

/**
 * Tag based caching that uses node bundle list tags.
 *
 * @ingroup views_cache_plugins
 */
#[ViewsCache(
  id: 'pantheon_advanced_page_cache_test_bundle_tag',
  title: new TranslatableMarkup('Bundle tag based'),
  help: new TranslatableMarkup('Tag based caching that replaces node_list with node_list:{bundle} for the bundles in the type filter.'),
)]
class BundleTag extends Tag {

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $bundles = $this->displayHandler->getOption('filters')['type']['value'] ?? [];
    if ($bundles) {
      $tags = array_diff($tags, ['node_list']);
      foreach ($bundles as $bundle) {
        $tags[] = 'node_list:' . $bundle;
      }
    }
    return array_values($tags);
  }

}
