<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for VisualN Resource Format plugins.
 */
interface VisualNResourceFormatInterface extends PluginInspectionInterface {


  /**
   * Create resource object based on raw resource input values/parameters.
   */
  public function buildResource(array $raw_input);

}
