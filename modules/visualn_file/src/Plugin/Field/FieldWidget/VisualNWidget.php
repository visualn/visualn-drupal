<?php

// @todo: rename the file to VisualNFileWidget.php

namespace Drupal\visualn_file\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'visualn_visualn' widget.
 *
 * @FieldWidget(
 *   id = "visualn_file",
 *   label = @Translation("VisualN"),
 *   field_types = {
 *     "visualn_file"
 *   }
 * )
 */
class VisualNWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'drawer_config' => [],
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    return $element;
  }

  /**
   * Restructure $form_state values for $drawer_fields.
   * @todo: rename the method
   */
  public function validateDrawerFieldsForm(&$form, FormStateInterface $form_state, $full_form) {
    // see WidgetBase::extractFormValues()
    $field_name = $this->fieldDefinition->getName();

    $path = array_merge($form['#parents'], ['visualn_style_id']);
    $key_exists = NULL;
    $visualn_style_id = NestedArray::getValue($form_state->getValues(), $path, $key_exists);
    if($visualn_style_id) {
      $path = ['drawer_container', 'drawer_config'];
      $key_exists = NULL;
      $subform = NestedArray::getValue($form, $path, $key_exists);
      if ($key_exists) {
        $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

        $visualn_style = \Drupal::service('entity_type.manager')->getStorage('visualn_style')->load($visualn_style_id);
        $drawer_plugin = $visualn_style->getDrawerPlugin();

        // @todo: it is not correct to call submit inside a validate method (validateDrawerFieldsForm())
        //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
        $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // @todo: what if style doesn't exist?
    $element['#element_validate'][] = [$this, 'validateDrawerFieldsForm'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = parent::massageFormValues($values, $form, $form_state);
    // @todo: get drawer config values and attach to $new_values
    foreach ($new_values as $key => $new_value) {
      $drawer_config = [];
      if (!empty($new_value['drawer_container']['drawer_config'])) {
        foreach ($new_value['drawer_container']['drawer_config'] as $drawer_config_key => $drawer_config_item) {
          $drawer_config[$drawer_config_key] = $drawer_config_item;
        }
        // @todo: unset()
      }
      /*
      $visualn_style_id = $new_value['visualn_style_id'];
      if($visualn_style_id) {
        $visualn_style = \Drupal::service('entity_type.manager')->getStorage('visualn_style')->load($visualn_style_id);
        $drawer_plugin = $visualn_style->getDrawerPlugin();
        // @todo: submitConfigurationForm() should be used here instead of extractConfigArrayValues()
        //     also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
        $extracted_values = $drawer_plugin->extractConfigArrayValues($new_value, ['drawer_container', 'drawer_config']);
        $drawer_config = $extracted_values;
      }
      */
      /*if (is_array($drawer_config)) {
        $new_values[$key]['drawer_config'] = serialize($drawer_config);
      }*/

      $drawer_fields = [];
      // @todo: set correct #parents array for the data keys to avoid this part
      if (!empty($new_value['drawer_container']['drawer_fields'])) {
        foreach ($new_value['drawer_container']['drawer_fields'] as $drawer_field_key => $drawer_field) {
          $drawer_fields[$drawer_field_key] = $drawer_field['field'];
        }
        // @todo: unset()
      }

      $visualn_data = [
        'resource_format' => $new_value['resource_format'],
        'drawer_config' => $drawer_config,
        'drawer_fields' => $drawer_fields,
      ];
      $new_values[$key]['visualn_data'] = serialize($visualn_data);
    }

    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    // @see ImageWidget::process()

    // Define services as variables to explicitly see that they are loaded here
    // but not while object instantiation because the method is static.
    $visualNStyleStorage = \Drupal::service('entity_type.manager')->getStorage('visualn_style');
    //$visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');

    $item = $element['#value'];
    // @todo: check if not empty
    $item['visualn_data'] = !empty($item['visualn_data']) ? unserialize($item['visualn_data']) : [];
    $item['resource_format'] = !empty($item['visualn_data']['resource_format']) ? $item['visualn_data']['resource_format'] : '';
    $item['drawer_config'] = !empty($item['visualn_data']['drawer_config']) ? $item['visualn_data']['drawer_config'] : [];
    $item['drawer_fields'] = !empty($item['visualn_data']['drawer_fields']) ? $item['visualn_data']['drawer_fields'] : [];

    if (empty($item['fids'])) {
      return parent::process($element, $form_state, $form);
    }
    // @todo: attach style_id form only if file is uploaded

    // @todo: move into a function (since resource format selection is used in many places)
    $definitions = \Drupal::service('plugin.manager.visualn.resource_format')->getDefinitions();
    // @todo: there should be some default behaviour for the 'None' choice (actually, this refers to formatter)
    $resource_formats = ['' => t('- None -')];
    foreach ($definitions as $definition) {
      $resource_formats[$definition['id']] = $definition['label'];
    }

    $element['resource_format'] = [
      '#type' => 'select',
      '#title' => t('Resource format'),
      '#description' => t('The format of the data source'),
      '#default_value' => $item['resource_format'],
      '#options' => $resource_formats,
    ];

    // @todo: show this if override is allowed
    $parents = array_slice($element['#array_parents'], 0, -1);
    $field_element = NestedArray::getValue($form, $parents);
    //$ajax_wrapper_id = $field_element['#id'] . '-' . $element['#delta'] . '-drawer-config-ajax-wrapper';
    // @todo: this is test only ajax id (because the process method is static)
    $ajax_wrapper_id = 'field-name-id' . '-' . $element['#delta'] . '-drawer-config-ajax-wrapper';
    $visualn_styles = visualn_style_options(FALSE);
    $visualn_style_id = !empty ($item['visualn_style_id']) ? $item['visualn_style_id'] : '';
    $element['visualn_style_id'] = [
      '#title' => t('VisualN style'),
      '#type' => 'select',
      '#default_value' => $visualn_style_id,
      '#empty_option' => t('Default'),
      '#options' => $visualn_styles,
      // @todo: add ajax settings
      // @todo:
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        // @todo: ajax wrapper id for style id is ignored, and image (file) widget native one is called
        //   for multiple field
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $element['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
    ];
    // field_visualn_file_multiple[0][visualn_style_id]
    // @todo: if value is set, get it from from_state (see VisualNFormatter)
    $visualn_style_id = !empty ($item['visualn_style_id']) ? $item['visualn_style_id'] : '';

    // @todo: this is a copy-paste from VisualNFormatter, maybe move into a Trait class (actually not exactly a copy-paste)
    // Attach drawer configuration form
    if($visualn_style_id) {
      $visualn_style = $visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin = $visualn_style->getDrawerPlugin();

      // prepare drawer config; use per-field config and drawer config from visualn style
      // @todo: also get formatter config if any because this causes misunderstanding (?)
      //    actually it is not correct to use formatter settings in widget settings (those should be field settings then)
      $drawer_config = $item['drawer_config'] + $drawer_plugin->getConfiguration();


      // @todo: maybe there is no need to pass config since it is passed in createInstance
      // @todo: what if drawer form uses #process callback by itself, isn't it a problem
      //    since the current one is already a #process callback?
      $element['drawer_container']['drawer_config'] = [];
      // set new configuration. may be used by ajax calls from drawer forms
      $configuration = $form_state->getValue(array_merge($element['#parents'], ['drawer_container', 'drawer_config']));
      $configuration = !empty($configuration) ? $configuration : [];
      // @todo: is that the right order? won't it override form_state values, that changed on ajax call?
      $configuration = $drawer_config + $configuration;
      $drawer_plugin->setConfiguration($configuration);
      // @todo: createForSubform() works not pretty well by itself because when form
      //  is composed, its "#parents" key may be not set at the moment
      // @todo: pass Subform:createForSubform() instead of $form_state
      $element['drawer_container']['drawer_config'] = $drawer_plugin->buildConfigurationForm($element['drawer_container']['drawer_config'], $form_state);
      // @todo: add a checkbox to choose whether to override default drawer config or not
      // or an option to reset to defaults
      // @todo: add group type of fieldset with info about overriding style drawer config

      // prepare drawer fields subform
      // @todo: trim values after submitting settings
      $data_keys = $drawer_plugin->dataKeys();
      if (!empty($data_keys)) {
        $keys_subform = [];
        // @todo: get option setting
        $drawer_fields = $item['drawer_fields'];
        $keys_subform = [
          '#type' => 'table',
          '#header' => [t('Data key'), t('Field')],
        ];
        foreach ($data_keys as $i => $data_key) {
          $keys_subform[$data_key]['label'] = [
            '#plain_text' => $data_key,
          ];
          $keys_subform[$data_key]['field'] = [
            '#type' => 'textfield',
            '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
          ];
        }
        $element['drawer_container']['drawer_fields'] = $keys_subform;
      }

      $element['drawer_container'] = [
        '#type' => 'details',
        '#title' => t('Style configuration'),
        // @todo: actually we should change which exactly element was triggered, because as it is done now
        //    it will open all 'details' (but it's not a problem here since it will be visible only on ajax replace)
        '#open' => $form_state->getTriggeringElement(),
      ] + $element['drawer_container'];
    }
    return parent::process($element, $form_state, $form);
  }

  /**
   * {@inheritdoc}
   *
   * return drawerConfigForm via ajax at style change
   * @todo: Add into an interface or add description
   * @todo: Rename method if needed
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {

    //$form_parents = explode('/', $request->query->get('element_parents'));

    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];
    array_pop($triggering_element_parents);
    $element = NestedArray::getValue($form, $triggering_element_parents);
    //\Drupal::logger('visualn')->notice(print_r($element['#cardinality'], 1));

    // see FileWidget::process()
    // @todo: check field cardinality. see ManagedField:uploadAjaxCallback()
    if ($element['#cardinality'] != 1) {
      // @todo:
      return $form[$triggering_element['#parents'][0]];
    }
    else {
      return $element['drawer_container'];
    }
  }

}
