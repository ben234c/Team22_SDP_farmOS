<?php

declare(strict_types=1);

namespace Drupal\farm_ui_views\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\farm_location\AssetLocationInterface;

/**
 * Checks access for displaying Views of asset children.
 */
class FarmAssetChildrenViewsAccessCheck implements AccessInterface {

  /**
   * The asset storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $assetStorage;

  /**
   * The asset location service.
   *
   * @var \Drupal\farm_location\AssetLocationInterface
   */
  protected $assetLocation;

  /**
   * FarmAssetChildrenViewsAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\farm_location\AssetLocationInterface $asset_location
   *   The asset location service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AssetLocationInterface $asset_location) {
    $this->assetStorage = $entity_type_manager->getStorage('asset');
    $this->assetLocation = $asset_location;
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function access(RouteMatchInterface $route_match) {

    // If there is no "asset" parameter, bail.
    $asset_id = $route_match->getParameter('asset');
    if (empty($asset_id)) {
      return AccessResult::allowed();
    }

    // If the asset is a location, deny access.
    /** @var \Drupal\asset\Entity\AssetInterface $asset */
    $asset = $this->assetStorage->load($asset_id);
    if ($this->assetLocation->isLocation($asset)) {
      return AccessResult::forbidden();
    }

    // Run a count query to see if there are any assets that reference this
    // asset as a parent.
    $count = $this->assetStorage->getAggregateQuery()
      ->accessCheck(TRUE)
      ->condition('parent.entity.id', $asset_id)
      ->count()
      ->execute();

    // Determine access based on the child count.
    $access = AccessResult::allowedIf($count > 0);

    // Invalidate the access result when assets are changed.
    $access->addCacheTags(["asset_list"]);
    return $access;
  }

}
