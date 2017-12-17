<?php

namespace Drupal\visualn_data_sources\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for VisualN Data Provider plugins.
 */
//interface VisualNDataProviderInterface extends PluginInspectionInterface {
interface VisualNDataProviderInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Get Resource object corresponding to the current provider.
   *
   * @return \Drupal\visualn\Plugin\VisualNResourceInterface
   */
  public function getResource();

}
