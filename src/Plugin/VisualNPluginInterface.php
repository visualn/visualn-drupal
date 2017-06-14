<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for VisualN drawer, mapper and adapter plugins.
 */
interface VisualNPluginInterface extends PluginInspectionInterface {

  /**
   * Get plugin jsId.
   * Plugin jsId is used in plugin (drawer, mapper, adapter) js script to identify its function object.
   *
   * @return string $js_id
   */
  public function jsId();

}
