<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\ResourceInterface;

//use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides a 'TSV Adapter' VisualN adapter. Generally this is a wrapper around DSV Adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_tsv",
 *  label = @Translation("TSV Adapter"),
 *  input = "remote_generic_tsv",
 * )
 */
class TSVAdapter extends FileGenericDefaultAdapter {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // This setting is required by the DSV Adapter method
    // @todo: though it should be set in source provder
    $resource->file_mimetype = 'text/tab-separated-values';

    // Attach drawer config to js settings
    // Also attach settings from the parent method
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    return $resource;
  }

}
