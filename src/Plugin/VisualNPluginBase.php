<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for VisualN plugins.
 */
abstract class VisualNPluginBase extends PluginBase implements VisualNPluginInterface {

  /**
   * @inheritdoc
   */
  public function jsId() {
    return $this->getPluginId();
  }

}
