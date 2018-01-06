<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Defines an interface for VisualN drawer, mapper and adapter plugins.
 */
interface VisualNPluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface, ChainPluginInterface {

  /**
   * Attach plugin libraries and settings to render array.
   *
   * @param array $build
   *
   * @param string $vuid
   *
   * @param \Drupal\visualn\ResourceInterface $resource
   *
   * @return \Drupal\visualn\ResourceInterface $resource
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource);

  /**
   * Get plugin jsId.
   * Plugin jsId is used in plugin (drawer, mapper, adapter) js script to identify its function object.
   *
   * @return string $js_id
   */
  public function jsId();

}
