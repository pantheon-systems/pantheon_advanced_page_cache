<?php

namespace Drupal\pantheon_advanced_page_cache\Plugin\Purge\Purger;

use Drupal\facets\Exception\Exception;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CloudFlare purger.
 *
 * @PurgePurger(
 *   id = "panntheonadvancedpagecache",
 *   label = @Translation("Pantheon Advanced Page Cache"),
 *   description = @Translation("Purger for Pantheon."),
 *   types = {"tag", "url", "path"},
 *   multi_instance = FALSE,
 * )
 */
class PantheonAdvancedPageCachePurger extends PurgerBase implements \Drupal\purge\Plugin\Purge\Purger\PurgerInterface
{

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('pantheon_advanced_page_cache'),
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\CloudFlarePurger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   *
   * @throws \Exception
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * @inheritDoc
   */
  public function invalidate(array $invalidations) {
    // Build an array of these items to send to pantheon for purging.
    $urls = [];
    $tags = [];

    foreach ($invalidations as $invalidation) {
      $invalidationType = $invalidations[0]->getPluginId();
      switch ($invalidationType) {
        case 'tag':
          // These pantheon functions don't exist outside of Pantheon Environments.
          if (function_exists('pantheon_clear_edge_keys')) {
            // Still developing this plugin, uncomment when we remove the event subscriber.
            //$tags[] = $invalidation->getExpression();
          }
          break;
        case 'path':
          // Didn't have any path type invalidations to test, so leaving this commented out.
          // $urls[] = \Drupal::request()->getSchemeAndHttpHost() . $invalidation->getExpression();
          break;
        case 'url':
          // Don't try to invalidate files in the temporary directory.
          if (!strpos($invalidation->getExpression(), 'system/temporary')) {
            $urls[] = $invalidation->getExpression();
          }
          break;
      }
    }

    try {
      foreach ($urls as $url) {
        // From Pantheon, a request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . time());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
        $this->logger->info('purged ' . $url);
      }
      if (function_exists('pantheon_clear_edge_keys')) {
        pantheon_clear_edge_keys($tags);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::SUCCEEDED);
    }
  }

/**
 * @inheritDoc
 */
  public function hasRuntimeMeasurement() {
    return true;
  }
}
