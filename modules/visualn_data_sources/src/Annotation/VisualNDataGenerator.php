<?php

namespace Drupal\visualn_data_sources\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Data Generator item annotation object.
 *
 * @see \Drupal\visualn_data_sources\Plugin\VisualNDataGeneratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNDataGenerator extends Plugin {


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
   * The raw resource format of the generator.
   *
   * @var string
   */
  public $raw_resource_format = 'visualn_generic_data_array';

  /**
   * The list of compatible base drawers ids.
   *
   * @var array
   */
  public $compatible_drawers = [];

}
