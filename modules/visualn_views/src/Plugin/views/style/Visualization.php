<?php

/**
 * @file
 * Definition of Drupal\visualn_views\Plugin\views\style\Visualization.
 */

namespace Drupal\visualn_views\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNManagerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Element;
use Drupal\visualn\Helpers\VisualN;

/**
 * Style plugin to render listing.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "visualization",
 *   title = @Translation("VisualN"),
 *   help = @Translation("Render a listing of view data."),
 *   theme = "views_view_visualn",
 *   display_types = { "normal" }
 * )
 *
 */
class Visualization extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Specifies if the plugin uses row plugins.
   *
   * @todo: disable row plugin
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

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
   * The visualn manager manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNManagerManager
   */
  protected $visualNManagerManager;

  /**
   * The visualn unique identifier. Used for fields mapping and html_selector
   *   to distinguish from other drawings.
   *
   * @var string
   */
  protected $vuid;

  // @todo: add an option to filters form to override mappings

  // @todo: add a getVisualNOptions() to get style and drawer settings,
  //   by default returns $this->options array

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('plugin.manager.visualn.manager')
    );
  }

  /**
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager, VisualNManagerManager $visualn_manager_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNManagerManager = $visualn_manager_manager;
  }

  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['visualn_style_id'] = array('default' => '');
    $options['drawer_config'] = array('default' => []);
    $options['expose_keys_mapping'] = 0;

    return $options;
  }

  /**
   * Get display style options.
   *
   * By default this returns $this->options, but can be overriden
   *   e.g. by exposed keys mapping form.
   *
   * @todo: add to class interface
   */
  public function getVisualNOptions() {
    // @todo: check exposed mappings and override if any
    // @todo: rename option key
    if ($this->options['expose_keys_mapping']) {
      // @todo:
      //dsm($this->view->getExposedInput());
      $exposed_input = $this->view->getExposedInput();
      // @todo: the key should be unique. see visualn_form_alter()
      // do not confuse with 'drawer_fields' in buildOptionsForm()
      if (!empty($exposed_input['drawer_fields'])) {
        foreach ($exposed_input['drawer_fields'] as $key => $input_value) {
          // @todo: this one is actually form buildOptionsForm()
          $this->options['drawer_fields'][$key] = $input_value['field'];
        }
      }
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    //$visualn_styles = visualn_style_options(FALSE);
    $visualn_styles = visualn_style_options();
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );
    // @see ImageFormatter::settingsForm()
    // @todo: onChange execute an ajax callback to show mappings form for the drawer
    $form['visualn_style_id'] = array(
      '#type' => 'select',
      '#title' => $this->t('VisualN style'),
      '#description' => $this->t('Default style for the data to render.'),
      '#default_value' => $this->options['visualn_style_id'],
      '#options' => $visualn_styles,
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer visualn styles')
        '#access' => TRUE
      ],
      '#ajax' => [
        'url' => views_ui_build_form_url($form_state),
      ],
      //'#executes_submit_callback' => TRUE,
      '#required' => TRUE,
    );
    $form['drawer_container'] = [
      '#type' => 'container',
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];

    // @todo: add a checkbox to choose whether to override default drawer config or not
    //    or an option to reset to defaults
    // @todo: add group type of fieldset with info about overriding style drawer config


    // @todo: check for #ajax key in the drawer config form tree and add 'url' key (or look for a better solution)


    $form['expose_keys_mapping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose fields mapping'),
      '#default_value' => $this->options['expose_keys_mapping'],
    ];
  }

  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    // @todo: it seems that that most code here could be moved outside into a method since it is used multiple times
    //    in other places (see ResourceGenericDrawingFetcher::processDrawerContainerSubform() for example)

    $element_parents = $element['#parents'];

    // Here form_state corresponds to the current display style handler though is not instanceof SubformStateInterface.
    $style_element_parents = array_slice($element['#parents'], 0, -1);
    // since the function if called as a #process callback and the visualn_style_id select was already processed
    // and the values were mapped then it is enough to get form_state value for it and no need to check
    // configuration value (see FormBuilder::processForm() and FormBuilder::doBuildForm())
    // and no need in "is_null($visualn_style_id) then set value from config"
    $visualn_style_id = $form_state->getValue(array_merge($style_element_parents, ['visualn_style_id']));

    // If it is a fresh form (is_null($visualn_style_id)) or an empty option selected ($visualn_style_id == ""),
    // there is nothing to attach for drawer config.
    if (!$visualn_style_id) {
      return $element;
    }


    // Here the drawer plugin is initialized (inside getDrawerPlugin()) with the config stored in the style.
    $drawer_plugin = $this->visualNStyleStorage->load($visualn_style_id)->getDrawerPlugin();

    // We use drawer config from configuration only if it corresponds to the selected style. Also
    // we don't get form_state values for the drawer config here since they are handled by
    // drawer buildConfigurationForm() method itself and also even in buildConfigurationForm()
    // drawer should have access to the $this->configuration['drawer_config'] values.
    if ($visualn_style_id == $this->options['visualn_style_id']) {
      // Set initial configuration for the plugin according to the configuration stored in fetcher config.
      $drawer_config = $this->options['drawer_config'];
      $drawer_plugin->setConfiguration($drawer_config);

      // @todo: uncomment when the issue with handling drawer fields form_state values is resolved.
      //$drawer_fields = $this->options['drawer_fields'];

      // @todo: Until some generic way to hande drawer_fields form is introduced,
      //    e.g. \VisualN::buildDrawerDataKeysForm(), we should handle form_state values for the drawer_fields
      //    manually (i.e. in case of form validation errors form_state values should be used).
      $drawer_fields
        = $form_state->getValue(array_merge($element_parents, ['drawer_fields']), $this->options['drawer_fields']);
    }
    else {
      // Leave drawer_config unset for later initialization with drawer_plugin->getConfiguration() values
      // which are generally taken from visualn style configuration.

      // Initialize drawer_config based on (visualn style stored config) in case it is needed somewhere else below.
      $drawer_config = $drawer_plugin->getConfiguration();

      // Since drawer_fields is always an empty array for a visualn style drawer plugin (VisualNStyle::getDrawerPlugin()),
      // it is ok to set it to an empty array here. In contrast, if null, drawer_config should be taken
      // from the visualn style plugin configuraion.
      $drawer_fields = [];
    }

    // Use unique drawer container key for each visualn style from the select box so that the settings
    // wouldn't be overridden by the previous one on ajax calls (expecially when styles use the same
    // drawer and thus the same configuration form with the same keys).
    $drawer_container_key = $visualn_style_id;


    // get drawer configuration form

    $element[$drawer_container_key]['drawer_config'] = [];
    $element[$drawer_container_key]['drawer_config'] += [
      '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$drawer_container_key]['drawer_config'], $form, $form_state);
    // attach drawer configuration form
    $element[$drawer_container_key]['drawer_config']
              = $drawer_plugin->buildConfigurationForm($element[$drawer_container_key]['drawer_config'], $subform_state);


    // Process #ajax elements. If drawer configuration form uses #ajax to rebuild elements on cerain events,
    // those calls must use views specific 'url' setting or new elements values won't be saved.
    $this->replaceAjaxOptions($element[$drawer_container_key]['drawer_config'], $form_state);



    // @todo: Use some kind of \VisualN::buildDrawerDataKeysForm($drawer_plugin, $form, $form_state) here.

    $data_keys = $drawer_plugin->dataKeys();
    if (!empty($data_keys)) {
      // @todo: get rid of value from 'field' or massage value at plugin submit
      $element[$drawer_container_key]['drawer_fields'] = [
        '#type' => 'table',
        '#header' => [$this->t('Data key'), $this->t('Field')],
      ];
      $field_names = $this->displayHandler->getFieldLabels();
      foreach ($data_keys as $data_key) {
        $element[$drawer_container_key]['drawer_fields'][$data_key]['label'] = [
          '#plain_text' => $data_key,
        ];
        $element[$drawer_container_key]['drawer_fields'][$data_key]['field'] = [
          '#type' => 'select',
          '#options' => $field_names,
          '#default_value' => isset($drawer_fields[$data_key]) ? $drawer_fields[$data_key] : '',
        ];
      }
    }


    // since drawer and fields onfiguration forms may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$drawer_container_key]['drawer_config'])
         || Element::children($element[$drawer_container_key]['drawer_fields'])) {
      $details_open = FALSE;
      if ($form_state->getTriggeringElement()) {
        $style_element_array_parents = array_slice($element['#array_parents'], 0, -1);
        // check that the triggering element is visualn_style_id but not fetcher_id select (or some other element) itself
        $triggering_element = $form_state->getTriggeringElement();
        // @todo: triggering element may be empty
        $details_open = $triggering_element['#array_parents'] === array_merge($style_element_array_parents, ['visualn_style_id']);
        // if triggered an ajaxafield configuration form element, open configuration form details after refresh
        if (!$details_open) {
          $array_merge = array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']);
          $array_diff = array_diff($triggering_element['#array_parents'], $array_merge);
          $is_subarray = $triggering_element['#array_parents'] == array_merge($array_merge, $array_diff);
          if ($is_subarray) {
            $details_open = TRUE;
          }
        }
      }
      $element[$drawer_container_key] = [
        '#type' => 'details',
        '#title' => t('Style configuration'),
        '#open' => $details_open,
      ] + $element[$drawer_container_key];
    }


    // @todo: attach #element_validate

    return $element;
  }


  /**
   * Process #ajax elements. If drawer configuration form uses #ajax to rebuild elements on cerain events,
   * those calls must use views specific 'url' setting or new elements values won't be saved.
   */
  protected function replaceAjaxOptions(&$element, FormStateInterface $form_state) {
    foreach (Element::children($element) as $key) {
      if (isset($element[$key]['#ajax'])) {
        $element[$key]['#ajax']['url'] = views_ui_build_form_url($form_state);
      }

      // check subtree elements
      $this->replaceAjaxOptions($element[$key], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    //$drawer_container_key = reset(Element::children($form['drawer_container']));
    $drawer_container_key = Element::children($form['drawer_container'])[0];
    //$base_element_parents = array_slice($element_parents, 0, -1);
    $element_parents = array_merge($form['#parents'], ['drawer_container']);
    $base_element_parents = $form['#parents'];



    // Call drawer_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    $full_form = $form_state->getCompleteForm();
    $subform = $form['drawer_container'][$drawer_container_key]['drawer_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $visualn_style_id  = $form_state->getValue(array_merge($base_element_parents, ['visualn_style_id']));
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();
    $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);







    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_config_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_config']));
    if (!is_null($drawer_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['drawer_config']), $drawer_config_values);
    }

    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_fields_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_fields']));
    if (!is_null($drawer_fields_values)) {
      $new_drawer_fields_values = [];
      foreach ($drawer_fields_values as $drawer_field_key => $drawer_field) {
        $new_drawer_fields_values[$drawer_field_key] = $drawer_field['field'];
      }

      $form_state->setValue(array_merge($base_element_parents, ['drawer_fields']), $new_drawer_fields_values);
    }

    // remove remove 'drawer_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$drawer_container_key]));
  }


  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    parent::preRender($result);

    // generally this returns $this->options but can overridden
    $style_options = $this->getVisualNOptions();

    // @todo: since this can be cached it could not take style changes (i.e. made in style
    //   configuration interface) into consideration, so a cache tag may be needed.

    $visualn_style_id = $style_options['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return;
    }


    // @todo: actually no need to use data_class_suffix here, any random string is ok
    $views_content_wrapper_selector = 'visualn-views-html-wrapper--' . $this->getDataClassSuffix();
    $options = [
      'style_id' => $visualn_style_id,
      // @todo: maybe move into 'drawer_settings'
      // @todo: compare with the same row in VisualNFormatterSettingsTrait::visualnViewElements()
      'drawer_config' => $style_options['drawer_config'],
      // @todo: maybe move into 'mapper_settings' (even though used in adapter)
      'drawer_fields' => $style_options['drawer_fields'],  // this setting should be used in adapter
      'output_type' => 'html_views',
      'adapter_settings' => [
        'views_content_wrapper_selector' => $views_content_wrapper_selector,
        // @todo: the vuid should be kept (see getVuid()) to wrap html data
        'data_class_suffix' => $this->getDataClassSuffix(),
      ],
    ];

    // Get drawing build
    $build = VisualN::makeBuild($options);

    // Attach the build to the view output
    $this->view->element['visualn_build'] = $build;

    // @todo: add wrapper so that adapter could hide the contents with data (so this part of resource actually)
    $this->view->element['#attributes']['class'][] = $views_content_wrapper_selector;

  }

  /**
   * Get Visualization vuid value.
   */
  public function getDataClassSuffix() {
    // @todo: rename vuid property to data_class_suffix
    if (empty($this->vuid)) {
      $vuid = \Drupal::service('uuid')->generate();
      $this->vuid = substr($vuid, 0, 4);
    }
    return $this->vuid;
  }

  // @todo: force using fields

}

