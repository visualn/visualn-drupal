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
  }

  /**
   * @inheritdoc
   */
  public function getInfo() {
    return [
      // @todo: should it be d3.js or just a generic js object?
      'output' => 'visualn_generic_output', // this input type represents a generic d3.js object with data keys from source
    ];
  }

}
