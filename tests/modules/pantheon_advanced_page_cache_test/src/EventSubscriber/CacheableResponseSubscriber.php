<?php

namespace Drupal\pantheon_advanced_page_cache_test\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Adds Surrogate-Key header to cacheable master responses.
 */
class CacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * Adds Surrogate-Key header to cacheable master responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
      $response->headers->set('whhhhaaaaat', 'huuuuh');
      print_r('haha!');
      die();
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();

    if ($response instanceof CacheableResponseInterface) {
        $tags = $response->getCacheableMetadata()->getCacheTags();
        print_r($tags);


      if (in_array("config:views.view.frontpage", $tags)) {
          $new_tags = array();
          foreach ($tags as $tag) {
              if (strpos($tag, "taxonomy_term:") === FALSE) {
                  $new_tags[] = $tag;
              }
          }
          $response->getCacheableMetadata()->setCacheTags( $new_tags);
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
