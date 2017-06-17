<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\Plugin\VisualNAdapterBase;

/**
 * Provides a 'Html Views Default Adapter' VisualN adapter.
 *
 * @VisualNAdapter(
 *  id = "visualn_html_views_default",
 *  label = @Translation("Html Views Default Adapter"),
 *  input = "html_views",
 *  output = "visualn_generic_input",
 * )
 */
class HtmlViewsDefaultAdapter extends VisualNAdapterBase {
  // It is supposed that the adapter doesn't need mapper (since it does it mapping by itself)
  // see 'output' property in plugin definition.


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);

    // adapter specific js settings
    $dataKeys = array_keys($options['drawer_fields']);  // we need only keys in adaper
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['dataKeys'] = $dataKeys;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/adapter-html-views-default';
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnHtmlViewsDefaultAdapter';
  }

}
