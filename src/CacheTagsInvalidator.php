<?php

namespace Drupal\pantheon_advanced_page_cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Cache tags invalidator implementation that invalidates the Pantheon edge.
 */
class CacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * Constructs a CacheTagsInvalidator.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $config_factory
   *   The config factory. Optional for backwards compatibility.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config_factory = NULL) {
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $do_not_run_urls = [
      // There is a weird interaction with metatag that clear local_tasks key
      // and therefore lots of cached pages.
      '/core/install.php',
      ];
    $current_request = $this->requestStack->getCurrentRequest();
    if ($current_request && in_array($current_request->getBaseUrl(), $do_not_run_urls)) {
      return;
    }
    if (function_exists('pantheon_clear_edge_keys')) {
      pantheon_clear_edge_keys($this->mapTagsForEdge($tags));
    }
  }

  /**
   * Maps Drupal cache tags to CDN edge key names.
   *
   * When override_list_tags is enabled, CacheableResponseSubscriber renames
   * _list to _emit_list in Surrogate-Key headers on outgoing responses.
   * Purge requests must send BOTH the original and renamed variants so that:
   * - The renamed variant purges current CDN entries (stored under _emit_list).
   * - The original variant purges any entries cached before override_list_tags
   *   was enabled (or during brief config transitions).
   * CDN ignores purge requests for keys it doesn't have, so sending both
   * is safe and handles all transition states.
   *
   * @param array $tags
   *   The Drupal-internal cache tag names.
   *
   * @return array
   *   The original tags plus renamed variants for any containing '_list'.
   */
  protected function mapTagsForEdge(array $tags): array {
    if (!$this->shouldOverrideListTags()) {
      return $tags;
    }
    $mapped = $tags;
    foreach ($tags as $tag) {
      // Must match CacheableResponseSubscriber::onRespond() transformation.
      $renamed = str_replace('_list', '_emit_list', $tag);
      if ($renamed !== $tag) {
        $mapped[] = $renamed;
      }
    }
    return $mapped;
  }

  /**
   * Returns whether entity_list tags should be overridden.
   *
   * Mirrors the logic in CacheableResponseSubscriber::getOverrideListTagsSetting()
   * so the same transformation is applied consistently in both directions.
   *
   * @return bool
   *   TRUE if list tags are renamed to emit_list in Surrogate-Key headers.
   */
  protected function shouldOverrideListTags(): bool {
    if ($this->configFactory === NULL) {
      return FALSE;
    }
    $config = $this->configFactory->get('pantheon_advanced_page_cache.settings');
    // Only return FALSE if this config value is really set to false.
    // A null value should return TRUE for backwards compatibility.
    if ($config->get('override_list_tags') === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

}
