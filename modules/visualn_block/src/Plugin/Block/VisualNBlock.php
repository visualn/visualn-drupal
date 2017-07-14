<?php

namespace Drupal\visualn_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNManagerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Drupal\Core\Form\SubformStateInterface;

/**
 * Provides a 'VisualNBlock' block.
 *
 * @Block(
 *  id = "visualn_block",
 *  admin_label = @Translation("VisualN Block"),
 * )
 */
class VisualNBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\visualn\Plugin\VisualNDrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager, VisualNManagerManager $visualn_manager_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNManagerManager = $visualn_manager_manager;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'resource_url' => '',
         //'resource_format' => '',
         'visualn_style_id' => '',
         'drawer_config' => [],
         'drawer_fields' => [],
        ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // @todo: validate the url
    $form['resource_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource Url'),
      '#description' => $this->t('Resource URL to use as data source for the drawing'),
      '#default_value' => $this->configuration['resource_url'],
      '#maxlength' => 256,
      '#size' => 64,
      '#weight' => '1',
      '#required' => TRUE,
    ];

    //$visualn_styles = visualn_style_options(FALSE);
    $visualn_styles = visualn_style_options();
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );
    // @todo: choose a better selector name
    $ajax_wrapper_id = 'visualn-block-config-ajax-wrapper';
    // @todo: maybe rename to visualn_style (the same note for visualn_file)
    $form['visualn_style_id'] = [
      '#type' => 'select',
      '#title' => $this->t('VisualN style'),
      '#options' => $visualn_styles,
      '#default_value' => $this->configuration['visualn_style_id'] ?: '',
      '#description' => $this->t('Default style for the data to render.'),
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer visualn styles')
        '#access' => TRUE
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#required' => TRUE,
      '#weight' => '2',
    ];
    $form['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#weight' => '3',
    ];

    // @todo: review this check after the main issue in drupal core is resolved
    // @see https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $visualn_style_id = $form_state->getCompleteFormState()->getValue(['settings', 'visualn_style_id']);
    }
    else {
      $visualn_style_id = $form_state->getValue(['settings', 'visualn_style_id']);
    }
    $visualn_style_id = $visualn_style_id ?: $this->configuration['visualn_style_id'];
    // Attach drawer configuration form
    if($visualn_style_id) {
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin_id = $visualn_style->getDrawerId();
      $drawer_config = $visualn_style->get('drawer');
      // @todo:
      $stored_drawer_config = $this->configuration['drawer_config'];
      $drawer_config = $stored_drawer_config + $drawer_config;
      $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $drawer_config);
      // @todo: maybe there is no need to pass config since it is passed in createInstance
      $config_form = $drawer_plugin->getConfigForm($drawer_config);
      if (!empty($config_form)) {
        // @todo: add group type of fieldset with info about overriding style drawer config
        $form['drawer_container']['drawer_config'] = $config_form;
      }

      // @todo: trim values after submitting settings
      $data_keys = $drawer_plugin->dataKeys();
      if (!empty($data_keys)) {
        // @todo: get option setting
        $drawer_fields = $this->configuration['drawer_fields'];
        $form['drawer_container']['drawer_fields'] = [
          '#type' => 'table',
          '#header' => [t('Data key'), t('Field')],
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
      }
    }
    return $form;
  }

  /**
   * Return drawerConfigForm via ajax at style change
   * @todo: Rename method if needed
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    return $form['settings']['drawer_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['resource_url'] = $form_state->getValue('resource_url');
    //$this->configuration['resource_format'] = $form_state->getValue('resource_format');
    $this->configuration['visualn_style_id'] = $form_state->getValue('visualn_style_id');
    $this->configuration['drawer_config'] = $form_state->getValue(['drawer_container', 'drawer_config']);
    $drawer_fields = $form_state->getValue(['drawer_container', 'drawer_fields']);
    foreach ($drawer_fields as $k => $drawer_field) {
      if (!trim($drawer_field['field'])) {
        unset($drawer_fields[$k]);
      }
      else {
        $drawer_fields[$k] = $drawer_field['field'];
      }
    }
    $this->configuration['drawer_fields'] = $drawer_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $url = $this->configuration['resource_url'];
    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return $build;
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
      'drawer_config' => $visualn_style->get('drawer') + $this->configuration['drawer_config'],
      'drawer_fields' => $this->configuration['drawer_fields'],
      'adapter_settings' => [],
    ];

    $options['output_type'] = 'file_dsv';  // @todo: for each delta output_type can be different (e.g. csv, tsv, json, xml)

    $options['adapter_settings']['file_url'] = $this->configuration['resource_url'];

    // @todo: this should be detected dynamically depending on reousrce type, headers, file extension
    $options['adapter_settings']['file_mimetype'] = 'text/tab-separated-values';

    // @todo: generate and set unique visualization (picture/canvas) id
    $vuid = \Drupal::service('uuid')->generate();
    // add selector for the drawing
    $html_selector = 'js-visualn-selector-file--' . $delta . '--' . substr($vuid, 0, 8);

    //$build['visualn_block']['#markup'] = '<p>' . $this->configuration['resource_url'] . '</p>';
    $build['visualn_block']['#markup'] = "<div class='{$html_selector}'></div></div>";

    $options['html_selector'] = $html_selector;  // where to attach drawing selector

    // @todo: for different drawers there can be different managers
    $manager_plugin->prepareBuild($build, $vuid, $options);

    // @todo: attach drawer

    return $build;
  }

}
