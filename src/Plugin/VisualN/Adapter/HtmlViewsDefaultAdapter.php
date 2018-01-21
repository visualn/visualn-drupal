<?php

namespace Drupal\visualn\Plugin\VisualN\Adapter;

use Drupal\visualn\Plugin\VisualNAdapterBase;
use Drupal\visualn\ResourceInterface;

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
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // adapter specific js settings
    $drawer_fields = $this->configuration['drawer_fields'];
    $dataKeys = array_keys($drawer_fields);  // we need only keys in adaper
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['dataKeys'] = $dataKeys;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/adapter-html-views-default';


    // get resource params
    $views_wrapper_id = $resource->views_content_wrapper_selector;
    $data_class_suffix = $resource->data_class_suffix;

    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['viewsContentWrapperSelector'] = $views_wrapper_id;
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['dataClassSuffix'] = $data_class_suffix;

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnHtmlViewsDefaultAdapter';
  }

}
