<?php

namespace Drupal\visualn\Plugin\VisualN\Mapper;

use Drupal\visualn\Plugin\VisualNMapperBase;
use Drupal\visualn\ResourceInterface;

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
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // mapper specific js settings
    $dataKeysMap = $this->configuration['drawer_fields'];  // here need both keys and values for remapping values
    // @todo: exclude this settings for views
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['dataKeysMap'] = $dataKeysMap;
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/default-mapper';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnDefaultMapper';
  }

}
