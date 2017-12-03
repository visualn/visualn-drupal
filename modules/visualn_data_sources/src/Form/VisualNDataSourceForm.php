<?php

namespace Drupal\visualn_data_sources\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNDataSourceForm.
 */
class VisualNDataSourceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_data_source = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_data_source->label(),
      '#description' => $this->t("Label for the VisualN Data Source."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_data_source->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn_data_sources\Entity\VisualNDataSource::load',
      ],
      '#disabled' => !$visualn_data_source->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_data_source = $this->entity;
    $status = $visualn_data_source->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Data Source.', [
          '%label' => $visualn_data_source->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Data Source.', [
          '%label' => $visualn_data_source->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_data_source->toUrl('collection'));
  }

}
