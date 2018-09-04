<?php

namespace Drupal\visualn;

trait ChainPluginJsTrait {

  /**
   * @inheritdoc
   */
  public function jsId() {
    return $this->getPluginId();
  }

  /**
   * @inheritdoc
   */
  public function prepareJsConfig(array &$configuration) {
  }

}
