<?php

namespace Drupal\visualn_file\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'visualn_visualn' widget.
 *
 * @FieldWidget(
 *   id = "visualn_visualn",
 *   label = @Translation("VisualN"),
 *   field_types = {
 *     "visualn"
 *   }
 * )
 */
class VisualNWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = parent::massageFormValues($values, $form, $form_state);
    // @todo: get values via extractConfigFormValues() and attach to $new_values
    foreach ($new_values as $key => $new_value) {
      $drawer_config = [];
      if (!empty($new_value['drawer_container']['drawer_config'])) {
        foreach ($new_value['drawer_container']['drawer_config'] as $drawer_config_key => $drawer_config_item) {
          $drawer_config[$drawer_config_key] = $drawer_config_item;
        }
        // @todo: unset()
      }
      $visualn_style_id = $new_value['visualn_style_id'];
      if($visualn_style_id) {
        $visualn_style = \Drupal::service('entity_type.manager')->getStorage('visualn_style')->load($visualn_style_id);
        $drawer_plugin_id = $visualn_style->getDrawerId();
        $drawer_plugin = \Drupal::service('plugin.manager.visualn.drawer')->createInstance($drawer_plugin_id, []);
        $extracted_values = $drawer_plugin->extractConfigArrayValues($new_value, ['drawer_container', 'drawer_config']);
        $drawer_config = $extracted_values;
      }
      /*if (is_array($drawer_config)) {
        $new_values[$key]['drawer_config'] = serialize($drawer_config);
      }*/
      $new_values[$key]['drawer_config'] = serialize($drawer_config);

      $drawer_fields = [];
      if (!empty($new_value['drawer_container']['drawer_fields'])) {
        foreach ($new_value['drawer_container']['drawer_fields'] as $drawer_field_key => $drawer_field) {
          $drawer_fields[$drawer_field_key] = $drawer_field['field'];
        }
        // @todo: unset()
      }
      $new_values[$key]['drawer_fields'] = serialize($drawer_fields);
      $visualn_data = [
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

    $item = $element['#value'];
    // @todo: check if not empty
    $item['visualn_data'] = !empty($item['visualn_data']) ? unserialize($item['visualn_data']) : [];
    $item['drawer_config'] = !empty($item['visualn_data']['drawer_config']) ? $item['visualn_data']['drawer_config'] : [];
    $item['drawer_fields'] = !empty($item['visualn_data']['drawer_fields']) ? $item['visualn_data']['drawer_fields'] : [];

    if (empty($item['fids'])) {
      return parent::process($element, $form_state, $form);
    }
    // @todo: attach style_id form only if file is uploaded

    // @todo: show this if override is allowed
    $parents = array_slice($element['#array_parents'], 0, -1);
    $field_element = NestedArray::getValue($form, $parents);
    //$ajax_wrapper_id = $field_element['#id'] . '-' . $element['#delta'] . '-drawer-config-ajax-wrapper';
    // @todo: this is test only ajax id
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

      //$visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $visualn_style = \Drupal::service('entity_type.manager')->getStorage('visualn_style')->load($visualn_style_id);  // @todo: replacement
      $drawer_plugin_id = $visualn_style->getDrawerId();
      // @todo: maybe pass form by reference and extend it in the VisualNDrawer::getConfigForm()
      $drawer_config = $visualn_style->get('drawer');  // @todo: rename the property for drawer config for style
      // @todo: set default option value to empty array
      $stored_drawer_config = $item['drawer_config'];  // @todo: replacement
      // @todo: also get formatter config if any because this causes misunderstanding (?)
      //    actually it is not correct to use formatter settings in widget settings (those should be field settings then)
      $drawer_config = $stored_drawer_config + $drawer_config;

      //$drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $drawer_config);
      // @todo: add $visualn_style->getDrawer() or getDrawerInstance()
      $drawer_plugin = \Drupal::service('plugin.manager.visualn.drawer')->createInstance($drawer_plugin_id, $drawer_config); // @todo: replacement

      // @todo: maybe there is no need to pass config since it is passed in createInstance
      $config_form = $drawer_plugin->getConfigForm($drawer_config);
      // @todo: add a checkbox to choose whether to override default drawer config or not
      // or an option to reset to defaults
      if (!empty($config_form)) {
        // @todo: add group type of fieldset with info about overriding style drawer config
        $element['drawer_container']['drawer_config'] = $config_form;
      }

      // @todo: trim values after submitting settings
      $data_keys = $drawer_plugin->dataKeys();
      if (!empty($data_keys)) {
        // @todo: get option setting
        $drawer_fields = $item['drawer_fields'];  // @todo: replacement
        $element['drawer_container']['drawer_fields'] = [
          '#type' => 'table',
          '#header' => [t('Data key'), t('Field')],
        ];
        foreach ($data_keys as $i => $data_key) {
          $element['drawer_container']['drawer_fields'][$data_key]['label'] = [
            '#plain_text' => $data_key,
          ];
          $element['drawer_container']['drawer_fields'][$data_key]['field'] = [
            '#type' => 'textfield',
            '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
          ];
        }
      }
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
