<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN Mapper plugins.
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

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $mapper_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['mapperId'] = $mapper_js_id;
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['mappers'][$mapper_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference

    // @todo: this should return the resource of required type (as in annotation output_type)

    return $resource;
  }

}
