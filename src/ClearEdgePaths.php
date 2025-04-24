<?php

namespace Drupal\pantheon_advanced_page_cache;

use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;

class ClearEdgePaths {

  /**
   * Clears paths for a file.
   *
   * Clears edge cache by file url. If this is not done, changing file contents
   * does not actually change the file that gets served until the cache times
   * out.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   */
  public static function file(FileInterface $file) {
    // Don't try this if we're not in a Pantheon environment.
    if (!function_exists('pantheon_clear_edge_paths')) {
      return;
    }
    // No matter what, the file's base URL needs to be cleared out.
    $paths_to_clear = [$file->createFileUrl()];

    // If this is an image, we need to clear the edge cache paths for every
    // image style, or those won't work.
    if ((class_exists(ImageStyle::class)) && (strpos((string) $file->getMimeType(), 'image') === 0)) {
      $styles = ImageStyle::loadMultiple();
      foreach ($styles as $style) {
        $file_uri = $file->getFileUri();
        $url = $style->buildUrl($file_uri);

        $paths_to_clear[] = parse_url($url)['path'];
      }
    }

    try {
      pantheon_clear_edge_paths($paths_to_clear);
    }
    catch (\Exception $e) {
      \Drupal::logger('pantheon_advanced_page_cache')
        ->warning('File upload @filename did not successfully clear edge caches. Caches may need to be cleared manually.',
          ['@filename' => $file->getFileName()]);

      \Drupal::logger('pantheon_advanced_page_cache')
        ->error('@error', ['@error' => $e->getMessage()]);

      return;
    }

    \Drupal::logger('pantheon_advanced_page_cache')
      ->notice('File paths cleared in edge cache: @paths.',
        ['@paths' => implode(', ', $paths_to_clear)]);
  }

}
