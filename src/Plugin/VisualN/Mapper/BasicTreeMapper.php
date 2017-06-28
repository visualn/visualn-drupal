<?php

namespace Drupal\visualn\Plugin\VisualN\Mapper;

use Drupal\visualn\Plugin\VisualNMapperBase;

/**
 * Provides a 'Basic Tree Mapper' VisualN mapper.
 *
 * @VisualNMapper(
 *  id = "visualn_basic_tree",
 *  label = @Translation("Basic Tree Mapper"),
 *  input = "visualn_generic_output",
 *  output = "visualn_basic_tree_input",
 * )
 */
class BasicTreeMapper extends VisualNMapperBase {

  // used to build mapper plugins chain
  // @todo: find better terms here instead of input and output keys
  //    because this leads to misunderstanding
  // @todo: maybe this should return arrays (optional) to support multiple
  //    input and output format types (e.g. visualn_generic_input and visualn_generic_output for "output" key), or provide groups of formats structures somewhere else
  // @todo: e.g. visualn_plain -> visualn_plain with/without keys remapping/renaming

  /**
   * {@inheritdoc}
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $options);

    // mapper specific js settings
    $dataKeysMap = $options['drawer_fields'];  // here need both keys and values for remapping values
    $dataKeysStructure = $build['#visualn']['drawing_info']['data_keys_structure'];

    // process data keys structure to attach a cleaner settings tree to js
    $this->prepareJSKeysStructure($dataKeysStructure);

    // @todo: exclude this settings for views
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['dataKeysMap'] = $dataKeysMap;
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['dataKeysStructure'] = $dataKeysStructure;
    // @todo: attach dataKeysStructure
    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn/basic-tree-mapper';
  }

  /**
   * Prepare keys structure to attach to js settings.
   */
  protected function prepareJSKeysStructure(array &$dataKeysStructure) {
    foreach($dataKeysStructure as $k => $v) {
      if (is_array($v)) {
        if (!isset($v['mapping'])) {
          $dataKeysStructure[$k]['mapping'] = $k;
        }
        if (!isset($v['typeFunc'])) {
          $dataKeysStructure[$k]['typeFunc'] = '';
        }
        if (!isset($v['structure'])) {
          $dataKeysStructure[$k]['structure'] = [];
        }
        else {
          $this->prepareJSKeysStructure($dataKeysStructure[$k]['structure']);
        }
      }
      else {
        $dataKeysStructure[$k] = [
          'mapping' => $v,
          'structure' => [],
          'typeFunc' => '',
        ];
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnBasicTreeMapper';
  }

}
