<?php

declare(strict_types=1);

namespace Drupal\Tests\farm_ui_views\Functional;

use Drupal\Tests\farm_test\Functional\FarmBrowserTestBase;
use Drupal\asset\Entity\Asset;
use Drupal\log\Entity\Log;

/**
 * Tests the farm_ui_views Views.
 *
 * @group farm
 */
class FarmUiViewsTest extends FarmBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'farm_activity',
    'farm_equipment',
    'farm_water',
    'farm_ui_views',
    'farm_ui_views_test',
  ];

  /**
   * Test Views provided by the farm_ui_views module.
   */
  public function testFarmUiViews() {

    // Create and login a user with permission to view assets.
    $user = $this->createUser(['view any asset', 'view any log']);
    $this->drupalLogin($user);

    // Create two assets of different types.
    $equipment = Asset::create([
      'name' => 'Equipment asset',
      'type' => 'equipment',
      'status' => 'active',
    ]);
    $equipment->save();
    $water = Asset::create([
      'name' => 'Water asset',
      'type' => 'water',
      'status' => 'active',
    ]);
    $water->save();

    // Check that both assets are visible in /assets.
    $this->drupalGet('/assets');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($equipment->label());
    $this->assertSession()->pageTextContains($water->label());

    // Check that only water assets are visible in /assets/water.
    $this->drupalGet('/assets/water');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains($equipment->label());
    $this->assertSession()->pageTextContains($water->label());

    // Check that /assets/equipment includes equipment-specific columns.
    $this->drupalGet('/assets/equipment');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Manufacturer');
    $this->assertSession()->pageTextContains('Model');
    $this->assertSession()->pageTextContains('Serial number');

    // Create two activity logs with different test_string values.
    $activity1 = Log::create([
      'name' => 'Foo activity',
      'type' => 'activity',
      'status' => 'done',
      'test_string' => 'foo',
    ]);
    $activity1->save();
    $activity2 = Log::create([
      'name' => 'Baz activity',
      'type' => 'activity',
      'status' => 'done',
      'test_string' => 'bar',
    ]);
    $activity2->save();

    // Check that /logs and /logs/activity include the "Test string" column.
    $this->drupalGet('/logs');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test string');
    $this->drupalGet('/logs/activity');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test string');

    // Check that both activity logs are present.
    $this->assertSession()->pageTextContains('Foo activity');
    $this->assertSession()->pageTextContains('Baz activity');

    // Check that filtering by "Test string" works.
    $this->drupalGet('/logs/activity', ['query' => ['test_string' => 'foo']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Foo activity');
    $this->assertSession()->pageTextNotContains('Baz activity');
  }

}
