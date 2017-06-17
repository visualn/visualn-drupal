<?php

namespace Drupal\visualn\Plugin\VisualN\Mapper;

use Drupal\visualn\Plugin\VisualNMapperBase;

/**
 * Provides a 'Default Mapper' VisualN mapper.
 *
 * @VisualNMapper(
 *  id = "visualn_default",
 *  label = @Translation("Default Mapper"),
 *  input =  "visualn_generic_output",
 *  output =  "visualn_generic_input",
 * )
 */
class DefaultMapper extends VisualNMapperBase {

  /**
   * {@inheritdoc}
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);

    // mapper specific js settings
    $dataKeysMap = $options['drawer_fields'];  // here need both keys and values for remapping values
    // @todo: exclude this settings for views
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['dataKeysMap'] = $dataKeysMap;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/default-mapper';
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnDefaultMapper';
  }

}
