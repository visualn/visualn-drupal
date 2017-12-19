<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides an 'Empty Data Adapter' VisualN adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_empty_data",
 *  label = @Translation("Empty Data Adapter"),
 *  input = "empty_data_generic",
 * )
 */
class EmptyDataAdapter extends AttachedJSONDataAdapter {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    $options['adapter_settings']['data'] = [];

    // @todo: some drawers that attach their markup directly do not need js at all
    //    though some settings are still attached here
    parent::prepareBuild($build, $vuid, $options);
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnAttachedJSONDataAdapter';
  }

}
