<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNSetupForm.
 */
class VisualNSetupForm extends EntityForm {

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

    /* You will need additional form elements for your custom properties. */

    return $form;
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
    $form_state->setRedirectUrl($visualn_setup->toUrl('collection'));
  }

}
