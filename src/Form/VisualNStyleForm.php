<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VisualNStyleForm.
 *
 * @package Drupal\visualn\Form
 */
class VisualNStyleForm extends EntityForm {


  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Constructs an VisualNStyleEditForm object.
   *
   * @param \Drupal\visualn\Plugin\VisualNDrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   */
  public function __construct(VisualNDrawerManager $visualn_drawer_manager) {
    $this->visualNDrawerManager = $visualn_drawer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('plugin.manager.visualn.drawer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_style = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_style->label(),
      '#description' => $this->t("Label for the VisualN style."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_style->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn\Entity\VisualNStyle::load',
      ],
      '#disabled' => !$visualn_style->isNew(),
    ];

    // Get drawer plugins list
    $definitions = $this->visualNDrawerManager->getDefinitions();
    // @todo: is it really needed to include empty element here
    $drawers_list = [];
    //$drawers_list = ['' => $this->t('- Select -')];
    foreach ($definitions as $definition) {
      $drawers_list[$definition['id']] = $definition['label'];
    }
    $default_drawer = $visualn_style->isNew() ? '' : $visualn_style->getDrawerId();
    $form['drawer_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Drawer'),
      '#options' => $drawers_list,
      '#default_value' => $default_drawer,
      '#description' => $this->t("Drawer for the VisualN style."),
      '#ajax' => [
        'callback' => '::drawerConfigForm',
        'wrapper' => 'drawer-config-form-ajax',
      ],
      '#empty_value' => '',
      '#required' => TRUE,
    ];

    // Attach drawer configuration form
    $drawer_plugin_id = !empty($form_state->getValues()) ? $form_state->getValue('drawer_id') : $default_drawer;
    $config_form = [];

    // @todo: potentially config values can override style values e.g. "label" (see "name" attribute, it should be
    //    contained inside a container)
    $form['drawer_config'] = [];
    if ($drawer_plugin_id) {
      $drawer_config = $this->entity->getDrawerConfig();
      $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $drawer_config);

      // set new configuration. may be used by ajax calls from drawer forms
      $configuration = $form_state->getValues();
      $configuration = !empty($configuration) ? $configuration : [];
      $configuration = $drawer_config + $configuration;
      $drawer_plugin->setConfiguration($configuration);

      $form['drawer_config'] = $drawer_plugin->buildConfigurationForm($form['drawer_config'], $form_state);
    }

    $form['drawer_config'] += [
      '#prefix' => '<div id="drawer-config-form-ajax">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Add into an interface or add description
   * @todo: Rename method if needed
   */
  public function drawerConfigForm(array $form, FormStateInterface $form_state) {
    return !empty($form['drawer_config']) ? $form['drawer_config'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_style = $this->entity;
    $status = $visualn_style->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN style.', [
          '%label' => $visualn_style->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN style.', [
          '%label' => $visualn_style->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_style->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $drawer_plugin_id = $form_state->getValue('drawer_id');
    $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, []);

    // @todo: here drawer_id and label can be misused if there is a key with the same name in drawer config form

    // Extract config values from drawer config form for saving in VisualNStyle config entity
    // and add drawer plugin id for the visualn style.
    $this->entity->set('drawer_id', $drawer_plugin_id);
    $drawer_plugin->submitConfigurationForm($form, $form_state);
    $drawer_config_values = $form_state->getValues();
    $this->entity->set('drawer_config', $drawer_config_values);
  }

}
