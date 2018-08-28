<?php

namespace Drupal\visualn_embed\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * @todo: add docblock with description
 */
class DrawingEmbedListDialogForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visualn_drawing_embed_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo: the library should be connected outside of the form
    $form['#attached']['library'][] = 'visualn_embed/preview-drawing-dialog';
    $drawing_entities_list = [];



    // @todo: If it loads full entites, just get ids and labels using an sql query
    //   also check permission and published status
    $drawing_entities  = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->loadMultiple();
    foreach ($drawing_entities as $drawing_entity) {

      $drawing_id = $drawing_entity->id();

      // get preview, edit and delete links markup
      $preview_link = Link::createFromRoute($this->t('preview'), 'visualn_embed.drawing_embed_controller_real_preview', ['id' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);
      $edit_link = Link::createFromRoute($this->t('edit'), 'visualn_embed.drawing_controller_edit', ['id' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);
      $delete_link = Link::createFromRoute($this->t('delete'), 'visualn_embed.drawing_controller_delete', ['id' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);


      $drawing_entities_list[$drawing_entity->id()] = [
        'name' => $drawing_entity->label(),
        'id' => $drawing_entity->id(),
        'preview' => $preview_link,
        'edit' => $edit_link,
        'delete' => $delete_link,
      ];
    }


    // @todo: maybe add 'Update' button, but open 'New drawing' in a dialog but not modal dialog
    $form['update_list'] = [
      '#type' => 'submit',
      // @todo: rename to refresh
      '#value' => $this->t('Update list'),
      '#ajax' => [
        'callback' => '::ajaxUpdateDrawingsListCallback',
        'event' => 'click',
      ],
      '#limit_validation_errors' => [],
      '#submit' => ['::emptySubmit'],
    ];

    // @todo: other dialogs should be on top the current one and disable it
    //   maybe related to https://www.drupal.org/project/drupal/issues/2672344

    // @todo: check permissions
    // Create a new drawing button
    $form['new_drawing'] = [
      '#type' => 'submit',
      '#value' => $this->t('New drawing'),
      '#ajax' => [
        'callback' => '::ajaxNewDrawingCallback',
        'event' => 'click',
      ],
      '#limit_validation_errors' => [],
      '#submit' => ['::emptySubmit'],
    ];

    $header = [
      'name' => t('Name'),
      'id' => t('ID'),
      'preview' => t('Preview'),
      'edit' => t('Edit'),
      'delete' => t('Delete'),
    ];
    $form['drawing_id'] = [
      '#type' => 'tableselect',
      '#caption' => t('Drawings'),
      '#header' => $header,
      '#options' => $drawing_entities_list,
      '#multiple' => FALSE,
      '#empty' => $this->t('No drawings found'),
      //'#required' => TRUE,
    ];

    // @todo: make it sticky at the bottom of the table
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Embed Drawing'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    // add wrapper to be used on form refresh if errors found
    $form['#prefix'] = '<div id="drawing-emebed-form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // draiwng id is required when "embed drawing" submitted
    // it is used instead of 'required' option in drawing_id tableselect
    // since it is called in #element_validate which errors seems not to be
    // suppressed by #limit_validation_errors (see https://www.drupal.org/node/1488294)
    $drawing_id = $form_state->getValue('drawing_id', NULL);
    if (empty($drawing_id)) {
      $form_state->setErrorByName('drawing_id', $this->t('Drawing is required'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
/*
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }
*/

  }

  /**
   * Emtpy submit to make #limit_validation_errors work
   *
   * @todo: #limit_validation_errors doesn't work without submit callback
   */
  public function emptySubmit(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      // @todo: it is possible to replace form markup though if scrolled the user won't
      //   immidiately see the error, not good UX
      //$response->addCommand(new ReplaceCommand('#drawing-emebed-form-wrapper', $form));

      $content = $form;
      $response->addCommand(new OpenModalDialogCommand(t('Choose Drawing'), $content, ['classes' => ['ui-dialog' => 'ui-dialog-visualn']]));

      return $response;
    }

    $drawing_id = $form_state->getValue('drawing_id', 0);
    $data = [
      'drawing_id' => $drawing_id,
      'tag_attributes' => [
        'data-visualn-drawing-id' => $drawing_id,
      ],
    ];

    $response->addCommand(new EditorDialogSave($data));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxUpdateDrawingsListCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // @todo: or maybe update the list form element itself without updating the whole modal with the form
    //   since the drawing doesn't embed
    //   see core/modules/ckeditor/js/ckeditor.js dialog::afterclose

    $content = $form;
    // @todo: add ui-dialog-visualn class instead for consistency with other calls
    $response->addCommand(new OpenModalDialogCommand(t('Choose Drawing'), $content, ['classes' => ['ui-dialog' => 'ui-dialog-visualn']]));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxNewDrawingCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // @todo: on difference between open dialog and open dialog commands
    //   see https://www.drupal.org/project/entity_browser/issues/2727031

    $content = [];
    $content['links'] = [
      '#theme' => 'links',
      '#links' => [],
    ];


    // @todo: here all downstream dialogs are opened in a simple dialog
    //   but not in a modal dialog for two reasons:
    //   * it doesn't work ...
    //   * for UX reasons


    // @todo: EntityManager::getBundleInfo() deprecated
    $drawing_bundles = \Drupal::entityManager()->getBundleInfo('visualn_drawing');
    foreach ($drawing_bundles as $key => $drawing_bundle) {
      // see https://www.drupal.org/node/1989646
      $content['links']['#links']['link_'.$key] = [
        'title' => $drawing_bundle['label'],
        'url' => Url::fromRoute('visualn_embed.new_drawing_controller_build', ['type' => $key]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-options' => json_encode([
            'target' => 'new-drawing-dialog',
            'classes' => ['ui-dialog' => 'ui-dialog-visualn'],
          ]),
        ],
      ];



    }

    // @todo: some outline appering around the modal and on the left when not using CloseModalDialogCommand
    //   though it shouldn't be required, maybe because of focus
    //$response->addCommand(new CloseModalDialogCommand());


    $response->addCommand(new OpenDialogCommand('#new-drawing-dialog',
      $this->t('Choose drawing type'), $content,
      ['classes' => ['ui-dialog' => 'ui-dialog-visualn'], 'modal' => TRUE]));

    return $response;
  }

}
