<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN Adapter plugins.
 *
 * @see \Drupal\visualn\Plugin\VisualNAdapterInterface
 *
 * @ingroup adapter_plugins
 */
abstract class VisualNAdapterBase extends VisualNPluginBase implements VisualNAdapterInterface {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'drawer_fields' => [],
    ];
  }

}
