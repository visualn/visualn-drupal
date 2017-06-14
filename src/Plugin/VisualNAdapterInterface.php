<?php

namespace Drupal\visualn\Plugin;

/**
 * Defines an interface for VisualN Adapter plugins.
 */
interface VisualNAdapterInterface extends VisualNPluginInterface {


  /**
   * Attach adapter libraries to render array.
   *
   * @param array $build
   *
   * @param array $options
   */
  public function prepareBuild(array &$build, array $options = []);

  /**
   * Get adapter plugin info. Includes data output type etc.
   *
   * @return array $adapter_info
   */
  public function getInfo();

}
