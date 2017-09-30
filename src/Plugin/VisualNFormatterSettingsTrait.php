<?php

/**
 * @file
 * Contains methods to add visualn common settings to visualn fields formatters settings forms.
 */

namespace Drupal\visualn\Plugin;

use Drupal\core\form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides common elements for VisualN fields formatters
 * settings forms.
 */
trait VisualNFormatterSettingsTrait {

  /**
   * defaultSettings()
   */
  public static function visualnDefaultSettings() {
    return array(
      'visualn_style' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
    ) + parent::defaultSettings();
  }

  /**
   * settingsForm()
   */
  public function visualnSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // @todo: use $element instead of $form
    $visualn_styles = visualn_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );
    $form['visualn_style'] = [
      '#title' => t('VisualN style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('visualn_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $visualn_styles,
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer image styles')
        '#access' => TRUE
      ],
      // https://www.drupal.org/docs/8/creating-custom-modules/create-a-custom-field-formatter
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'formatter-drawer-config-form-ajax',  // @todo: use a more explicit wrapper
      ],
      '#required' => TRUE,
    ];
    $form['drawer_container'] = [
      '#prefix' => '<div id="formatter-drawer-config-form-ajax">',
      '#suffix' => '</div>',
      '#type' => 'container',
    ];

    // First, retrieve the field name for the current field]
    $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
    // Next, set the key for the setting for which a value is to be retrieved
    $setting_key = 'visualn_style';

    // Try to retrieve a value from the form state. This will not exist on initial page load
    if($value = $form_state->getValue(['fields', $field_name, 'settings_edit_form', 'settings', $setting_key])) {
      $visualn_style_id = $value;
    }
    // On initial page load, retrieve the default setting
    else {
      $visualn_style_id = $this->getSetting('visualn_style');
    }


    // Attach drawer configuration form
    if($visualn_style_id) {
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin_id = $visualn_style->getDrawerId();
      $drawer_config = $visualn_style->get('drawer');
      $stored_drawer_config = $this->getSetting('drawer_config');
      $drawer_config = $stored_drawer_config + $drawer_config;

      $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $drawer_config);
      $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
      // @todo: add a checkbox to choose whether to override default drawer config or not
      // or an option to reset to defaults
      // @todo: add group type of fieldset with info about overriding style drawer config

      // @todo: if drawer_config form empty?
      $form['drawer_container']['drawer_config'] = [];
      $form['drawer_container']['drawer_config'] = $drawer_plugin->buildConfigurationForm($form['drawer_container']['drawer_config'], $form_state);
      $form['drawer_container']['drawer_config']['#parents'] =  ['fields', $field_name, 'settings_edit_form', 'settings', 'drawer_config'];
      // @todo: element #parents could be set in a #process callback since it's original #parents value is already set there
      //$form['drawer_container']['drawer_config']['#process'] = [[get_class($this), 'processVisualnSettingsForm']];

      // @todo: trim values after submitting settings
      $data_keys = $drawer_plugin->dataKeys();
      if (!empty($data_keys)) {
        $drawer_fields = $this->getSetting('drawer_fields');
        $form['drawer_container']['drawer_fields'] = [
          '#type' => 'table',
          '#header' => [$this->t('Data key'), $this->t('Field')],
        ];
        foreach ($data_keys as $i => $data_key) {
          $form['drawer_container']['drawer_fields'][$data_key]['label'] = [
            '#plain_text' => $data_key,
          ];
          $form['drawer_container']['drawer_fields'][$data_key]['field'] = [
            '#type' => 'textfield',
            '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
          ];
        }
        $form['drawer_container']['drawer_fields']['#parents'] =  ['fields', $field_name, 'settings_edit_form', 'settings', 'drawer_fields'];
      }
      $form['drawer_container']['#element_validate'] = [[$this, 'validateDrawerFieldsForm']];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * return drawerConfigForm via ajax at style change
   * @todo: Add into an interface or add description
   * @todo: Rename method if needed
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
    $element_to_return = 'drawer_container';

    return $form['fields'][$field_name]['plugin']['settings_edit_form']['settings'][$element_to_return];
  }

  /**
   * Restructure $form_state values for $drawer_fields.
   * @todo: rename the method
   */
  public function validateDrawerFieldsForm(&$form, FormStateInterface $form_state, $full_form) {
    // set options values from table fields (i.e. remove "field" key from options path to the value)
    $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
    $element_parents =  ['fields', $field_name, 'settings_edit_form', 'settings', 'drawer_fields'];
    $drawer_fields = $form_state->getValue($element_parents);
    foreach ($drawer_fields as $key => $drawer_field) {
      $form_state->setValue(array_merge($element_parents, [$key]), $drawer_field['field']);
    }

    // @todo: use $element['#parents']
    $element_parents =  ['fields', $field_name, 'settings_edit_form', 'settings', 'drawer_config'];
    $visualn_style_id = $form_state->getValue(['fields', $field_name, 'settings_edit_form', 'settings', 'visualn_style']);
    if($visualn_style_id) {
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin_id = $visualn_style->getDrawerId();
      $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, []);

      $subform = $form['drawer_config'];
      $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);
      // @todo: it is not correct to call submit inside a validate method (validateDrawerFieldsForm())
      //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
      $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);
    }
  }

  /**
   * settingsSummary()
   */
  public function visualnSettingsSummary() {
    $summary = array();

    $visualn_styles = visualn_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($visualn_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $visualn_style_setting = $this->getSetting('visualn_style');
    if (isset($visualn_styles[$visualn_style_setting])) {
      $summary[] = t('VisualN style: @style', array('@style' => $visualn_styles[$visualn_style_setting]));
    }
    else {
      $summary[] = t('Raw data');
    }

    /*
    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }
*/

    return $summary;
  }

  /**
   * viewElements()
   */
  public function visualnViewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);

    // @todo: since this can be cached it could not take style changes (i.e. made in style
    //   configuration interface) into consideration, so a cache tag may be needed.

    // @todo: check if visualn style settings are overridden
    //   and use those if true

    $visualn_style_id = $this->getSetting('visualn_style');
    if (empty($visualn_style_id)) {
      return $elements;
    }

    // load style and get drawer manager from plugin definition
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin_id = $visualn_style->getDrawerId();
    $manager_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];

    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $this->visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    // @todo: pass options as part of $manager_config (?)
    $options = [
      //'style_id' => $this->getSetting('visualn_style'),
      'style_id' => $visualn_style_id,
      'drawer_config' => $visualn_style->get('drawer') + $this->getSetting('drawer_config'),
      // @todo: use another name for adapter group
      // delimiter separated values file
      /*'output_type' => 'file_dsv',  // @todo: for each delta output_type can be different (e.g. csv, tsv, json, xml)*/
      // @todo: maybe rename to mapper_settings (though it is used in adapter in views display style)
      //   so can be used both in mapper and in adapter (or even in drawer, if it does remapping by itself)
      'drawer_fields' => $this->getSetting('drawer_fields'),
      'adapter_settings' => [],
    ];

    // set additional options for the formatter type (can be overridden by the formatter)
    $options = $this->visualnViewElementsOptionsAll($elements, $options);
    // @todo: formatter settings should be restructured before saving
    //foreach ($options['drawer_fields'] as $k => $v) {
      //$options['drawer_fields'][$k] = $v['field'];
    //}

    foreach ($elements as $delta => $element) {
      // @todo: is it ok to access item this way?
      // @todo: this is a temporary solution
      $item = $items[$delta];
      if ($items[$delta]->visualn_style_id) {
        $visualn_style_id = $items[$delta]->visualn_style_id;
        $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
        $drawer_plugin_id = $visualn_style->getDrawerId();
        $visualn_data = !empty($items[$delta]->visualn_data) ? unserialize($items[$delta]->visualn_data) : [];
        $drawer_config = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];

        // don't use getSetting() if visualn_style is different from the one in formatter settings
        if ($visualn_style_id == $this->getSetting('visualn_style')) {
          $drawer_config += $this->getSetting('drawer_config');
        }
        // @todo: here we need just to get drawer manager (as in code above); also check comment for the manager below
        $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $drawer_config);
        $options = [
          //'style_id' => $this->getSetting('visualn_style'),
          'style_id' => $visualn_style_id,
          'drawer_config' => $drawer_config,
          // @todo: use another name for adapter group
          // delimiter separated values file
          // @todo: xml or json can't be considered dsv a file
          'output_type' => 'file_dsv',  // @todo: for each delta output_type can be different (e.g. csv, tsv, json, xml)
          //'output_info' => ['mimetype' => ''],  // currently it is passed via 'adapter_settings'
          // @todo: maybe rename to mapper_settings (though it is used in adapter in views display style)
          //   so can be used both in mapper and in adapter (or even in drawer, if it does remapping by itself)
          'drawer_fields' => !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [],
          // @todo: rename to ouput_info/output_data or something like that
          'adapter_settings' => [],
        ];
      }

      // @todo: generate and set unique visualization (picture/canvas) id
      $vuid = \Drupal::service('uuid')->generate();

      // set additional options for the formatter type for each single delta (can be overridden by the formatter)
      $options = $this->visualnViewElementsOptionsEach($element, $options, $item);
      // add selector for the drawing
      $html_selector = 'js-visualn-selector-file--' . $delta . '--' . substr($vuid, 0, 8);
      //$elements[$delta]['#attributes']['class'][] = $html_selector;
      $elements[$delta]['#suffix'] = isset($elements[$delta]['#suffix']) ? $elements[$delta]['#suffix'] : '';
      $elements[$delta]['#suffix'] .= "<div class='{$html_selector}'></div></div>";
      $options['html_selector'] = $html_selector;  // where to attach drawing selector

      // @todo: for different drawers there can be different managers
      $manager_plugin->prepareBuild($elements[$delta], $vuid, $options);
    }
    return $elements;
  }

  // @todo: currently these settings are added for visualn_file formatter so should be moved there
  public function visualnViewElementsOptionsAll($elements, array $options) {
    $options['output_type'] = 'file_dsv';  // @todo: for each delta output_type can be different (e.g. csv, tsv, json, xml)
    return $options;
  }

  // @todo: currently these settings are added for visualn_file formatter so should be moved there
  public function visualnViewElementsOptionsEach($element, array $options, $item) {
    $file = $element['#file'];
    $url = $file->url();
    $options['adapter_settings']['file_url'] = $url;
    $options['adapter_settings']['file_mimetype'] = $file->getMimeType();

    return $options;
  }

}
