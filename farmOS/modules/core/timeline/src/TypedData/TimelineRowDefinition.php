<?php

declare(strict_types=1);

namespace Drupal\farm_timeline\TypedData;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Timeline row definition.
 */
class TimelineRowDefinition extends ComplexDataDefinitionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (empty($this->propertyDefinitions)) {

      $this->propertyDefinitions['id'] = DataDefinition::create('string')
        ->setLabel($this->t('ID'))
        ->setRequired(TRUE);

      $this->propertyDefinitions['label'] = DataDefinition::create('string')
        ->setLabel($this->t('Label'))
        ->setRequired(TRUE);

      $this->propertyDefinitions['link'] = DataDefinition::create('uri')
        ->setLabel($this->t('Link'));

      $this->propertyDefinitions['draggable'] = DataDefinition::create('boolean')
        ->setLabel($this->t('Enable dragging'))
        ->addConstraint('NotNull');

      $this->propertyDefinitions['resizable'] = DataDefinition::create('boolean')
        ->setLabel($this->t('Enable resizing'))
        ->addConstraint('NotNull');

      $this->propertyDefinitions['classes'] = ListDataDefinition::create('string')
        ->setLabel($this->t('Classes'));

      $this->propertyDefinitions['tasks'] = ListDataDefinition::create('farm_timeline_task')
        ->setLabel($this->t('Tasks'));

      $this->propertyDefinitions['expanded'] = DataDefinition::create('boolean')
        ->setLabel($this->t('Expanded'));

      $this->propertyDefinitions['children'] = ListDataDefinition::create('farm_timeline_row')
        ->setLabel($this->t('Children'));

    }
    return $this->propertyDefinitions;
  }

}
