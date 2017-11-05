<?php

namespace Drupal\visualn_drawings_library\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Drawing Fetcher item annotation object.
 *
 * @see \Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNDrawingFetcher extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
