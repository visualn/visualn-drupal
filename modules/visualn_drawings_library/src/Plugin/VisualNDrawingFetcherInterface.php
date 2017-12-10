<?php

namespace Drupal\visualn_drawings_library\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

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

}
