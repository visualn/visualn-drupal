<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Plugin\VisualNSetupBakerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VisualNSetupForm.
 */
class VisualNSetupForm extends EntityForm {

  /**
   * The visualn setup baker manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNSetupBakerManager
   */
  protected $visualNSetupBakerManager;

  /**
   * Constructs a VisualNSetupEditForm object.
   *
   * @param \Drupal\visualn\Plugin\VisualNSetupBakerManager $visualn_setup_baker_manager
   *   The visualn setup baker manager service.
   */
  public function __construct(VisualNSetupBakerManager $visualn_setup_baker_manager) {
    $this->visualNSetupBakerManager = $visualn_setup_baker_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('plugin.manager.visualn.setup_baker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_setup = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_setup->label(),
      '#description' => $this->t("Label for the VisualN Setup."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_setup->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn\Entity\VisualNSetup::load',
      ],
      '#disabled' => !$visualn_setup->isNew(),
    ];

    // @todo: this is almost a copy-paste from VisualNStyleForm

    $bakers_list = [];

    // Get setup baker plugins list
    $definitions = $this->visualNSetupBakerManager->getDefinitions();
    foreach ($definitions as $definition) {
      $bakers_list[$definition['id']] = $definition['label'];
    }

    $default_baker = $visualn_setup->isNew() ? "" : $visualn_setup->getBakerId();
    $form['baker_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Setup Baker'),
      '#options' => $bakers_list,
      '#default_value' => $default_baker,
      '#description' => $this->t("Baker for the drawer setup."),
      '#ajax' => [
        'callback' => '::bakerConfigForm',
        'wrapper' => 'baker-config-form-ajax',
      ],
      '#empty_value' => '',
      '#required' => TRUE,
    ];


    // output setup baker form

    // Attach baker configuration form
    $baker_plugin_id = !empty($form_state->getValues()) ? $form_state->getValue('baker_id') : $default_baker;

    // @todo: potentially config values can override style values e.g. "label" (see "name" attribute, it should be
    //    contained inside a container)
    $form['baker_config'] = [];
    if ($baker_plugin_id) {
      $baker_config = $visualn_setup->getBakerConfig();
      $baker_plugin = $this->visualNSetupBakerManager->createInstance($baker_plugin_id, $baker_config);

      // @todo:
/*
      // set new configuration. may be used by ajax calls from drawer forms and also when submitting the form
      //    without ajax (when js is disabled) or when validation errors occur. see stored_drawer_config in other handlers
      $configuration = $form_state->getValues();
      $configuration = !empty($configuration) ? $configuration : [];
      // @todo: check order here. for ajax call configuration should (since changed values should go
      //    to buildConfigurationForm() to rebuild the form) override drawer_config
      $configuration = $drawer_config + $configuration;
      $drawer_plugin->setConfiguration($configuration);
*/

      $form['baker_config'] = $baker_plugin->buildConfigurationForm($form['baker_config'], $form_state);
    }

    $form['baker_config'] += [
      '#prefix' => '<div id="baker-config-form-ajax">',
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
  public function bakerConfigForm(array $form, FormStateInterface $form_state) {
    return !empty($form['baker_config']) ? $form['baker_config'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_setup = $this->entity;
    $status = $visualn_setup->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Setup.', [
          '%label' => $visualn_setup->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Setup.', [
          '%label' => $visualn_setup->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_setup->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // @todo:

    $baker_plugin_id = $form_state->getValue('baker_id');
    // @todo: maybe use config (?)
    //$baker_config = $visualn_setup->getBakerConfig();
    $baker_config = [];
    $baker_plugin = $this->visualNSetupBakerManager->createInstance($baker_plugin_id, $baker_config);

    // Extract config values from baker config form for saving in VisualNSetup config entity
    // and add baker plugin id for the visualn setup.
    $this->entity->set('baker_id', $baker_plugin_id);
    // give baker a chance to act on config values before saving (e.g. extract and transform config values)
    // and maybe perform other actions
    $baker_plugin->submitConfigurationForm($form, $form_state);
    $baker_config_values = $form_state->getValues();
    $this->entity->set('baker_config', $baker_config_values);
  }

}
