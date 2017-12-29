<?php

/**
 * @file
 * Contains methods to add visualn common settings to visualn fields formatters settings forms.
 */

namespace Drupal\visualn\Plugin;

use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\core\form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\Helpers\VisualN;

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
      'visualn_style_id' => '',
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

    // prepare data for #process callback
    $initial_config_item = new \StdClass();
    $initial_config_item->visualn_style_id = $this->getSetting('visualn_style_id');
    $serialize_data = [
      'drawer_config' => $this->getSetting('drawer_config'),
      'drawer_fields' => $this->getSetting('drawer_fields'),
    ];
    // @todo: maybe using just an array instead of object would be a better option to avoid taking it for something
    //    different than a standard object for data storage
    // @todo: no need to serialize settings here, see VisualNImageFormatter for example
    $initial_config_item->visualn_data = serialize($serialize_data);


    $form['visualn_style_id'] = [
      '#title' => t('VisualN style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('visualn_style_id'),
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
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];
    // @todo: $item is needed in the #process callback to access drawer_config from field configuration,
    //    maybe there is a better way
    $form['drawer_container']['#item'] = $initial_config_item;

    return $form;
  }

  // @todo: may be this should be static
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $item = $element['#item'];
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';
    $visualn_data['drawer_config'] = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
    $visualn_data['drawer_fields'] = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];

    $configuration = $visualn_data;
    $configuration['visualn_style_id'] = $item->visualn_style_id ?: '';
    // @todo: add visualn_style_id = "" to widget default config (check) to avoid "?:" check

    $element = VisualNFormsHelper::processDrawerContainerSubform($element, $form_state, $form, $configuration);

    return $element;
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
    $visualn_style_setting = $this->getSetting('visualn_style_id');
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

    $visualn_style_id = $this->getSetting('visualn_style_id');
    if (empty($visualn_style_id)) {
      return $elements;
    }

    // load style and get drawer manager from plugin definition
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();
    $drawer_plugin_id = $drawer_plugin->getPluginId();
    $manager_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];

    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $this->visualNManagerManager->createInstance($manager_plugin_id, $manager_config);
    // @todo: pass options as part of $manager_config (?)
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: getConfiguration() here isn't needed since configuration is taken from setConfiguration()
      //    in __construct() of the drawer
      'drawer_config' => $drawer_plugin->getConfiguration() + $this->getSetting('drawer_config'),
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

        $visualn_data = !empty($items[$delta]->visualn_data) ? unserialize($items[$delta]->visualn_data) : [];
        $drawer_config = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];

        // don't use getSetting() if visualn_style is different from the one in formatter settings
        if ($visualn_style_id == $this->getSetting('visualn_style_id')) {
          $drawer_config += $this->getSetting('drawer_config');
        }
        $options = [
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


      // set additional options for the formatter type for each single delta (can be overridden by the formatter)
      $options = $this->visualnViewElementsOptionsEach($element, $options, $item);


      // Get drawing build
      $build = VisualN::makeBuild($options);
      //$elements[$delta]['visualn_drawing'] = $build;

      // @todo: this is a temporary solution, should be used custom theme template
      $elements[$delta]['#suffix'] = isset($elements[$delta]['#suffix']) ? $elements[$delta]['#suffix'] : '';
      $elements[$delta]['#suffix'] .= \Drupal::service('renderer')->render($build);
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
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    if (!empty($visualn_data['resource_format'])) {
      $resource_format_plugin_id = $visualn_data['resource_format'];
      $options['output_type'] = \Drupal::service('plugin.manager.visualn.resource_format')->getDefinition($resource_format_plugin_id)['output'];
    }

    return $options;
  }

}
