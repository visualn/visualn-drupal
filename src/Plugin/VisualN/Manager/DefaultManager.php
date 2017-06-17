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
  public function prepareBuild(array &$build, $options = []) {
    // @todo: visualn-core.js should be attached before other visualn js scripts (drawers, mappers, adapters, managers)
    // @todo: move into base class or even into dependencies for manager js script and attach it here instead of end of method function
    $build['#attached']['library'][] = 'visualn/visualn-core';
    $vuid = $options['vuid'];
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid] = [];
    $manager_id = 'visualnDefaultManager';
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['managers'][$manager_id][$vuid] = $vuid;  // @todo: this settings is just for reference

    // required options: style_id, vuid, html_selector
    // add optional options
    $options += [
      'adapter_group' => '',  // optional (drawer can perform adapter functionality by itself)
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

    // @todo: maybe create an intermediary "drawing info" object and pass to chaing builder
    // @todo: there may be different input_options required for different adapters (and other plugin types)
    // @todo: do we have chain_plugins_configs here? i.e. in case chain is built for the first time
    //    is chain stored anywhere (in config settings)?
    $drawer = $this->visualNDrawerManager->createInstance($visualn_style->getDrawerId(), $options['drawer_config']);

    //$chain = $this->composePluginsChain($drawer, $input_type, $input_data);
    $chain = $this->composePluginsChain($drawer, $options['adapter_group'], []); // $drawer, $input_type, $input_options

    // there could be now drawer after composing chain
    if (empty($chain['drawer'])) {
      return;
    }

    // generally this should be the same drawer as passed into composerPluginsChain()
    $drawer = $chain['drawer'][0];

    // options contain vuid (which is required) and also other plugins (adapter, mapper) settings in case drawer
    // needs them
    // @todo: pass $vuid as an argument to ::prepareBuild()
    $drawer->prepareBuild($build, $options);

    // this can be required by mappers (e.g. basic_tree_mapper)
    $options['data_keys_structure'] = $drawer->dataKeysStructure();

    // @todo: also include 'drawer' into array_merge()
    //    and decide which order is correct and if it matters at all
    // $chain = array_merge($chain['drawer'], $chain['adapter'], $chain['mapper']);

    // @todo: so there should be a special object that collects data from each plugin (e.g. for data_keys_structure)
    $chain = array_merge($chain['adapter'], $chain['mapper']);
    // generally there is one plugin of a kind
    foreach ($chain as $chain_plugin) {
      $chain_plugin->prepareBuild($build, $options);
    }

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
    $chain = ['drawer' => [], 'mapper' => [], 'adapter' => []];

    $chain['drawer'][] = $drawer;
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
