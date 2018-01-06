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
use Drupal\visualn\ResourceInterface;

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
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_styles/dashboard';

    return $resource;
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
    // @todo: choose a better ajax selector, must be unique
    //$ajax_wrapper_id = implode('-', $form['#array_parents']) . '--dashboard-ajax';
    $ajax_wrapper_id = 'some-wrapper--dashboard-ajax';
    $form['sections'] = [
      '#type' => 'select',
      '#title' => t('Number of sections'),
      '#options' => $options,
      '#default_value' => $configuration['sections'],
      // @todo: add ajax
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
        //'url' => views_ui_build_form_url($form_state),  // this is for views, so ajax setting depends on somewhere else
      ],
      // @todo: trigger style 'change' handler (though it won't work for style configuration itself)
      //    also when dealing with wrappe id, consider that mapping keys structure also should be updated
    ];
    // @todo: the value should be cleaned out from form_state in the extractFormValues() method
    $form['update_sections'] = [
      '#type' => 'submit',
      '#value' => t('Update sections'),
      '#prefix' => '<div class="form-item">',
      '#suffix' => '</div>',
    ];
    $form['ajax_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#process' => [[get_called_class(), 'processConfigurationFormSectionsUpdate']],
    ];

    return $form;
  }

  public static function processConfigurationFormSectionsUpdate(array $element, FormStateInterface $form_state, $form) {
    // Generally $element['#parents'] could be directly here since 'section' element triggers ajax request
    // by we leave it as it is for clarity.
    $element_parents = array_slice($element['#parents'], 0, -1);
    $sections = $form_state->getValue(array_merge($element_parents, ['sections']));
    if ($sections >= 3) {
      $container_key = $sections;
      $element[$container_key]['demo_textfield'] = [
        '#type' => 'textfield',
        '#description' => t('This field is for ajaxified config form demo purposes only. Change "sections" to see how it works.'),
        '#default_value' => $sections,
        '#disabled' => TRUE,
      ];
    }
    return $element;
  }

  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    $values = parent::extractFormValues($form, $form_state);

    // @todo:
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
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['ajax_container'];
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
