<?php

namespace Drupal\visualn\Plugin;

/**
 * Defines an interface for VisualN Mapper plugins.
 */
interface VisualNMapperInterface extends VisualNPluginInterface {

  /**
   * Get mapper plugin info. Includes data input and output types etc.
   *
   * @return array $mapper_info
   */
  public function getInfo();

}
