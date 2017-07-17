<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Resource Format item annotation object.
 *
 * @see \Drupal\visualn\Plugin\VisualNResourceFormatManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNResourceFormat extends Plugin {


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
   * The data output type of the plugin.
   *
   * @var string
   */
  public $output = '';

}
