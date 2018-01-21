<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\ResourceInterface;

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
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $resource->data = [];

    // @todo: actually standalone drawers don't need adapters at all though
    //    chain builder will try to build chain depending on the Resource output_type,
    //    this case should be examined in more detail

    // @todo: some drawers that attach their markup directly do not need js at all
    //    though some settings are still attached here
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnAttachedJSONDataAdapter';
  }

}
