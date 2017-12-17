<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for VisualN Resource plugins.
 */
// @todo: use ContextAwarePluginBase instead
abstract class VisualNResourceBase extends PluginBase implements VisualNResourceInterface {

  // @todo: termporary property, remove
  protected $output_info;

  // @todo: termporary method, remove
  public function getOutputInfo() {
    return $this->output_info;
  }

  // @todo: termporary method, remove
  public function setOutputInfo($output_type, $output_interface) {
    $this->output_info = [
      'output_type' => $output_type,
      'output_interface' => $output_interface,
    ];
  }

  // Add common methods and abstract methods for your plugin type here.

}
