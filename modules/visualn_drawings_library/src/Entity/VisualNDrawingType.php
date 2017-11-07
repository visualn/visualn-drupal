<?php

namespace Drupal\visualn_drawings_library\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the VisualN Drawing type entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_drawing_type",
 *   label = @Translation("VisualN Drawing type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_drawings_library\VisualNDrawingTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn_drawings_library\Form\VisualNDrawingTypeForm",
 *       "edit" = "Drupal\visualn_drawings_library\Form\VisualNDrawingTypeForm",
 *       "delete" = "Drupal\visualn_drawings_library\Form\VisualNDrawingTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_drawings_library\VisualNDrawingTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_drawing_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "visualn_drawing",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "drawing_fetcher_field" = "drawing_fetcher_field"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/visualn/drawing-types/{visualn_drawing_type}",
 *     "add-form" = "/admin/config/media/visualn/drawing-types/add",
 *     "edit-form" = "/admin/config/media/visualn/drawing-types/{visualn_drawing_type}/edit",
 *     "delete-form" = "/admin/config/media/visualn/drawing-types/{visualn_drawing_type}/delete",
 *     "collection" = "/admin/config/media/visualn/drawing-types"
 *   }
 * )
 */
class VisualNDrawingType extends ConfigEntityBundleBase implements VisualNDrawingTypeInterface {

  /**
   * The VisualN Drawing type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Drawing type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN Drawing Fetcher field ID.
   *
   * @var string
   */
  protected $drawing_fetcher_field;

  /**
   * {@inheritdoc}
   */
  public function getDrawingFetcherField() {
    return $this->drawing_fetcher_field;
  }

}
