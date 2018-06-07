<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\ResourceInterface;

//use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides a 'XML Adapter' VisualN adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_xml",
 *  label = @Translation("XML Adapter"),
 *  input = "remote_xml_basic",
 * )
 */
class XMLAdapter extends FileGenericDefaultAdapter {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // This setting is required by the DSV/XML Adapter method
    // @todo: though it should be set in source provder
    $resource->file_mimetype = 'text/xml';

    // Attach drawer config to js settings
    // Also attach settings from the parent method
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    return $resource;
  }

}
