<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for VisualN Mapper plugins.
 */
interface VisualNMapperInterface extends PluginInspectionInterface {


  /**
   * Attach mapper libraries to render array.
   *
   * @param array $build
   *
   * @param array $options
   */
  public function prepareBuild(array &$build, array $options = []);

  /**
   * Get mapper jsId.
   *
   * @return string $js_id
   */
  public function jsId();

  /**
   * Get mapper plugin info. Includes data input and output types etc.
   *
   * @return array $mapper_info
   */
  public function getInfo();

}
