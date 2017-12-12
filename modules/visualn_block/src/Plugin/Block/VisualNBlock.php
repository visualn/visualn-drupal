<?php

namespace Drupal\visualn_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
//use Drupal\Core\Link;
//use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
//use Drupal\Core\Entity\EntityStorageInterface;
//use Drupal\visualn\Plugin\VisualNDrawerManager;
//use Drupal\visualn\Plugin\VisualNManagerManager;
//use Drupal\visualn\Plugin\VisualNResourceFormatManager;
use Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\visualn_iframe\ShareLinkBuilder;

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
   * The visualn drawing fetcher manager service.
   *
   * @var \Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherManager
   */
  protected $visualNDrawingFetcherManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.visualn.drawing_fetcher')
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
   * @param \Drupal\visualn_drawings_library\Plugin\VisualNDrawingFetcherManager $visualn_drawing_fetcher_manager
   *   The visualn drawing fetcher manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VisualNDrawingFetcherManager $visualn_drawing_fetcher_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->visualNDrawingFetcherManager = $visualn_drawing_fetcher_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'fetcher_id' => '',
         'fetcher_config' => [],
         'enable_share_link' => 0,
         'iframe_hash' => '',
        ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Get fetcher plugins list for the drawing fetcher select.
    $fetchers_list = [];
    $definitions = $this->visualNDrawingFetcherManager->getDefinitions();
    foreach ($definitions as $definition) {
      // Exclude fetchers with which have at least one required context scince here no context is provided.
      if (!empty($definition['context'])) {
        foreach ($definition['context'] as $name => $context_definition) {
          if ($context_definition->isRequired()) {
            continue 2;
          }
        }
      }
      $fetchers_list[$definition['id']] = $definition['label'];
    }

    // @todo: review this check after the main issue in drupal core is resolved
    // @see https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $fetcher_id = $form_state->getCompleteFormState()->getValue(['settings', 'fetcher_id']);
    }
    else {
      $fetcher_id = $form_state->getValue(['settings', 'fetcher_id']);
    }


    // If form is new and form_state is null for the fetcher_id, get fetcher_id from the block configuration.
    // Also we destinguish empty string and null because user may change fetcher
    // to '- Select drawing fetcher -' keyed by "", which is not null.
    if (is_null($fetcher_id)) {
      $fetcher_id = $this->configuration['fetcher_id'];
    }


    // select drawing fetcher plugin
    //$ajax_wrapper_id = 'visualn-block-fetcher-config-ajax-wrapper';
    $form_array_parents = isset($form['#array_parents']) ? $form['#array_parents'] : [];
    $ajax_wrapper_id = implode('-', array_merge($form_array_parents, ['fetcher_id'])) . '-visualn-block-ajax-wrapper';
    $form['fetcher_id'] = [
      '#type' => 'select',
      '#title' => t('Drawer fetcher plugin'),
      '#options' => $fetchers_list,
      '#default_value' => $fetcher_id,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#empty_value' => '',
      '#empty_option' => t('- Select drawing fetcher -'),
    ];
    $form['fetcher_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#weight' => '1',
      // add process at fetcher_container level since fetcher_id input should be already mapped (prepopulated)
      // for further processing (see FormBuilder::processForm())
      //'#process' => [[get_called_class(), 'processFetcherConfigurationSubform']],

      //'#process' => [[$this, 'processFetcherConfigurationSubform']],
    ];
    // Use #process callback for building the fetcher configuration form itself because it
    // may need #array_parents key to be already filled up (see PluginFormInterface::buildConfigurationForm()
    // method comments on https://api.drupal.org).
    $form['fetcher_container']['fetcher_config'] = [
      '#type' => 'container',
      '#process' => [[$this, 'processFetcherConfigurationSubform']],
    ];



    // check if visualn_iframe module is enabled
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('visualn_iframe')){
      // @todo: also maybe disable access to the iframe by url (or add some other access management mechanism)
      $form['enable_share_link'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Share link'),
        '#default_value' => $this->configuration['enable_share_link'],
        '#weight' => '1',
      ];
    }
    // @todo: is this needed, i.e. maybe the value in $this->configuration is enough
    $form['iframe_hash'] = [
      '#type' => 'value',
      '#value' => $this->configuration['iframe_hash'],  // hash is set in blockSubmit()
    ];




    return $form;
  }

  /**
   * Return fetcher configuration form via ajax request at fetcher change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    return $form['settings']['fetcher_container'];
  }

  /**
   * Process fetcher configuration subform
   *
   * Here we use process callback, cinse fetcher_plugin::buildConfigurationForm() may need
   * element #array_parents keys (e.g. to define triggering element at ajax calls).
   */
  //public static function processFetcherConfigurationSubform(array $element, FormStateInterface $form_state, $form) {
  public function processFetcherConfigurationSubform(array $element, FormStateInterface $form_state, $form) {
    $fetcher_element_parents = array_slice($element['#parents'], 0, -2);
    $fetcher_id = $form_state->getValue(array_merge($fetcher_element_parents, ['fetcher_id']));
    // Whether fetcher_id is an empty string (which means changed to the Default option) or NULL (which means
    // that the form is fresh) there is nothing to attach for fetcher_config subform.
    //if (empty($fetcher_id)) {
    if (!$fetcher_id) {
      return $element;
    }

    //if (!is_null($fetcher_id) && $fetcher_id == $this->configuration['fetcher_id']) {
    if ($fetcher_id == $this->configuration['fetcher_id']) {
      // @note: plugins are instantiated with default configuration to know about it
      //    but at configuration form rendering always the form_state values are (should be) used
      $fetcher_config = $this->configuration['fetcher_config'];
    }
    else {
      $fetcher_config = [];
    }

    // Basically this check is not needed
    if ($fetcher_id) {
      // fetcher plugin buildConfigurationForm() needs Subform:createForSubform() form_state
      $subform_state = SubformState::createForSubform($element, $form, $form_state);

      // instantiate fetcher plugin
      $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);
      // attach fetcher configuration form
      // @todo: also fetcher_config_key may be added here as it is done for ResourceGenericDraweringFethcher
      //    and drawer_container_key.
      $element = $fetcher_plugin->buildConfigurationForm($element, $subform_state);

      // change fetcher configuration form container to fieldset if not empty
      if (Element::children($element)) {
        $element['#type'] = 'fieldset';
        $element['#title'] = t('Drawing fetcher settings');
      }
/*
      $element['fetcher_config'] = [];
      $element['fetcher_config'] += [
        '#parents' => array_merge($element['#parents'], ['fetcher_config']),
        '#array_parents' => array_merge($element['#array_parents'], ['fetcher_config']),
      ];
*/
/*
      $element[$drawer_container_key]['drawer_config'] = [];
      $element[$drawer_container_key]['drawer_config'] += [
        '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
        '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
      ];
*/
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['fetcher_id'] = $form_state->getValue('fetcher_id');
    // @todo: also keep in mind that fetcher_container will be removed from form_state values after restructuring
    $this->configuration['fetcher_config'] = $form_state->getValue(['fetcher_container', 'fetcher_config'], []);
    $this->configuration['iframe_hash'] = $form_state->getValue('iframe_hash');

    // @todo: extracting and restructuring values, if needed, would better be done on the element level,
    //    as it is done inside ResourceGenericDrawingFetcher for drawer_container with drawer_config and drawer_fields.

    $fetcher_id = $this->configuration['fetcher_id'];
    $fetcher_config = $this->configuration['fetcher_config'];
    $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);

    // @todo: maybe move fetcher_plugin::submitConfigurationForm() to #element_submit when introduced into core,
    //    currently can be also in #element_validate which is not correct strictly speaking
    $full_form = $form_state->getCompleteForm();
    $subform = $form['settings']['fetcher_container']['fetcher_config'];
    $subform_state = SubformState::createForSubform($subform, $full_form, $form_state->getCompleteFormState());
    $fetcher_plugin->submitConfigurationForm($subform, $subform_state);




    // @todo: move this block into a visualn_iframe function or a class
    // @todo: service would be preferable to using \Drupal (assuming it's an option in current context)
    $moduleHandler = \Drupal::service('module_handler');
    // check if visualn_iframe module is enabled
    if ($moduleHandler->moduleExists('visualn_iframe')){
      $this->configuration['enable_share_link'] = $form_state->getValue(['enable_share_link']);
      // @todo: maybe use a service
      $share_link_builder = new ShareLinkBuilder();
      // @todo: record should be craeted/changed first in the block Submit handler
      $hash = $this->configuration['iframe_hash'] ?: '';
      // the key should correspond to the key from the respective content provider service class
      // @todo: set key in the class property
      $hash = $share_link_builder->createIframeDbRecord('visualn_block_key', $this->configuration, $hash);
      // for the first time the form is submitted, the hash is empty
      if (empty($this->configuration['iframe_hash'])) {
        $this->configuration['iframe_hash'] = $hash;
      }
      // @todo: since we can use not only in page regions but also in panels etc.,
      //    we can't use block_id (also it is not accessible here) or other such info for
      //    the link generation
    }
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $fetcher_id = $this->configuration['fetcher_id'];

    if (empty($fetcher_id)) {
      return ['#markup' => ''];
    }

    // create fetcher plugin instance
    $fetcher_config = $this->configuration['fetcher_config'];
    $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);

    // get markup from the drawing fetcher
    $build['visualn_block'] = $fetcher_plugin->fetchDrawing();

    // @todo: move this block into a visualn_iframe function or a class
    // @todo: service would be preferable to using \Drupal (assuming it's an option in current context)
    $moduleHandler = \Drupal::service('module_handler');
    // check if visualn_iframe module is enabled
    if ($moduleHandler->moduleExists('visualn_iframe') && $this->configuration['enable_share_link']){
      // @todo: deal with render cache here (e.g. if module was enabled, disabled and then re-enabled - link won't appear/disapper because of block cache)
      // @todo: maybe use a service
      $share_link_builder = new ShareLinkBuilder();
      // @todo: record should be craeted/changed first in the block Submit handler
      $iframe_url = $share_link_builder->getIframeUrl($this->configuration['iframe_hash']);
      // this key is used in IframeConentProvider::provideContent()
      $build['share_iframe_link'] = $share_link_builder->buildLink($iframe_url);
      // @todo: since we can use not only in page regions but also in panels etc.,
      //    we can't use block_id (also it is not accessible here) or other such info for
      //    the link generation
    }

    return $build;
  }

}

