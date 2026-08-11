<?php

declare(strict_types=1);

namespace Drupal\Tests\pantheon_advanced_page_cache\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\pantheon_advanced_page_cache\CacheTagsInvalidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests edge key mapping in CacheTagsInvalidator.
 *
 * @coversDefaultClass \Drupal\pantheon_advanced_page_cache\CacheTagsInvalidator
 * @group pantheon_advanced_page_cache
 */
class CacheTagsInvalidatorTest extends TestCase {

  /**
   * The purge keys captured by the mock pantheon_clear_edge_keys().
   *
   * @var array|null
   */
  public static ?array $capturedEdgeKeys = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    self::$capturedEdgeKeys = NULL;
    require_once __DIR__ . '/PantheonEdgeFunctionsMock.php';
  }

  /**
   * Tests that list tags produce both original AND renamed variants for purge.
   *
   * @covers ::invalidateTags
   * @covers ::mapTagsForEdge
   */
  public function testOverrideListTagsEnabledSendsBothVariants(): void {
    $invalidator = $this->createInvalidator(TRUE);

    $invalidator->invalidateTags([
      'node:5',
      'node_list',
      'search_api_list:primary',
      'config:views.view.search',
    ]);

    $this->assertNotNull(self::$capturedEdgeKeys);
    // Original tags must be present (handles config transition).
    $this->assertContains('node:5', self::$capturedEdgeKeys);
    $this->assertContains('node_list', self::$capturedEdgeKeys);
    $this->assertContains('search_api_list:primary', self::$capturedEdgeKeys);
    $this->assertContains('config:views.view.search', self::$capturedEdgeKeys);

    // Renamed variants must also be present (matches CDN-stored keys).
    $this->assertContains('node_emit_list', self::$capturedEdgeKeys);
    $this->assertContains('search_api_emit_list:primary', self::$capturedEdgeKeys);
  }

  /**
   * Tests that tags are passed through unchanged when override is disabled.
   *
   * @covers ::invalidateTags
   * @covers ::mapTagsForEdge
   */
  public function testOverrideListTagsDisabledPassesThrough(): void {
    $invalidator = $this->createInvalidator(FALSE);

    $invalidator->invalidateTags([
      'node:5',
      'node_list',
      'search_api_list:primary',
    ]);

    $this->assertNotNull(self::$capturedEdgeKeys);
    $this->assertEquals([
      'node:5',
      'node_list',
      'search_api_list:primary',
    ], self::$capturedEdgeKeys);
  }

  /**
   * Tests backward compat: no config factory injected means no rename.
   *
   * @covers ::invalidateTags
   * @covers ::shouldOverrideListTags
   */
  public function testNullConfigFactoryPassesThrough(): void {
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn(new Request());
    $invalidator = new CacheTagsInvalidator($requestStack);

    $invalidator->invalidateTags(['node_list', 'node:5']);

    $this->assertNotNull(self::$capturedEdgeKeys);
    $this->assertContains('node_list', self::$capturedEdgeKeys);
    $this->assertNotContains('node_emit_list', self::$capturedEdgeKeys);
  }

  /**
   * Tests null override_list_tags config (1.x upgrade, treated as TRUE).
   *
   * @covers ::shouldOverrideListTags
   */
  public function testNullOverrideListTagsTreatedAsTrue(): void {
    $invalidator = $this->createInvalidator(NULL);

    $invalidator->invalidateTags(['node_list']);

    $this->assertNotNull(self::$capturedEdgeKeys);
    $this->assertContains('node_emit_list', self::$capturedEdgeKeys);
    // Original also present (belt-and-suspenders).
    $this->assertContains('node_list', self::$capturedEdgeKeys);
  }

  /**
   * Tests that tags without _list are not duplicated.
   *
   * @covers ::mapTagsForEdge
   */
  public function testNonListTagsNotDuplicated(): void {
    $invalidator = $this->createInvalidator(TRUE);

    $invalidator->invalidateTags([
      'node:5',
      'user:1',
      'config:system.site',
      'http_response',
    ]);

    $this->assertEquals([
      'node:5',
      'user:1',
      'config:system.site',
      'http_response',
    ], self::$capturedEdgeKeys);
  }

  /**
   * Creates a CacheTagsInvalidator with the given override_list_tags setting.
   *
   * @param bool|null $override_list_tags
   *   The override_list_tags config value.
   *
   * @return \Drupal\pantheon_advanced_page_cache\CacheTagsInvalidator
   *   The configured invalidator.
   */
  protected function createInvalidator(bool|null $override_list_tags): CacheTagsInvalidator {
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn(new Request());

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('override_list_tags')
      ->willReturn($override_list_tags);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('pantheon_advanced_page_cache.settings')
      ->willReturn($config);

    return new CacheTagsInvalidator($requestStack, $configFactory);
  }

}
