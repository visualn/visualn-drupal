<?php

/**
 * @file
 *
 * Conatins DefaultDrawerWrapper used for subdrawers. Its primary purpose is to allow modifiers
 * to modify base drawer behaviour.
 */

namespace Drupal\visualn\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerWrapperBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Default Drawer Wrapper' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_default_drawer_wrapper",
 *  label = @Translation("Default Drawer Wrapper"),
 *  role = "wrapper"
 * )
 */
// @todo: !IMPORTANT: drawer should always be actualized with every new change to the interface and base class
//    since it must include all the methods and wrapper around them to delegate to the subdrawer_base_drawer object.
class DefaultDrawerWrapper extends VisualNDrawerWrapperBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->subdrawer_base_drawer->buildConfigurationForm($form, $form_state);

    // Modify drawer configuration form here

    return $form;
  }

}
