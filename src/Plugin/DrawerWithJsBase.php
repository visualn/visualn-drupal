<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\ResourceInterface;
use Drupal\visualn\ChainPluginJsTrait;

/**
 * Base class for VisualN Drawer plugins using js.
 *
 * @see \Drupal\visualn\Plugin\DrawerWithJsInterface
 *
 * @ingroup drawer_plugins
 */
abstract class DrawerWithJsBase extends VisualNDrawerBase implements DrawerWithJsInterface {

  use ChainPluginJsTrait;

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $drawer_config =  $this->getConfiguration();
    $this->prepareJsConfig($drawer_config);
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['config'] = $drawer_config;

    // defaults to plugin id if not overriden in drawer plugin class
    $drawer_js_id = $this->jsId();
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['drawerId'] = $drawer_js_id;
    // @todo: this setting is just for reference
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['drawings'][$drawer_js_id][$vuid] = $vuid;

    return $resource;
  }

}
