<?php

namespace Drupal\visualn_data_sources\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for VisualN Data Generator plugins.
 */
interface VisualNDataGeneratorInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Generate data array
   *
   * @return array
   */
  public function generateData();

}
