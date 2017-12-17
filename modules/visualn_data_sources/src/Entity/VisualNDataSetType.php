<?php

namespace Drupal\visualn_data_sources\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the VisualN Data Set type entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_data_set_type",
 *   label = @Translation("VisualN Data Set type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_data_sources\VisualNDataSetTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn_data_sources\Form\VisualNDataSetTypeForm",
 *       "edit" = "Drupal\visualn_data_sources\Form\VisualNDataSetTypeForm",
 *       "delete" = "Drupal\visualn_data_sources\Form\VisualNDataSetTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_data_sources\VisualNDataSetTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_data_set_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "visualn_data_set",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "resource_provider_field" = "resource_provider_field"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/visualn/data-set-types/manage/{visualn_data_set_type}",
 *     "add-form" = "/admin/config/media/visualn/data-set-types/add",
 *     "edit-form" = "/admin/config/media/visualn/data-set-types/manage/{visualn_data_set_type}/edit",
 *     "delete-form" = "/admin/config/media/visualn/data-set-types/manage/{visualn_data_set_type}/delete",
 *     "collection" = "/admin/config/media/visualn/data-set-types"
 *   }
 * )
 */
class VisualNDataSetType extends ConfigEntityBundleBase implements VisualNDataSetTypeInterface {

  /**
   * The VisualN Data Set type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Data Set type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN Resource Provider field ID.
   *
   * @var string
   */
  protected $resource_provider_field;

  /**
   * {@inheritdoc}
   */
  public function getResourceProviderField() {
    return $this->resource_provider_field;
  }

}
