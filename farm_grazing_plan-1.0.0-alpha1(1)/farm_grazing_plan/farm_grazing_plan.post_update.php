<?php

/**
 * @file
 * Post update hooks for mymodule.
 */

declare(strict_types=1);

/**
 * Add "My new field" to logs.
 */

 /**
  * Change name each time it is run. drush will not execute functions that have previously been executed through "drush updb".
  * https://drupal.stackexchange.com/questions/315086/hook-post-update-not-found
  */

function farm_grazing_plan_post_update_add_planned2(&$planned) {
  $options = [
    'type' => 'integer',
    'label' => t('planned'),
    'description' => t('States if a grazing event was planned'),
    'weight' => [
      'form' => 10,
      'view' => 10,
    ],
    'required' => TRUE,
  ];
  $field_definition = \Drupal::service('farm_field.factory')->bundleFieldDefinition($options);
  \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition('planned', 'plan_record', 'farm_grazing_plan', $field_definition);
  \Drupal::service('entity_field.manager')->rebuildBundleFieldMap();
}

