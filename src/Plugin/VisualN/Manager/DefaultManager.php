<?php

namespace Drupal\visualn\Plugin\VisualN\Manager;

use Drupal\visualn\Plugin\VisualNManagerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\visualn\Plugin\VisualNAdapterManager;
use Drupal\visualn\Plugin\VisualNMapperManager;
use Drupal\visualn\Entity\VisualNStyle;
use Drupal\visualn\Plugin\VisualNDrawerInterface;

/**
 * Provides a 'Default Manager' VisualN manager.
 *
 * @VisualNManager(
 *  id = "visualn_default",
 *  label = @Translation("Default Manager"),
 * )
 */
class DefaultManager extends VisualNManagerBase implements ContainerFactoryPluginInterface {

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * The visualn adapter manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNAdapterManager
   */
  protected $VisualNAdapterManager;

  /**
   * The visualn mapper manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNMapperManager
   */
  protected $VisualNMapperManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('plugin.manager.visualn.adapter'),
      $container->get('plugin.manager.visualn.mapper')
    );
  }

  /**
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, VisualNDrawerManager $visualn_drawer_manager, VisualNAdapterManager $visualn_adapter_manager, visualNMapperManager $visualn_mapper_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    //$this->definition = $plugin_definition + $configuration;
    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNAdapterManager = $visualn_adapter_manager;
    $this->visualNMapperManager = $visualn_mapper_manager;
  }

  /**
   * @inheritdoc
   *
   * @todo: add into interface and Base class
   * @todo: some or all options should be passed as part of manager_config (at least visualn_style_id)
   *  at plugin object instatiation
   */
  public function prepareBuild(array &$build, $vuid, $options = []) {
    // @todo: visualn-core.js should be attached before other visualn js scripts (drawers, mappers, adapters, managers)
    // @todo: move into base class or even into dependencies for manager js script and attach it here instead of end of method function
    $build['#attached']['library'][] = 'visualn/visualn-core';
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid] = [];
    $manager_id = 'visualnDefaultManager';
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['managers'][$manager_id][$vuid] = $vuid;  // @todo: this settings is just for reference

    // required options: style_id, html_selector
    // add optional options
    $options += [
      'output_type' => '',  // optional (drawer can perform adapter functionality by itself)
      'drawer_config' => [],  // optional (drawer default config is considered)
      'adapter_settings' => [],  // optional (in some cases, e.g. file_csv, it is needed to pass file url)
    ];
    // @todo: do we really need style_id here? maybe just pass drawer_plugin_id or both
    //  manager needs to know nothing about the visualn style
    $visualn_style_id = $options['style_id'];
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    if (empty($visualn_style)) {
      return;
    }

    // @todo: there may be different input_options required for different adapters (and other plugin types)
    // @todo: do we have chain_plugins_configs here? i.e. in case chain is built for the first time
    //    is chain stored anywhere (in config settings)?
    $drawer = $this->visualNDrawerManager->createInstance($visualn_style->getDrawerId(), $options['drawer_config']);

    //$chain = $this->composePluginsChain($drawer, $input_type, $input_data);
    $chain = $this->composePluginsChain($drawer, $options['output_type'], []); // $drawer, $input_type, $input_options

    // there could be now drawer after composing chain
    if (empty($chain['drawer'])) {
      return;
    }

    // generally this should be the same drawer as passed into composerPluginsChain()
    //$drawer = $chain['drawer'][0];

    // The $build['#visualn'] array collects data from each plugin (e.g. for data_keys_structure) since
    // it can be required by mappers (e.g. basic_tree_mapper) or adapters.
    // The info is attached to the $build array (instead of using a certain variable)
    // in case it could be required in some non-standard workflow or even anywhere outside VisualN process.
    $build['#visualn'] = [];

    // options contain other plugins (adapter, mapper) settings in case drawer needs them

    // First drawer plugins are called, so they could set data_keys_structure.
    // Then adapter plugins since they can provide some data for mappers.
    $plugin_types = ['drawer', 'adapter', 'mapper'];
    foreach ($plugin_types as $plugin_type) {
      // generally there is one plugin of each kind
      foreach ($chain[$plugin_type] as $k => $chain_plugin) {
        $input_options = [];
        if ($plugin_type == 'adapter' && $k == 0) {
          $input_options = [
            'adapter_settings' => $options['adapter_settings'] ?: [],
            'drawer_fields' => $options['drawer_fields'] ?: [],
          ];
        }
        elseif ($plugin_type == 'mapper' && $k == 0) {
          $input_options = [
            'data_keys_structure' => $build['#visualn']['chain_info']['drawer'][0]['data_keys_structure'],
            'drawer_fields' => $options['drawer_fields'] ?: [],
          ];
        }
        $chain_plugin->prepareBuild($build, $vuid, $input_options);
      }
    }

/*
    $chain = array_merge($chain['drawer'], $chain['adapter'], $chain['mapper']);
    foreach ($chain as $chain_plugin) {

      // @todo: Implement Linker plugin. Currently the login is implemented in the cycle above.
      //    Linkers would provide data required by specific plugin and would allow users to override default workflow
      //    for specific plugins. Also they make data used by each plugin transparent.
      //    Linkers would need a way to get plugin type, so a corresponding method should be implemented in the
      //    plugins base class.
      //    An example could be "$input_options = Linker($chain_plugin, $build['#visualn'], $options);"
      //

      $chain_plugin->prepareBuild($build, $vuid, $options);
    }
*/

    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['html_selector'] = $options['html_selector'];
    // Attach visualn manager js script.
    // @todo: move this to a method in a base abstract class
    $build['#attached']['library'][] = 'visualn/visualn-manager';
  }

  /**
   * @inheritdoc
   *
   * @todo: move to interface and maybe rename
   */
  protected function composePluginsChain(VisualNDrawerInterface $drawer, $input_type, array $input_options) {
    // The arrays are used to allow multiple plugins of each type in the chain
    // though generally this isn't used and wasn't tested. In most cases
    // this seems to have no sense (at least for drawer plugins).
    $chain = ['drawer' => [$drawer], 'mapper' => [], 'adapter' => []];

    $drawer_input = $drawer->getPluginDefinition()['input'];

    // get all adapter candidates
    $matched_adapters = [];
    $adapterDefinitions = $this->visualNAdapterManager->getDefinitions();
    foreach ($adapterDefinitions as $adapter_id => $definition) {
      if ($definition['input'] == $input_type) {
        $matched_adapters[$adapter_id] = $definition['output'];
      }
    }

    // get all mapper candidates
    $matched_mappers = [];
    $mapperDefinitions = $this->visualNMapperManager->getDefinitions();
    foreach ($mapperDefinitions as $mapper_id => $definition) {
      if ($definition['output'] == $drawer_input) {
        $matched_mappers[$mapper_id] = $definition['input'];
      }
    }

    // choose matching adapters and mappers for the chain
    $adapter_id = $mapper_id = NULL;
    $array_intersect = array_unique(array_values(array_intersect($matched_adapters, $matched_mappers)));
    if (!empty($array_intersect)) {
      // @todo: there should be some criteria to choose an optimal chain but not just the first matched
      $join_type = $array_intersect[0];
      $adapter_id  = array_search($join_type, $matched_adapters);
      $mapper_id = array_search($join_type, $matched_mappers);
      $chain['adapter'][] = $this->visualNAdapterManager->createInstance($adapter_id, []);
      $chain['mapper'][] = $this->visualNMapperManager->createInstance($mapper_id, []);
    }
    else {
      if (!empty($matched_adapters) || !empty($matched_mappers)) {
        // @todo: there is a question which one to choose
        //  here we may have two possibilities: an adapter or a mapper serves as both, adapter and mapper
        //  first check adapters
        $result_adapters = array_keys($matched_adapters, $drawer_input);
        if (!empty($result_adapters)) {
          $adapter_id = $result_adapters[0];
          $chain['adapter'][] = $this->visualNAdapterManager->createInstance($adapter_id, []);
        }
        else {
          $result_mappers = array_keys($matched_mappers, $drawer_input);
          if (!empty($result_mappers)) {
            $mapper_id = $result_mappers[0];
            $chain['mapper'][] = $this->visualNMapperManager->createInstance($mapper_id, []);
          }
        }
      }
    }

    // if source output is equal to drawer input (e.g. no need in mapper or adapter)
    // else empty the chain (no drawing will be drawn)
    if (empty($chain['adapter']) && empty($chain['mapper']) && $drawer_input != $input_type) {
      $chain = ['drawer' => [], 'mapper' => [], 'adapter' => []];
    }

    // @todo: cache chains

    return $chain;
  }

}
