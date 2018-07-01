<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
//use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Line Chart' VisualN drawer.
 *
 * @ingroup drawer_plugins
 *
 * @VisualNDrawer(
 *  id = "visualn_linechart_basic",
 *  label = @Translation("Linechart Basic"),
 * )
 */
class LinechartBasicDrawer extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_basic_drawers/linechart-basic-drawer';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnLinechartBasicDrawer';
  }

}
