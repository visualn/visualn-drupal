<?php

namespace Drupal\visualn_data_sources\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn_drawings_library\Plugin\GenericDrawingFetcherBase;
//use Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNManagerManager;
use Drupal\visualn_data_sources\Plugin\VisualNDataProviderManager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Element;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'VisualN Data Provider generic drawing fetcher' VisualN drawing fetcher.
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_data_provider_generic",
 *  label = @Translation("VisualN Data Provider generic drawing fetcher"),
 *  needs_entity_info = FALSE,
 * )
 */
//class DataProviderGenericDrawingFetcher extends VisualNDrawingFetcherBase implements ContainerFactoryPluginInterface {
//class DataProviderGenericDrawingFetcher extends VisualNDrawingFetcherBase {
class DataProviderGenericDrawingFetcher extends GenericDrawingFetcherBase implements ContainerFactoryPluginInterface {

  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\visualn_data_sources\Plugin\VisualNDataProviderManager
   */
  protected $visualNDataProviderManager;

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
      $container->get('plugin.manager.visualn.manager'),
      $container->get('plugin.manager.visualn.data_provider')
    );
  }

  /**
   * Constructs a VisualNFormatter object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition
   * @param \Drupal\Core\Entity\EntityStorageInterface $visualn_style_storage
   *   The visualn style entity storage service.
   * @param \Drupal\visualn\Plugin\VisualNDrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   * @param \Drupal\visualn\Plugin\VisualNManagerManager $visualn_manager_manager
   *   The visualn manager manager service.
   * @param \Drupal\visualn_data_sources\Plugin\VisualNDataProviderManager $visualn_data_provider_manager
   *   The visualn data provider manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager, VisualNManagerManager $visualn_manager_manager, VisualNDataProviderManager $visualn_data_provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $visualn_style_storage, $visualn_drawer_manager, $visualn_manager_manager);

    $this->visualNDataProviderManager = $visualn_data_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data_provider_id' => '',
      'data_provider_config' => [],
      // these settings are provided by GenericDrawingFetcherBase abstract class
      //'visualn_style_id' => '',
      //'drawer_config' => [],
      //'drawer_fields' => [],
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->visualNDataProviderManager->getDefinitions();
    $data_providers = [];
    foreach ($definitions as $definition) {
      $data_providers[$definition['id']] = $definition['label'];
    }

    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['data_provider_id'])) .'-ajax-wrapper';

    $form['data_provider_id'] = [
      '#type' => 'select',
      '#title' => t('Data provider'),
      '#description' => t('The data provider for the drawing'),
      '#default_value' => $this->configuration['data_provider_id'],
      '#options' => $data_providers,
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select data provider -'),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallbackDataProvider'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $form['provider_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processProviderContainerSubform']],
      '#process' => [[$this, 'processProviderContainerSubform']],
    ];
    $form['provider_container']['#stored_configuration'] = $this->configuration;

    // Attach visualn style select box for the fetcher
    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * Return data provider configuration form via ajax request at style change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackDataProvider(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['provider_container'];
  }

  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processProviderContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'data_provider_id' => $stored_configuration['data_provider_id'],
      'data_provider_config' => $stored_configuration['data_provider_config'],
    ];



    $provider_element_parents = array_slice($element['#parents'], 0, -1);
    $data_provider_id = $form_state->getValue(array_merge($provider_element_parents, ['data_provider_id']));

    // If it is a fresh form (is_null($data_provider_id)) or an empty option selected ($data_provider_id == ""),
    // there is nothing to attach for provider config.
    if (!$data_provider_id) {
      return $element;
    }

    if ($data_provider_id == $configuration['data_provider_id']) {
      $data_provider_config = $configuration['data_provider_config'];
    }
    else {
      $data_provider_config = [];
    }

    $visualNDataProviderManager = \Drupal::service('plugin.manager.visualn.data_provider');

    $provider_plugin = $visualNDataProviderManager->createInstance($data_provider_id, $data_provider_config);

    $provider_container_key = $data_provider_id;

    // get provider configuration form

    $element[$provider_container_key]['provider_config'] = [];
    $element[$provider_container_key]['provider_config'] += [
      '#parents' => array_merge($element['#parents'], [$provider_container_key, 'provider_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$provider_container_key, 'provider_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$provider_container_key]['provider_config'], $form, $form_state);
    // attach provider configuration form
    $element[$provider_container_key]['provider_config']
              = $provider_plugin->buildConfigurationForm($element[$provider_container_key]['provider_config'], $subform_state);


    // since provider configuration form may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$provider_container_key]['provider_config'])) {
      $provider_element_array_parents = array_slice($element['#array_parents'], 0, -1);
      // check that the triggering element is data_provider_id but not fetcher_id select (or some other element) itself
      $details_open = FALSE;
      if ($form_state->getTriggeringElement()) {
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($provider_element_array_parents, ['data_provider_id']);
      }
      // @todo: take it out everywhere else
      $element[$provider_container_key] = [
        '#type' => 'details',
        '#title' => t('Provider configuration'),
        '#open' => $details_open,
      ] + $element[$provider_container_key];
    }

    // @todo: replace with #element_submit when introduced into core
    // extract values for provider_container subform and provider_config
    //    remove provider_container key from form_state values path
    //    also it can be done in ::submitConfigurationForm()
    $element[$provider_container_key]['#element_validate'] = [[get_called_class(), 'validateProviderContainerSubForm']];
    //$element[$provider_container_key]['#element_validate'] = [[get_called_class(), 'submitDrawerContainerSubForm']];


    return $element;
  }

  // @todo: Restructuring form_state values (removing provider_container key) should be moved
  //    into #element_submit callback when introduced.
  // This is based on VisualNFormHelper::validateDrawerContainerSubForm().
  public static function validateProviderContainerSubForm(&$form, FormStateInterface $form_state, $full_form) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get provider_container_key (for selected provider is equal by convention to data_provider_id,
    // see processProviderContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $provider_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $provider_container_key = array_pop($element_parents);

    // remove 'provider_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);



    // Call provider_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    // @todo: it is not correct to call submit inside a validate method (validateDrawerContainerSubForm())
    //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
    //$full_form = $form_state->getCompleteForm();
    $subform = $form['provider_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $visualNDataProviderManager = \Drupal::service('plugin.manager.visualn.data_provider');
    $data_provider_id  = $form_state->getValue(array_merge($base_element_parents, ['data_provider_id']));
    // The submit callback shouldn't depend on plugin configuration, it relies only on form_state values.
    $data_provider_config  = [];
    $provider_plugin = $visualNDataProviderManager->createInstance($data_provider_id, $data_provider_config);
    $provider_plugin->submitConfigurationForm($subform, $sub_form_state);


    // move provider_config two levels up (remove 'provider_container' and $provider_container_key) in form_state values
    $provider_config_values = $form_state->getValue(array_merge($element_parents, [$provider_container_key, 'provider_config']));
    if (!is_null($provider_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['data_provider_config']), $provider_config_values);
    }

    // remove remove 'provider_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$provider_container_key]));
    // also unset 'provider_container' key if empty
    // this check is added in case something else is added to the container by exteinding classes
    // @todo: actually the same check should be added before unsetting provider_container_key (and
    //    to other places where the same logic with config forms is implemented)
    if (!$form_state->getValue($element_parents)) {
      $form_state->unsetValue($element_parents);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    //dsm($this->configuration);

    $data_provider_id = $this->configuration['data_provider_id'];
    $data_provider_config = $this->configuration['data_provider_config'];
    $visualn_style_id = $this->configuration['visualn_style_id'];
    //if (empty($visualn_style_id) || empty($data_provider_id)) {
    if (empty($visualn_style_id)) {
      return parent::fetchDrawing();
    }

    $build = [];

    // load style and get drawer manager from plugin definition
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    $drawer_plugin = $visualn_style->getDrawerPlugin();
    $drawer_plugin_id = $drawer_plugin->getPluginId();
    $manager_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['manager'];

    // @todo: check if config is needed
    $manager_config = [];
    $manager_plugin = $this->visualNManagerManager->createInstance($manager_plugin_id, $manager_config);

    // @todo:

    $vuid = \Drupal::service('uuid')->generate();
    // Add drawer info before calling data provider in case it uses it. E.g. it can use
    // these options to create demo data sets depending on the drawer and its config.
    $options = [
      'style_id' => $visualn_style_id,
      'drawer_config' => $this->configuration['drawer_config'],
      'drawer_fields' => $this->configuration['drawer_fields'],
      'output_type' => '',
      'adapter_settings' => [],
    ];


    if (!empty($data_provider_id)) {
      $provider_plugin = $this->visualNDataProviderManager->createInstance($data_provider_id, $data_provider_config);
      // @todo:
      //  Provider plugin may return attachments:
      //  - js script that dynamically generates data
      //  - js settings for the script
      //  - even html markup to be parsed on client side to get data
      //  Provider should return output_type to be used by manager to build chain
      //  Provider may return additional adapter_settings or maybe resource_settings
      //    that could be taken into consideration by manager when defining adapter used
      // @todo: review the decision to add an 'data_provider' subelement
      //    though at this stage $build is empty anyway and data provide can make not so much use of it
      $build['data_provider'] = [];
      $provider_plugin->prepareBuild($build['data_provider'], $vuid, $options);
      // @todo: maybe in a similar way $build['drawing'] should be passed to manager but not the $build itself

      $options['output_type'] = $provider_plugin->getOutputType();
      // @todo: Previously named adapter_settings but then renamed because it relates to
      //  the source and data but not the adapter iteself. Other name suggestions: _settings, _info,
      //  _properties, _deliveries, _information, _descriptors, _aux, _parameters
      $output_interface = $provider_plugin->getOutputInterface();
      // Every output type may have its different (but generally speaking, fixed) set of interface parameters
      $options['adapter_settings'] = $provider_plugin->getOutputInterface();
      // @todo: when selecting an adapter at chain building stage, it should have
      //    a method to check if it complies with the output interface
    }

    // @todo: maybe use adapter_config instead of adapter_settings for consistency

    // Add html selector where the drawing should be attached.
    // Intentionally attach html_selector setting after provider call since
    // it should not use it in any way.
    $html_selector = 'js-visualn-selector-block--' . substr($vuid, 0, 8);
    $options['html_selector'] = $html_selector;
    // @todo: maybe use prefix and suffix instead of markup
    $build['#markup'] = "<div class='{$html_selector}'></div>";



    // @todo: pass parameter to the data provider and just get the result


    $manager_plugin->prepareBuild($build, $vuid, $options);

    $drawing_markup = $build;

    return $drawing_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }
}

// @todo: add comments everywhere (in particlar to #process callback method code)

