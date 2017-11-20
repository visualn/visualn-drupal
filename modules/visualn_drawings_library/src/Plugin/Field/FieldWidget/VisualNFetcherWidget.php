<?php

namespace Drupal\visualn_drawings_library\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'visualn_fetcher' widget.
 *
 * @FieldWidget(
 *   id = "visualn_fetcher",
 *   label = @Translation("VisualN fetcher"),
 *   field_types = {
 *     "visualn_fetcher"
 *   }
 * )
 */
class VisualNFetcherWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      //'fetcher_id' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    /*$elements['fetcher_id'] = [
      '#type' => 'select',
      '#title' => t('Fetcher plugin id'),
      '#options' => $options,
      '#description' => t('Default drawing fetcher plugin.'),
    ];*/

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // @todo: add settings summary
    $summary = [];

    /*if (!empty($this->getSetting('fetcher_id'))) {
      // @todo: get label for the fetcher plugin
      $summary[] = t('Drawing fetcher: @fetcher_plugin_label', ['@fetcher_plugin_label' => $this->getSetting('fetcher_id')]);
    }*/

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';

    $item = $items[$delta];
    $fetcher_config = !empty($item->fetcher_config) ? unserialize($item->fetcher_config) : [];
    // @todo: use #field_parents key

    $fetchers_list = ['' => t('- Select drawing fetcher -')];

    // Get drawing fetchers plugins list
    // @todo: instantiate at class creation
    $definitions = \Drupal::service('plugin.manager.visualn.drawing_fetcher')->getDefinitions();
    foreach ($definitions as $definition) {
      $fetchers_list[$definition['id']] = $definition['label'];
    }

    // @todo: why not also considered fetcher_id from form_state here (even if doesn't affect the code after it since it is redefined below)?
    $fetcher_id = $item->fetcher_id ?: '';
    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-' . $delta . '-fetcher-config-ajax-wrapper';

    // select drawing fetcher plugin
    $element['fetcher_id'] = [
      '#type' => 'select',
      '#title' => t('Drawer fetcher plugin'),
      '#options' => $fetchers_list,
      '#default_value' => $fetcher_id,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#empty_value' => '',
    ];
    $element['fetcher_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
    ];

    // @todo: is this ok to get parents this way?
    //    if used in #process though, #parents key is already set
    $parents = array_merge($element['#field_parents'], [$field_name, $delta]);
    // user may change fetcher to 'Default' keyed by "", which is not null
    if ($form_state->getValue(array_merge($parents, ['fetcher_id'])) !== NULL) {
      $fetcher_id = $form_state->getValue(array_merge($parents, ['fetcher_id']));
      $fetcher_config = $form_state->getValue(array_merge($parents, ['fetcher_container', 'fetcher_config']), []);
    }

    if ($fetcher_id) {
      $fetcher_plugin = \Drupal::service('plugin.manager.visualn.drawing_fetcher')
                          ->createInstance($fetcher_id, $fetcher_config);

      // @todo: Set entity type and bundle for the fetcher_plugin since it may need the list of all its fields.

      // @todo: We can't pass the current reference because it doesn't always exist,
      //    e.g. when setting default value for the field in field settings.
      $entity_type = $this->fieldDefinition->get('entity_type');
      $bundle = $this->fieldDefinition->get('bundle');

      // @todo: maybe set as part of config?
      $fetcher_plugin->setEntityInfo($entity_type, $bundle);

      $fetcher_config = $fetcher_config + $fetcher_plugin->getConfiguration();

      // @todo: use #process callback
      $element['fetcher_container']['fetcher_config'] = [];


      // @todo: get config (consider also ajax calls from fetcher forms, see notice in VisualNResourceWidget)
      // set new configuration. may be used by ajax calls from fetcher forms
      $configuration = $form_state->getValue(array_merge($parents, ['fetcher_container', 'fetcher_config']));
      $configuration = !empty($configuration) ? $configuration : [];
      $configuration = $fetcher_config + $configuration;
      $fetcher_plugin->setConfiguration($configuration);


      // @todo: pass Subform:createForSubform() instead of $form_state
      $element['fetcher_container']['fetcher_config'] = $fetcher_plugin->buildConfigurationForm($element['fetcher_container']['fetcher_config'], $form_state);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // @todo: serialize fetcher_config
    foreach ($values as &$value) {
      $fetcher_config = [];
      if (!empty($value['fetcher_container']['fetcher_config'])) {
        foreach ($value['fetcher_container']['fetcher_config'] as $fetcher_config_key => $fetcher_config_item) {
          $fetcher_config[$fetcher_config_key] = $fetcher_config_item;
        }
        // @todo: unset()
      }
      $value['fetcher_config'] = serialize($fetcher_config);
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

    return $element['fetcher_container'];
  }

}
