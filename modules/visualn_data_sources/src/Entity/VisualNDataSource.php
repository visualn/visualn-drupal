<?php

namespace Drupal\visualn_data_sources\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VisualN Data Source entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_data_source",
 *   label = @Translation("VisualN Data Source"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_data_sources\VisualNDataSourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn_data_sources\Form\VisualNDataSourceForm",
 *       "edit" = "Drupal\visualn_data_sources\Form\VisualNDataSourceForm",
 *       "delete" = "Drupal\visualn_data_sources\Form\VisualNDataSourceDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_data_sources\VisualNDataSourceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_data_source",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/visualn/data-sources/manage/{visualn_data_source}",
 *     "add-form" = "/admin/config/media/visualn/data-sources/add",
 *     "edit-form" = "/admin/config/media/visualn/data-sources/manage/{visualn_data_source}/edit",
 *     "delete-form" = "/admin/config/media/visualn/data-sources/manage/{visualn_data_source}/delete",
 *     "collection" = "/admin/config/media/visualn/data-sources"
 *   }
 * )
 */
class VisualNDataSource extends ConfigEntityBase implements VisualNDataSourceInterface {

  /**
   * The VisualN Data Source ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Data Source label.
   *
   * @var string
   */
  protected $label;

}
