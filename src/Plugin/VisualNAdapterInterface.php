<?php

namespace Drupal\visualn\Plugin;

/**
 * Defines an interface for VisualN Adapter plugins.
 */
interface VisualNAdapterInterface extends VisualNPluginInterface {

  /**
   * Get adapter plugin info. Includes data output type etc.
   *
   * @return array $adapter_info
   */
  public function getInfo();

}
