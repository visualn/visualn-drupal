<?php

namespace Drupal\visualn\Plugin;

/**
 * Base class for VisualN Adapter plugins.
 */
abstract class VisualNAdapterBase extends VisualNPluginBase implements VisualNAdapterInterface {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, array $options = []) {
    $vuid = $options['vuid'];

    $adapter_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['adapterId'] = $adapter_js_id;
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['adapters'][$adapter_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference
  }

}
