<?php

namespace Drupal\visualn\Plugin;

/**
 * Base class for VisualN Mapper plugins.
 */
abstract class VisualNMapperBase extends VisualNPluginBase implements VisualNMapperInterface {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    $mapper_js_id = $this->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['mapperId'] = $mapper_js_id;
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['mappers'][$mapper_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference
  }

}
