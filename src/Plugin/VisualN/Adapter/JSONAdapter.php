<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

//use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides a 'JSON Adapter' VisualN adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_json",
 *  label = @Translation("JSON Adapter"),
 *  input = "json_generic",
 * )
 */
class JSONAdapter extends FileGenericDefaultAdapter {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // This setting is required by the DSV/JSON Adapter method
    // @todo: though it should be set in source provder
    $options['adapter_settings']['file_mimetype'] = 'application/json';

    // Attach drawer config to js settings
    // Also attach settings from the parent method
    parent::prepareBuild($build, $vuid, $options);
  }

}
