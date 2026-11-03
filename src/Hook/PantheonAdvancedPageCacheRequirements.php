<?php

namespace Drupal\pantheon_advanced_page_cache\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the Pantheon Advanced Page Cache module.
 */
class PantheonAdvancedPageCacheRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    if ($this->moduleHandler->moduleExists('big_pipe')) {
      $requirements['pantheon_advanced_page_cache_big_pipe'] = [
        'title' => $this->t('Pantheon Advanced Page Cache: Big Pipe incompatibility'),
        'value' => $this->t('Big Pipe module is enabled'),
        'severity' => RequirementSeverity::Error,
        'description' => $this->t('Big Pipe provides no benefit on Pantheon and the module is <strong>incompatible</strong> with Pantheon Advanced Page Cache. It can cause fatal errors (such as 504 Gateway Timeout) and site breakage. <strong>Disable or uninstall Big Pipe immediately.</strong>'),
      ];
    }
    return $requirements;
  }

}
