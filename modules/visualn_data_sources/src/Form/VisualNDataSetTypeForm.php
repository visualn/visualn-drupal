<?php

namespace Drupal\visualn_data_sources\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNDataSetTypeForm.
 */
class VisualNDataSetTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_data_set_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_data_set_type->label(),
      '#description' => $this->t("Label for the VisualN Data Set type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_data_set_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn_data_sources\Entity\VisualNDataSetType::load',
      ],
      '#disabled' => !$visualn_data_set_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_data_set_type = $this->entity;
    $status = $visualn_data_set_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Data Set type.', [
          '%label' => $visualn_data_set_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Data Set type.', [
          '%label' => $visualn_data_set_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_data_set_type->toUrl('collection'));
  }

}
