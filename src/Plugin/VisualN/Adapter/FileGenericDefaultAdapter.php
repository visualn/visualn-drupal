<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides a 'File Generic Default Adapter' VisualN adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_file_generic_default",
 *  label = @Translation("File Generic Default Adapter"),
 *  input = "file_dsv",
 * )
 */
class FileGenericDefaultAdapter extends VisualNAdapterBase {

  // @todo: generally this is a DSV (delimiter separated values) file
  // @todo: convert it to general purpose adapter for formatted column text

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);

    $url = $options['adapter_settings']['file_url'];

    $file_type = '';
    if (!empty($options['adapter_settings']['file_mimetype'])) {
      $file_mimetype = $options['adapter_settings']['file_mimetype'];
      switch ($file_mimetype) {
        case 'text/tab-separated-values' :
          $file_type = 'tsv';
          break;
      }
    }

    // @todo: do nothing if file type undefined or set a warning in js console

    // adapter specific js settings
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['fileUrl'] = $url;
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['fileType'] = $file_type;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/adapter-file-generic-default';
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnFileGenericDefaultAdapter';
  }

}
