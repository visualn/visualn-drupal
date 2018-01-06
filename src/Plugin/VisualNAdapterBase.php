<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN Adapter plugins.
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

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $adapter_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['adapterId'] = $adapter_js_id;
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['adapters'][$adapter_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference

    // @todo: this should return the resource of required type (as in annotation output_type)

    return $resource;
  }

}
