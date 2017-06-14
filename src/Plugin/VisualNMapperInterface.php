<?php

namespace Drupal\visualn\Plugin;

/**
 * Defines an interface for VisualN Mapper plugins.
 */
interface VisualNMapperInterface extends VisualNPluginInterface {


  /**
   * Attach mapper libraries to render array.
   *
   * @param array $build
   *
   * @param array $options
   */
  public function prepareBuild(array &$build, array $options = []);

  /**
   * Get mapper plugin info. Includes data input and output types etc.
   *
   * @return array $mapper_info
   */
  public function getInfo();

}
