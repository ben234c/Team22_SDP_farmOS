<?php

declare(strict_types=1);

namespace Drupal\asset\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the asset type entity.
 *
 * @ConfigEntityType(
 *   id = "asset_type",
 *   label = @Translation("Asset type"),
 *   label_collection = @Translation("Asset types"),
 *   label_singular = @Translation("Asset type"),
 *   label_plural = @Translation("Asset types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count asset type",
 *     plural = "@count asset types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\asset\AssetTypeListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\asset\Form\AssetTypeForm",
 *       "edit" = "Drupal\asset\Form\AssetTypeForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer asset types",
 *   config_prefix = "type",
 *   bundle_of = "asset",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/asset-type/{asset_type}",
 *     "add-form" = "/admin/structure/asset-type/add",
 *     "edit-form" = "/admin/structure/asset-type/{asset_type}/edit",
 *     "delete-form" = "/admin/structure/asset-type/{asset_type}/delete",
 *     "collection" = "/admin/structure/asset-type"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "workflow",
 *     "new_revision",
 *   }
 * )
 */
class AssetType extends ConfigEntityBundleBase implements AssetTypeInterface {

  /**
   * The asset type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The asset type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this asset type.
   *
   * @var string
   */
  protected $description;

  /**
   * The asset type workflow ID.
   *
   * @var string
   */
  protected $workflow;

  /**
   * Default value of the 'Create new revision' checkbox of the asset type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($workflow_id) {
    $this->workflow = $workflow_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // The asset type must depend on the module that provides the workflow.
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflow = $workflow_manager->createInstance($this->getWorkflowId());
    $this->calculatePluginDependencies($workflow);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    return $this->set('new_revision', $new_revision);
  }

}
