<?php

namespace Drupal\visualn_drawings_library\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn_drawings_library\Plugin\GenericDrawingFetcherBase;

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


    $build = [];

    // load style and get drawer manager from plugin definition
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();
    $drawer_plugin_id = $drawer_plugin->getPluginId();
    $manager_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];

    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $this->visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    // @todo: pass options as part of $manager_config (?)
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: unsupported operand types error
      // @todo: why can it be empty (not even an empty array)?
      //'drawer_config' =>  $this->configuration['drawer_config'] + $visualn_style->get('drawer'),
      'drawer_config' => ($this->configuration['drawer_config'] ?: []) + $drawer_plugin->getConfiguration(),
      'drawer_fields' => $this->configuration['drawer_fields'],
      'adapter_settings' => [],
    ];
    // @todo: this will attach some js settings even though drawer may use no js at all
    $options['output_type'] = 'empty_data_generic';





    // @todo: generate and set unique visualization (picture/canvas) id
    $vuid = \Drupal::service('uuid')->generate();
    // add selector for the drawing
    $html_selector = 'js-visualn-selector--' . substr($vuid, 0, 8);

    $build['#markup'] = "<div class='{$html_selector}'></div>";

    $options['html_selector'] = $html_selector;  // where to attach drawing selector

    // @todo: for different drawers there can be different managers
    $manager_plugin->prepareBuild($build, $vuid, $options);

    // @todo: attach drawer markup

    $drawing_markup = $build;

    return $drawing_markup;
  }
}

