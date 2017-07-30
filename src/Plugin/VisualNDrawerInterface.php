<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\Entity\VisualNStyleInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for VisualN Drawer plugins.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerBase
 */
interface VisualNDrawerInterface extends VisualNPluginInterface, PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Modify drawer_config before attaching to js settings.
   * Can be used to translate strings etc.
   *
   * @param array $drawer_config
   */
  public function prepareJSCofig(array &$drawer_config);

  /**
   * Extract configuration array values to map into VisualNStyle entity config.
   *
   * @param array $values
   *
   * @param  array $array_parents
   *
   * @return array $drawer_config_values
   */
  public function extractConfigArrayValues(array $values, array $array_parents);

  /**
   * Return a list of data keys used by the drawer script.
   *
   * @todo: think of returning a more complex data map then just and array of keys
   *
   * @return array $data_keys
   */
  public function dataKeys();

  /**
   * Return data keys used by mapper script.
   *
   * @return array $data_keys
   */
  public function dataKeysStructure();

}
