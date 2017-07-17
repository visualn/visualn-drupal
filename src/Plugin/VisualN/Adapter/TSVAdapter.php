<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

//use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides a 'TSV Adapter' VisualN adapter. Generally this is a wrapper around DSV Adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_tsv",
 *  label = @Translation("TSV Adapter"),
 *  input = "tsv_generic",
 * )
 */
class TSVAdapter extends FileGenericDefaultAdapter {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // This setting is required by the DSV Adapter method
    // @todo: though it should be set in source provder
    $options['adapter_settings']['file_mimetype'] = 'text/tab-separated-values';

    // Attach drawer config to js settings
    // Also attach settings from the parent method
    parent::prepareBuild($build, $vuid, $options);
  }

}
