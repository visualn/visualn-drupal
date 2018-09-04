<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN Mapper plugins.
 *
 * @see \Drupal\visualn\Plugin\VisualNMapperInterface
 *
 * @ingroup mapper_plugins
 */
abstract class VisualNMapperBase extends VisualNPluginBase implements VisualNMapperInterface {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'data_keys_structure' => [],
      'drawer_fields' => [],
    ];
  }

}
