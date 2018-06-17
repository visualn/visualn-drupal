<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Raw Resource Format plugins.
 */
abstract class RawResourceFormatBase extends PluginBase implements RawResourceFormatInterface {

  /**
   * {@inheritdoc}
   */
  public function buildResource(array $raw_input) {
    $output_type = $this->getPluginDefinition()['output'];
    $adapter_settings = $raw_input;


    // @todo: the code is copied from VisualN::getResourceByOptions() so check comments there

    $resource_plugin_id = $output_type;

    $visualNResourceManager = \Drupal::service('plugin.manager.visualn.resource');
    $plugin_definitions = $visualNResourceManager->getDefinitions();

    if (!isset($plugin_definitions[$resource_plugin_id])) {
      $resource_plugin_id = 'generic';
    }

    $resource_plugin_config = ['adapter_settings' => $adapter_settings];
    $resource = $visualNResourceManager->createInstance($resource_plugin_id, $resource_plugin_config);

    $resource->setValue($adapter_settings);
    $resource->setResourceType($output_type);

    return $resource;
  }

}
