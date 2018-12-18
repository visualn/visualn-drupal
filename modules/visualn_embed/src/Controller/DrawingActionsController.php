<?php

namespace Drupal\visualn_embed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\visualn_drawing\Entity\VisualNDrawing;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\visualn_embed\Form\DrawingEmbedListDialogForm;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenDialogCommand;

/**
 * Class DrawingActionsController.
 *
 * @see visualn_embed.module visualn_embed_form_alter()
 */
class DrawingActionsController extends ControllerBase {

  public function getNewDrawingDialogTitle($type) {
    $drawing_type  = \Drupal::entityTypeManager()->getStorage('visualn_drawing_type')->load($type);
    return t('Create %drawing_type', ['%drawing_type' => $drawing_type->label()]);
  }

  /**
   * Build.
   *
   * @return string
   *   Return Hello string.
   */
  public function createNew($type) {

    // @todo: return PageNotFound if type doesn't exist
    //   or AccessDenied if user doesn't have permission to create drawings of the give type

    // @todo: check user permissions for each single type

    // @todo: same for 'edit' entity form
    //   https://drupal.stackexchange.com/questions/216480/how-do-i-programmatically-generate-an-entity-form
    $entity = VisualNDrawing::create([
      // @todo: check for other required parameters
      'type' => $type,
    ]);

    // add a flag to form state to be used in visualn_embed_form_visualn_drawing_form_alter()
    $form_state_additions = ['visualn_drawing_preview_dialog' => TRUE];
    $drawing_form = \Drupal::service('entity.form_builder')->getForm($entity, 'default', $form_state_additions);

/*
    // @todo: should be added before form #process callbacks
    $drawing_form['actions']['save_preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and Preview'),
      '#ajax' => [
        //'callback' => '::ajaxSubmitForm',
        'callback' => [get_called_class(), 'ajaxGoToListCallback'],
        'event' => 'click',
      ],
    ];
*/

    // @todo: alter Submit entity button or replace it with a custom one

    return $drawing_form;
  }

  // @todo: reuse method in ::edit()
  public function edit_content($id) {
    $build = [];

    $entity = VisualNDrawing::load($id);
    if ($entity) {
      // add a flag to form state to be used in visualn_embed_form_visualn_drawing_form_alter()
      $form_state_additions = ['visualn_drawing_preview_dialog' => TRUE];
      $form_state_additions['visualn_update_widget'] = TRUE;
      $form_state_additions['visualn_update_drawing_id'] = $id;
      // @todo: maybe use 'add' action
      $drawing_form = \Drupal::service('entity.form_builder')->getForm($entity, 'default', $form_state_additions);
      $drawing_form['#attached']['library'][] = 'visualn_embed/preview-drawing-dialog';

      $build = $drawing_form;

      //$title = 'Edit';
      //$response->addCommand(new OpenDialogCommand('#new-drawing-dialog', $title, $drawing_form, ['width' => 'auto']));
    }

    return $build;
  }

  // @todo:
  public function edit($id) {
    $response = new AjaxResponse();

    $entity = VisualNDrawing::load($id);
    if ($entity) {
      // add a flag to form state to be used in visualn_embed_form_visualn_drawing_form_alter()
      $form_state_additions = ['visualn_drawing_preview_dialog' => TRUE];
      // @todo: maybe use 'add' action instead of 'default'
      $drawing_form = \Drupal::service('entity.form_builder')->getForm($entity, 'default', $form_state_additions);

      $title = 'Edit';
      $response->addCommand(new OpenDialogCommand('#new-drawing-dialog', $title, $drawing_form, ['classes' => ['ui-dialog' => 'ui-dialog-visualn'], 'modal' => TRUE]));
    }

    // @todo: return NotFound or AccessDenied
    return $response;
  }

  public function delete($id) {
     $response = new AjaxResponse();

    $entity = VisualNDrawing::load($id);
    if ($entity) {
      // add a flag to form state to be used in visualn_embed_form_visualn_drawing_form_alter()
      $form_state_additions = ['visualn_drawing_preview_dialog' => TRUE];
      $drawing_form = \Drupal::service('entity.form_builder')->getForm($entity, 'delete', $form_state_additions);

      $title = 'Delete';
      $response->addCommand(new OpenDialogCommand('#new-drawing-dialog', $title, $drawing_form, ['width' => 'auto', 'modal' => TRUE]));
    }

    // @todo: return NotFound or AccessDenied
    return $response;
 }

  /**
   * @todo: see visualn_embed_form_alter()
   */
  public static function ajaxGoToListCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      // @todo: it is possible to replace form markup though if scrolled the user won't
      //   immidiately see the error, not good UX
      //$response->addCommand(new ReplaceCommand('#drawing-emebed-form-wrapper', $form));

      $entity = $form_state->getFormObject()->getEntity();
      $title = !$entity->isNew() ? t('Edit') : t('Create');

      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $content = $form;
      $response->addCommand(new OpenDialogCommand('#new-drawing-dialog', $title, $content, ['classes' => ['ui-dialog' => 'ui-dialog-visualn'], 'modal' => TRUE]));

      return $response;
    }

    // @todo: the form doesn't work again as ajaxified
    // seems to be related to https://www.drupal.org/project/drupal/issues/2504115
/*
    $initial_dialog_form = \Drupal::service('form_builder')->getForm(DrawingEmbedListDialogForm::class);
    $initial_dialog_form['#action'] = '/visualn_embed/form/drawing_embed_dialog';
    $content = $initial_dialog_form;
*/


    $response->addCommand(new CloseDialogCommand('#new-drawing-dialog'));

    return $response;
  }

  /**
   * Prepare ajax response commands for 'Save and embed' drawing entity dialog submit
   *
   * Close all dialogs and embed the newly created drawing if no errors found.
   *
   * @see visualn_embed_form_visualn_drawing_form_alter()
   */
  public static function ajaxSaveEmbedCallback(array &$form, FormStateInterface $form_state) {
    // Get main ajax response commands and proceed with closing and embedding if no errors found
    $response = static::ajaxGoToListCallback($form, $form_state);
    if (!$form_state->hasAnyErrors()) {
      // close the main dialog and embed the drawing (see main dialog commands)
      // @see DrawingEmbedListDialogForm::ajaxSubmitForm()
      $entity = $form_state->getFormObject()->getEntity();
      $drawing_id = $entity->id();
      $data = [
        'drawing_id' => $drawing_id,
        'tag_attributes' => [
          'data-visualn-drawing-id' => $drawing_id,
        ],
      ];

      $response->addCommand(new EditorDialogSave($data));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Prepare ajax response commands for 'Save' drawing entity dialog submit
   *
   * Close the newly created drawing dialog form drawing if no errors found.
   *
   * @see visualn_embed_form_visualn_drawing_form_alter()
   */
  public static function ajaxUpdateCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Also possible to get drawing_id from $form_state->getFormObject()->getEntity()
    // though visualn_update_drawing_id is still needed to initialize the drawing edit form
    $drawing_id = $form_state->get('visualn_update_drawing_id');
    //$drawing_id = $form_state->getValue('drawing_id', 0);
    $data = [
      'drawing_id' => $drawing_id,
      'tag_attributes' => [
        'data-visualn-drawing-id' => $drawing_id,
      ],
    ];

    $response->addCommand(new EditorDialogSave($data));
    //$response->addCommand(new CloseDialogCommand('#new-drawing-dialog'));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
