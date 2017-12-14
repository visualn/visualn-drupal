<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides an 'Attached JSON Data Adapter' VisualN adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_attached_json",
 *  label = @Translation("Attached JSON Data Adapter"),
 *  input = "json_generic_attached",
 * )
 */
class AttachedJSONDataAdapter extends VisualNAdapterBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach the data. Drupal js settings are attached in json format, thus so is the data for the drawing.
    $data = $options['adapter_settings']['data'];
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['adapterData'] = $data;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/adapter-attached-json-data';

    // Attach drawer config to js settings
    // Also attach settings from the parent method
    parent::prepareBuild($build, $vuid, $options);
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnAttachedJSONDataAdapter';
  }

}
