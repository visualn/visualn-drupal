<?php

namespace Drupal\visualn_drawings_library\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn_drawings_library\Plugin\GenericDrawingFetcherBase;
use Drupal\visualn\Helpers\VisualN;

/**
 * Provides a 'VisualN Standalone drawing fetcher' VisualN drawing fetcher.
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_standalone",
 *  label = @Translation("VisualN Standalone drawing fetcher")
 * )
 */
class StandaloneDrawingFetcher extends GenericDrawingFetcherBase {

  // @todo: add an option to show only styles using standalone drawers

  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    // @todo: review the code here
    $drawing_markup = parent::fetchDrawing();

    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return parent::fetchDrawing();
    }


    // @todo: pass options as part of $manager_config (?)
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: unsupported operand types error
      //    add default value into defaultConfiguration()
      'drawer_config' => ($this->configuration['drawer_config'] ?: []),
      'drawer_fields' => $this->configuration['drawer_fields'],
      'adapter_settings' => [],
    ];
    // @todo: this will attach some js settings even though drawer may use no js at all
    $options['output_type'] = 'empty_data_generic';




    // Get drawing build
    $build = VisualN::makeBuild($options);

    // @todo: attach drawer markup

    $drawing_markup = $build;

    return $drawing_markup;
  }
}

