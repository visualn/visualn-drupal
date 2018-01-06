<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\ResourceInterface;

/**
 * Defines an interface for VisualN Chain plugins.
 */
interface ChainPluginInterface {

  /**
   * Prepare build array.
   *
   * @param array $build
   *
   * @param string $vuid
   *
   * @param \Drupal\visualn\Plugin\ResourceInterface $resource
   *
   * @return \Drupal\visualn\Plugin\ResourceInterface $resource
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource);

}
