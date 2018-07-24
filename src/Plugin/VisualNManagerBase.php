<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for VisualN Manager plugins.
 */
abstract class VisualNManagerBase extends VisualNPluginBase implements VisualNManagerInterface {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'visualn_style_id' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
      'html_selector' => '',
      // @todo: this was introduced later, for drawer preview page
      'base_drawer_id' => '',
    ];
  }

}
