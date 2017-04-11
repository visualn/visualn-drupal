<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for VisualN Mapper plugins.
 */
abstract class VisualNMapperBase extends PluginBase implements VisualNMapperInterface {


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, array $options = []) {
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return $this->getPluginId();
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
