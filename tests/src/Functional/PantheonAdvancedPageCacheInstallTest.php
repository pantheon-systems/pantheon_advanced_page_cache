<?php

namespace Drupal\Tests\pantheon_advanced_page_cache\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the installation and incompatibility handling.
 *
 * @group pantheon_advanced_page_cache
 */
class PantheonAdvancedPageCacheInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that big_pipe module is uninstalled when this module is installed.
   */
  public function testBigPipeUninstallationOnInstall() {
    // Enable big_pipe module first.
    $this->container->get('module_installer')->install(['big_pipe']);
    $this->assertTrue($this->container->get('module_handler')->moduleExists('big_pipe'));

    // Install pantheon_advanced_page_cache module.
    $this->container->get('module_installer')->install(['pantheon_advanced_page_cache']);

    // Verify that big_pipe is no longer enabled.
    $this->assertFalse($this->container->get('module_handler')->moduleExists('big_pipe'));
  }

  /**
   * Tests that the status report page shows an error if big_pipe is enabled.
   */
  public function testStatusReportShowsBigPipeError() {
    // Enable big_pipe module.
    $this->container->get('module_installer')->install(['big_pipe']);
    $this->assertTrue($this->container->get('module_handler')->moduleExists('big_pipe'));

    // Install pantheon_advanced_page_cache module.
    $this->container->get('module_installer')->install(['pantheon_advanced_page_cache']);

    // Login as admin user.
    $this->drupalLogin($this->createUser(['administer site configuration']));

    // Visit the status report page.
    $this->drupalGet('/admin/reports/status');

    // Check that the error about Big Pipe incompatibility is present.
    $this->assertSession()->pageTextContains('Pantheon Advanced Page Cache: Big Pipe incompatibility');
    $this->assertSession()->pageTextContains('Big Pipe module is enabled');
    $this->assertSession()->pageTextContains('incompatible');
    $this->assertSession()->pageTextContains('504 Gateway Timeout');
    $this->assertSession()->pageTextContains('Disable or uninstall Big Pipe immediately');
  }

}
