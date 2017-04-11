<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining VisualN style entities.
 */
interface VisualNStyleInterface extends ConfigEntityInterface {

  /**
   * Get VisualN style drawer Id.
   *
   * @return string $drawer_id
   */
  public function getDrawerId();

  /**
   * Get VisualN style drawer configuration.
   *
   * @return array $drawer_config
   */
  public function getDrawerConfig();

  /**
   * Set drawer plugin configuration for VisualN style.
   *
   * @param array $drawer_config
   *
   * @return $this
   */
  public function setDrawerConfig($drawer_config);

}
