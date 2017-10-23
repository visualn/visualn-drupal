<?php

/**
 * @file
 * Conatins HighchartsWSSDrawer drawer native wrapper
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\DrawerWrapperTrait;

/**
 * Provides a 'HighchartsWSS' VisualN drawer native wrapper.
 *
 * @VisualNDrawer(
 *  id = "visualn_highcharts_wss_wrapper",
 *  label = @Translation("Highcharts with setup select native wrapper"),
 *  role = "wrapper"
 * )
 */
class HighchartsWSSWrapper extends HighchartsWSSDrawer {

  // @todo: class properties such as $modifiers, $methods_modifiers_substitutions should go to
  //    the DrawerBase abstract class so that they would be defined explicitly and wouldn't be overridden

  use DrawerWrapperTrait;

}
