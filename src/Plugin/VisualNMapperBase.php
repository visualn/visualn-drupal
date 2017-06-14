<?php

namespace Drupal\visualn\Plugin;

/**
 * Base class for VisualN Mapper plugins.
 */
abstract class VisualNMapperBase extends VisualNPluginBase implements VisualNMapperInterface {


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, array $options = []) {
  }

  /**
   * @inheritdoc
   */
  public function getInfo() {
    return [
      'input' => '',
      'output' => '',
    ];
  }

}
