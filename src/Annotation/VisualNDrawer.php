<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Drawer item annotation object.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNDrawer extends Plugin {


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
   * The manager plugin id  of the plugin.
   *
   * @var string
   */
  public $manager = 'visualn_default';

  /**
   * The data input type of the plugin.
   *
   * @var string
   */
  public $input = 'visualn_generic_input';
  //public $input = 'visualn_generic';

  /**
   * The data output type of the plugin. Generally, not used.
   *
   * @var string
   */
  public $output = '';

}
