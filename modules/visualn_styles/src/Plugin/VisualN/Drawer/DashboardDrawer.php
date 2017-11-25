<?php

/**
 * @file
 * Conatins DashboardDrawer based on Dashboard d3.js script http://bl.ocks.org/NPashaP/96447623ef4d342ee09b
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\Component\Utility\NestedArray;
use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'Dashboard' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_dashboard",
 *  label = @Translation("Dashboard"),
 *  input = "visualn_basic_tree_input",
 * )
 */
class DashboardDrawer extends VisualNDrawerBase {


  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/dashboard';
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'sections' => 3,
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->extractFormValues($form, $form_state);
    $configuration =  $configuration + $this->configuration;

    foreach (range(1, 100) as $number) {
      $options[$number] = $number;
    }
    // @todo: somewhere here should go mapping (and maybe other) subforms that depend
    //    on the drawer config form values (actually this should be done in appropriate places
    //    such as widget settings form, views style settins form etc.) because number of mapping
    //    fields change
    $form['sections'] = [
      '#type' => 'select',
      '#title' => t('Number of sections'),
      '#options' => $options,
      '#default_value' => $configuration['sections'],
      // @todo: add ajax
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => 'some-wrapper',
        //'url' => views_ui_build_form_url($form_state),  // this is for views, so ajax setting depends on somewhere else
      ],
      // @todo: trigger style 'change' handler (though it won't work for style configuration itself)
      //    also when dealing with wrappe id, consider that mapping keys structure also should be updated
    ];
    $form['update_sections'] = [
      '#type' => 'submit',
      '#value' => t('Update sections'),
      '#prefix' => '<div class="form-item">',
      '#suffix' => '</div>',
    ];
    $form['#prefix'] = '<div id="some-wrapper">';
    $form['#suffix'] = '</div>';

    if ($configuration['sections'] > 3) {
      $form['tmp'] = [
        '#type' => 'textfield',
        '#description' => t('This field is for ajaxified config form demo purposes only.'),
        '#disabled' => TRUE,
      ];
    }
    return $form;
  }

  /**
   * @inheritdoc
   */
  public function extractConfigArrayValues(array $values, array $array_parents) {
    $values = parent::extractConfigArrayValues($values, $array_parents);
    // remove submit button value from config values
    unset($values['update_sections']);

    return $values;
  }

  /**
   * @inheritdoc
   *
   * @todo: move into the base class
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];
    array_pop($triggering_element_parents);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnDashboardDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'State',
      'freq',
      'low',
      'mid',
      'high',
    ];
    return $data_keys;
  }

  /**
   * @inheritdoc
   */
  public function dataKeysStructure() {
    return [
      'State' => 'State',  // @todo: here can be an empty array "[]" which has the same sense
      'freq' => [
        'mapping' => 'freq',  // @todo: optional. can be omitted if coincides with key from dataKeys()
        'structure' => [
          'low' => ['mapping' => 'low', 'typeFunc' => 'parseInt'],
          'mid' => ['mapping' => 'mid', 'typeFunc' => 'parseInt'],
          'high' => ['mapping' => 'high', 'typeFunc' => 'parseInt'],
        ],
      ],
    ];
  }

}
