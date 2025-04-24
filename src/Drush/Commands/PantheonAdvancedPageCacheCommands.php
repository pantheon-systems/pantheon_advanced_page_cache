<?php

namespace Drupal\pantheon_advanced_page_cache\Drush\Commands;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileStorageInterface;
use Drupal\pantheon_advanced_page_cache\ClearEdgePaths;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
final class PantheonAdvancedPageCacheCommands extends DrushCommands {

  use AutowireTrait;

  protected FileStorageInterface $storage;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->storage = $entityTypeManager->getStorage('file');
  }


  #[CLI\Command(name: 'pantheon_advanced_page_cache:flush')]
  #[CLI\Argument(name: 'file', description: 'File ID or a file path.')]
  public function commandName($file) {
    if ($entity = $this->getEntity($file)) {
      ClearEdgePaths::file($entity);
    }
    else {
      $this->output()->writeln("No files found for $file.");
    }
  }

  /**
   * Get the file entity.
   *
   * @param $file
   *   Can be a string or an integer file ID.
   *
   * @return \Drupal\file\FileInterface|FALSE
   */
  public function getEntity($file) {
    if (is_string($file)) {
      $relative = !UrlHelper::isExternal($file);
      $basename = basename($file);
      $fids = $this->storage->getQuery()
        ->condition('filename', $basename)
        ->execute();
      if ($fids) {
        $candidates = $this->storage->loadMultiple($fids);
        if (count($candidates) === 1) {
          return reset($candidates);
        }
        if (count($candidates) > 1) {
          foreach ($candidates as $candidate) {
            if ($candidate->createFileUrl($relative) === $file) {
              return $candidate;
            }
          }
        }
      }
    }
    elseif (is_numeric($file) && ($candidate = $this->storage->load($file))) {
      return $candidate;
    }
    return FALSE;
  }

}
