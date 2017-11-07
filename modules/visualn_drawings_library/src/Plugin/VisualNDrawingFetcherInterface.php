<?php

namespace Drupal\visualn_drawings_library\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\visualn_drawings_library\Entity\VisualNDrawing;

/**
 * Defines an interface for VisualN Drawing Fetcher plugins.
 */
// @todo: there may be configurable fetcher plugins and not configurable, which don't need PluginFormInterface
//    as for DrawerModifier plugins
interface VisualNDrawingFetcherInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Fetch drawing markup.
   */
  public function fetchDrawing();

  /**
   * Set reference to the drawing entity.
   */
  public function setDrawingEntity(VisualNDrawing $entity);

  /**
   * Set entity type and bundle.
   *
   * May be needed to get list of fields of the entity type.
   * Entity itself can't be used since field widget doesn't has a reference to it.
   */
  public function setEntityInfo($entity_type, $bundle);

}
