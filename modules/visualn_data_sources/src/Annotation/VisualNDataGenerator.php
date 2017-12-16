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

}
