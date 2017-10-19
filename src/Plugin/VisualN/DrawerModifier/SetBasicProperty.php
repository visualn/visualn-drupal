<?php

/**
 * @file
 * Conatins SetBasicProperty class.
 */

namespace Drupal\visualn\Plugin\VisualN\DrawerModifier;

use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNDrawerModifierBase;
use Drupal\visualn\ConfigurableDrawerModifierBase;

/**
 * Provides a 'Set Basic Property' VisualN drawer modifier.
 *
 * @VisualNDrawerModifier(
 *  id = "visualn_set_basic_property",
 *  label = @Translation("Set Basic Property"),
 * )
 */
class SetBasicProperty extends ConfigurableDrawerModifierBase {

  public function defaultConfiguration() {
    return [];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['test_textfield'] = [
      '#type' => 'textfield',
      '#title' => 'Test textfield',
    ];
    return $form;
  }

}
