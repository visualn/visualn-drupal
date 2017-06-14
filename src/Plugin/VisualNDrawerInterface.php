<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\Entity\VisualNStyleInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for VisualN Drawer plugins.
 */
interface VisualNDrawerInterface extends VisualNPluginInterface {

  /**
   * Modify drawer_config before attaching to js settings.
   * Can be used to translate strings etc.
   *
   * @param array $drawer_config
   */
  public function prepareJSCofig(array &$drawer_config);

  /**
   * Get Drawer default configuration for config form.
   *
   * @return array $config
   */
  public function getDefaultConfig();

  /**
   * Get Drawer configuration form array.
   *
   * @param array $configuration
   *
   * @return array $form
   */
  public function getConfigForm(array $configuration = []);

  /**
   * Extract configuration form values to map into VisualNStyle entity config.
   *
   * @param FormStateInterface $form_state
   *
   * @param  array $element_parents
   *
   * @return array $drawer_config_values
   */
  public function extractConfigFormValues(FormStateInterface $form_state, array $element_parents);

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

  /**
   * Get drawer plugin info. Includes data input type etc.
   *
   * @return array $drawer_info
   */
  public function getInfo();

}
