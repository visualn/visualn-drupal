<?php

namespace Drupal\visualn_resource\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'visualn_resource' widget.
 *
 * @FieldWidget(
 *   id = "visualn_resource",
 *   label = @Translation("VisualN resource"),
 *   field_types = {
 *     "visualn_resource"
 *   }
 * )
 */
class VisualNResourceWidget extends LinkWidget implements ContainerFactoryPluginInterface {

  // @todo: implement defaultSettings() method

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer')
    );
  }

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
  }

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
    $item = $items[$delta];
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';
    $visualn_data['drawer_config'] = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
    $visualn_data['drawer_fields'] = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];
    // @todo: when style is changed (in ajax) and it doesn't correspond to the one in visualn_data,
    //    drawer_config and drawer_fields should be also be reseted

    // @todo: move into a function (since resource format selection is used in many places)
    $definitions = \Drupal::service('plugin.manager.visualn.resource_format')->getDefinitions();
    // @todo: there should be some default behaviour for the 'None' choice (actually, this refers to formatter)
    $resource_formats = ['' => $this->t('- None -')];
    foreach ($definitions as $definition) {
      $resource_formats[$definition['id']] = $definition['label'];
    }

    $element['resource_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Resource format'),
      '#description' => $this->t('The format of the data source'),
      '#default_value' => $visualn_data['resource_format'],
      '#options' => $resource_formats,
      '#weight' => '2',
    ];

    $visualn_style_id = $item->visualn_style_id ?: '';
    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-' . $delta . '-drawer-config-ajax-wrapper';
    $visualn_styles = visualn_style_options(FALSE);
    $element['visualn_style_id'] = [
      '#title' => t('VisualN style'),
      '#type' => 'select',
      '#default_value' => $visualn_style_id,
      '#empty_option' => t('Default'),
      '#options' => $visualn_styles,
      '#weight' => '3',
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $element['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#weight' => '3',
      '#type' => 'details',
      '#title' => t('Style configuration'),
      // @todo: actually we should change which exactly element was triggered, because as it is done now
      //    it will open all 'details' (but it's not a problem here since it will be visible only on ajax replace)
      '#open' => $form_state->getTriggeringElement(),
    ];
    // @todo: on first ajax call (on select change) this code is called twice
    // dsm('test ajax');

    // @todo: is this ok to get parents this way?
    //    if used in #process though, #parents key is already set
    $parents = [$field_name, $delta];
    // user may change visualn style to 'Default' keyed by "", which is not null
    if ($form_state->getValue(array_merge($parents, ['visualn_style_id'])) !== NULL) {
      $visualn_style_id = $form_state->getValue(array_merge($parents, ['visualn_style_id']));
    }

    // @todo: this is a copy-paste from VisualNFormatter, maybe move into a Trait class (actually not exactly a copy-paste)
    // Attach drawer configuration form
    if($visualn_style_id) {

      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      // if VisualN Style does not exist, e.g. was deleted, return
      // @todo: copy to the other widgets and config forms (e.g. VisualN Block)
      if (empty($visualn_style)) {
        return $element;
      }
      $drawer_plugin = $visualn_style->getDrawerPlugin();

      // prepare drawer config; use per-field config and drawer config from visualn style
      // @todo: also get formatter config if any because this causes misunderstanding (?)
      //    actually it is not correct to use formatter settings in widget settings (those should be field settings then)
      $drawer_config = $visualn_data['drawer_config'] + $drawer_plugin->getConfiguration();


      // @todo: maybe there is no need to pass config since it is passed in createInstance
      // @todo: what if drawer form uses #process callback by itself, isn't it a problem
      //    since the current one is already a #process callback?
      $element['drawer_container']['drawer_config'] = [];
      // set new configuration. may be used by ajax calls from drawer forms
      $configuration = $form_state->getValue(array_merge($parents, ['drawer_container', 'drawer_config']));
      $configuration = !empty($configuration) ? $configuration : [];
      $configuration = $drawer_config + $configuration;
      $drawer_plugin->setConfiguration($configuration);

      //$subform = $element['drawer_container']['drawer_config'];
      //$sub_form_state = SubformState::createForSubform($subform, $form, $form_state);


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
        $drawer_fields = $visualn_data['drawer_fields'];
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

      $element['#element_validate'][] = [$this, 'validateDrawerFieldsForm'];
    }


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
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['uri'] = static::getUserEnteredStringAsUri($value['uri']);
      $drawer_config = [];
      if (!empty($value['drawer_container']['drawer_config'])) {
        foreach ($value['drawer_container']['drawer_config'] as $drawer_config_key => $drawer_config_item) {
          $drawer_config[$drawer_config_key] = $drawer_config_item;
        }
        // @todo: unset()
      }

      $drawer_fields = [];
      // @todo: set correct #parents array for the data keys to avoid this part
      if (!empty($value['drawer_container']['drawer_fields'])) {
        foreach ($value['drawer_container']['drawer_fields'] as $drawer_field_key => $drawer_field) {
          $drawer_fields[$drawer_field_key] = $drawer_field['field'];
        }
        // @todo: unset()
      }
      $visualn_data = [
        'resource_format' => $value['resource_format'],
        'drawer_config' => $drawer_config,
        'drawer_fields' => $drawer_fields,
      ];
      $value['visualn_data'] = serialize($visualn_data);
      $value += ['options' => []];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Add into an interface or add description
   *
   * return drawerConfigForm via ajax at style change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }

}
