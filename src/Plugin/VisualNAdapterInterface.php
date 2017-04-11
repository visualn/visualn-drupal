<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for VisualN Adapter plugins.
 */
interface VisualNAdapterInterface extends PluginInspectionInterface {


  /**
   * Attach adapter libraries to render array.
   *
   * @param array $build
   *
   * @param array $options
   */
  public function prepareBuild(array &$build, array $options = []);

  /**
   * Get adapter jsId.
   *
   * @return string $js_id
   */
  public function jsId();

  /**
   * Get adapter plugin info. Includes data output type etc.
   *
   * @return array $adapter_info
   */
  public function getInfo();

}
