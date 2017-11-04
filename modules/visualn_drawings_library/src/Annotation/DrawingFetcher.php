<?php

namespace Drupal\visualn_drawings_library\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Drawing Fetcher item annotation object.
 *
 * @see \Drupal\visualn_drawings_library\Plugin\DrawingFetcherManager
 * @see plugin_api
 *
 * @Annotation
 */
class DrawingFetcher extends Plugin {


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
