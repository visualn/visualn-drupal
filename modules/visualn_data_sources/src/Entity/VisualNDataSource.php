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
 *     "uuid" = "uuid",
 *     "data_provider_id" = "data_provider_id",
 *     "data_provider_config" = "data_provider_config"
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

  /**
   * The VisualN data provider ID.
   *
   * @var string
   */
  protected $data_provider_id;

  /**
   * The VisualN data provider config.
   *
   * @var array
   */
  protected $data_provider_config = [];

  /**
   * The VisualN source specific data provider plugin.
   *
   * @var \Drupal\visualn_data_sources\Plugin\VisualNDataProviderInterface
   */
  protected $data_provider_plugin;

  /**
   * {@inheritdoc}
   *
   * @todo: add description
   */
  public function getDataProviderId() {
    return $this->data_provider_id ?: '';
  }

  /**
   * {@inheritdoc}
   *
   * @todo: add description
   */
  public function getDataProviderPlugin() {
    if (!isset($this->data_provider_plugin)) {
      $data_provider_id = $this->getDataProviderId();
      if (!empty($data_provider_id)) {
        $data_provider_config = [];
        $data_provider_config = $this->getDataProviderConfig() + $data_provider_config;
        // @todo: load manager at object instantiation
        $this->data_provider_plugin = \Drupal::service('plugin.manager.visualn.data_provider')->createInstance($data_provider_id, $data_provider_config);
      }
    }

    return $this->data_provider_plugin;
  }


  /**
   * {@inheritdoc}
   */
  public function getDataProviderConfig() {
    return $this->data_provider_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataProviderConfig($data_provider_config) {
    $this->data_provider = $data_provider_config;
    return $this;
  }

}
