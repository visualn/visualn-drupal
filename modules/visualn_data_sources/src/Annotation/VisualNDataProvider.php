<?php

namespace Drupal\visualn_data_sources\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Data Provider item annotation object.
 *
 * @see \Drupal\visualn_data_sources\Plugin\VisualNDataProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNDataProvider extends Plugin {


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
