<?php

declare(strict_types=1);

namespace Drupal\Tests\farm_id_tag\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\asset\Entity\Asset;
use Drupal\farm_id_tag\Plugin\Field\FieldType\IdTagItem;

/**
 * Test ID tag field.
 *
 * @group farm
 */
class IdTagTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'asset',
    'farm_field',
    'farm_id_tag',
    'farm_id_tag_test',
    'state_machine',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('asset');
    $this->installConfig(['farm_id_tag', 'farm_id_tag_test']);
  }

  /**
   * Test ID tag fields.
   */
  public function testIdTagField() {

    // Test creating a new asset and saving ID tag information.
    $asset = Asset::create([
      'name' => $this->randomString(),
      'type' => 'test',
      'id_tag' => [
        'id' => '123456',
        'type' => 'other',
        'location' => 'Frame',
      ],
    ]);
    $violations = $asset->validate();
    $this->assertEmpty($violations);
    $asset->save();

    // Confirm that the asset was created with expected ID tag values.
    $assets = Asset::loadMultiple();
    $this->assertCount(1, $assets);
    $id_tag = $assets[1]->get('id_tag')->first();
    $this->assertInstanceOf(IdTagItem::class, $id_tag);
    $this->assertEquals('123456', $id_tag->id);
    $this->assertEquals('other', $id_tag->type);
    $this->assertEquals('Frame', $id_tag->location);

    // Confirm that all sub-fields are optional.
    $asset = Asset::create([
      'name' => $this->randomString(),
      'type' => 'test',
      'id_tag' => [],
    ]);
    $violations = $asset->validate();
    $this->assertEmpty($violations);
    $asset = Asset::create([
      'name' => $this->randomString(),
      'type' => 'test',
      'id_tag' => [
        'id' => '',
        'type' => '',
        'location' => '',
      ],
    ]);
    $violations = $asset->validate();
    $this->assertEmpty($violations);

    // Confirm that an invalid tag type does not pass validation.
    $asset = Asset::create([
      'name' => $this->randomString(),
      'type' => 'test',
      'id_tag' => [
        'type' => 'invalid',
      ],
    ]);
    $violations = $asset->validate();
    $this->assertNotEmpty($violations);
    $this->assertEquals('Invalid ID tag type: invalid', $violations[0]->getMessage());

    // Confirm that multiple ID tags are validated.
    $asset = Asset::create([
      'name' => $this->randomString(),
      'type' => 'test',
      'id_tag' => [
        [
          'type' => 'invalid',
        ],
        [
          'type' => 'invalid',
        ],
      ],
    ]);
    $violations = $asset->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals('Invalid ID tag type: invalid', $violations[1]->getMessage());
  }

}
