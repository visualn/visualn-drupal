<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\visualn\ChainPluginJsTrait;

/**
 * Base class for VisualN Builder plugins.
 *
 * @see \Drupal\visualn\Plugin\VisualNBuilderInterface
 *
 * @ingroup builder_plugins
 */
abstract class VisualNBuilderBase extends VisualNPluginBase implements VisualNBuilderInterface {

  // @todo: actually this should be moved to BuilderWithJsBase (see DrawerWithJsBase for example)
  //   and used as base class for DefaultBuilder plugin
  use ChainPluginJsTrait;

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
