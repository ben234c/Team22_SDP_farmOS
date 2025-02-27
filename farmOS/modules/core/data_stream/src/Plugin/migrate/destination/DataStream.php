<?php

declare(strict_types=1);

namespace Drupal\data_stream\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;

/**
 * A destination plugin for data streams that can use a "providing_asset" ID.
 *
 * Extend the entity content base plugin to create backwards references
 * to assets that provide data streams. This is necessary because there was
 * no concept of data streams in farmOS 1.x. When creating a data stream from a
 * sensor asset, we need to reference the asset at this time.
 *
 * @deprecated in farm:3.4.0 and is removed from farm:4.0.0. Support for farmOS
 *   v1 migrations was dropped in farmOS 3.x.
 * @see https://www.drupal.org/project/farm/issues/3498067
 * @see https://www.drupal.org/project/farm/issues/3382616
 */
#[MigrateDestination(
  id: 'data_stream',
)]
class DataStream extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'data_stream';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {

    // The parent will create or update the entity.
    $entity = parent::getEntity($row, $old_destination_id_values);

    // Save the entity now so that it has an ID.
    // This means that the EntityValidationRequired feature will not work
    // because we save it now, rather than waiting to see if it is validated.
    $entity->save();

    // Check if a providing_asset ID was provided.
    if ($row->hasDestinationProperty('providing_asset')) {
      $providing_asset = $row->getDestinationProperty('providing_asset');

      /** @var \Drupal\asset\Entity\AssetInterface|null $asset */
      $asset = $this->storage->load($providing_asset);

      // Update the assets data_stream field if the asset was found
      // and the asset type has the field.
      if (!is_null($asset) && $asset->hasField('data_stream')) {
        $asset->get('data_stream')->appendItem($entity->id());
        $asset->save();
      }
    }

    // Return the entity.
    return $entity;
  }

}
