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

  /**
   * Shows whether the plugin needs a reference to an entity.
   *
   * It is usen by fetchers that use entity fields as data sources.
   *
   * @todo: remove when not needed
   *
   * @var boolean
   */
  public $needs_entity_info = TRUE;

}
