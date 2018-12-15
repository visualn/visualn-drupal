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
 * Build drawing embed form with list of available drawings
 *
 * The form provides the list of available drawing entities with preview, edit
 * and delete action links for each. Allows to embed (or replace) selected drawings
 * into ckeditor content. Also the form allows to open new drawing (drawing types list) dialog.
 *
 * @ingroup ckeditor_integration
 */
class DrawingEmbedListDialogForm extends FormBase {

  // @todo: rename the class to DrawingSelectDialogForm

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visualn_embed_drawing_select_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo: the library should be connected outside of the form
    $form['#attached']['library'][] = 'visualn_embed/preview-drawing-dialog';
    $drawing_entities_list = [];


    // @todo: check comments in DrawingEmbedListDialogForm::buildForm()
    $input = $form_state->getUserInput();
    $selected_drawing_id = isset($input['editor_object']['data-visualn-drawing-id']) ? $input['editor_object']['data-visualn-drawing-id'] : 0;

    // @todo: If it loads full entites, just get ids and labels using an sql query
    // @todo: also check permission and published status
    $drawing_entities  = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->loadMultiple();
    if (count($drawing_entities)) {
      $drawing_type_thumbnails = static::getDrawingTypesThumbnails();

      foreach ($drawing_entities as $drawing_entity) {

        $drawing_id = $drawing_entity->id();

        // get preview, edit and delete links markup
        $preview_link = '';
        $edit_link = '';
        $delete_link = '';

        // @todo: add per drawing type permissions
        // check permissions
        $user = \Drupal::currentUser();
        if ($user->hasPermission('view published visualn drawing entities')) {
          $preview_link = Link::createFromRoute($this->t('preview'), 'visualn_embed.drawing_embed_controller_real_preview', ['id' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);
        }
        if ($user->hasPermission('edit visualn drawing entities')) {
          $edit_link = Link::createFromRoute($this->t('edit'), 'visualn_embed.drawing_controller_edit', ['id' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);
        }
        if ($user->hasPermission('delete visualn drawing entities')) {
          $delete_link = Link::createFromRoute($this->t('delete'), 'visualn_embed.drawing_controller_delete', ['id' => $drawing_id], ['attributes' => ['class' => ['use-ajax']]]);
        }

        $drawing_entities_list[$drawing_entity->id()] = [
          'name' => $drawing_entity->label(),
          'id' => $drawing_entity->id(),
          'thumbnail_path' => $drawing_type_thumbnails[$drawing_entity->bundle()],
          'preview_link' => $preview_link,
          'edit_link' => $edit_link,
          'delete_link' => $delete_link,
        ];
      }
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


    $form['drawing_id'] = [
      '#type' => 'drawing_radios',
      '#options' => $drawing_entities_list,
      '#empty' => $this->t('No drawings found'),
    ];

    // preselect current drawing if set (user selected embedded drawing)
    if ($selected_drawing_id) {
      $form['drawing_id']['#default_value'] = $selected_drawing_id;
    }

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



    // @todo: here all downstream dialogs are opened in a simple dialog
    //   but not in a modal dialog for two reasons:
    //   * it doesn't work ...
    //   * for UX reasons

    $links = [];
    // @todo: EntityManager::getBundleInfo() deprecated
    $drawing_bundles = \Drupal::entityManager()->getBundleInfo('visualn_drawing');
    // get drawing type thumbnails
    $drawing_type_thumbnails = static::getDrawingTypesThumbnails();
    foreach ($drawing_bundles as $key => $drawing_bundle) {
      $title = [
        '#theme' => 'visualn_embed_new_drawing_type_select_item_label',
        '#name' => $drawing_bundle['label'],
        '#id' => $key,
        '#thumbnail_path' => $drawing_type_thumbnails[$key],
      ];

      // see https://www.drupal.org/node/1989646
      $links['link_'.$key] = [
        'title' => $title,
        'url' => Url::fromRoute('visualn_embed.new_drawing_controller_build', ['type' => $key]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-options' => json_encode([
            'target' => 'new-drawing-dialog',
            'classes' => ['ui-dialog' => 'ui-dialog-visualn'],
            'modal' => TRUE,
          ]),
        ],
      ];
    }

    // prepare dialog content
    $content = [
      '#theme' => 'visualn_embed_new_drawing_type_select_links',
      '#items' => [
        '#theme' => 'links',
        '#links' => $links,
      ],
    ];

    // @todo: some outline appering around the modal and on the left when not using CloseModalDialogCommand
    //   though it shouldn't be required, maybe because of focus
    //$response->addCommand(new CloseModalDialogCommand());


    $response->addCommand(new OpenDialogCommand('#new-drawing-dialog',
      $this->t('Choose drawing type'), $content,
      ['classes' => ['ui-dialog' => 'ui-dialog-visualn'], 'modal' => TRUE]));

    return $response;
  }

  // @todo: move into drawing entity type class (or manager class)
  public static function getDrawingTypesThumbnails() {
    $drawing_type_thumbnails = [];
    // @todo: maybe move default thumbnail path into a constant
    // use default drawing type thumbnail
    $default_thumbnail = drupal_get_path('module', 'visualn_drawing') . '/images/drawing-thumbnail-default.png';
    $drawing_types  = \Drupal::entityTypeManager()->getStorage('visualn_drawing_type')->loadMultiple();
    foreach ($drawing_types as $drawing_type) {
      $thumbnail_path = !empty($drawing_type->get('thumbnail_path')) ? $drawing_type->get('thumbnail_path') : $default_thumbnail;
      $drawing_type_thumbnails[$drawing_type->id()] = file_url_transform_relative(file_create_url($thumbnail_path));
    }

    return $drawing_type_thumbnails;
  }

}
