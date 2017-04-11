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
    $visualn_style_id = $options['style_id'];
    $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
    if (empty($visualn_style)) {
      return;
    }

    $chain = $this->composePluginsChain($visualn_style, $options);
    // processPluginsChain()

    $drawer_plugin = $chain['drawer'][0];

    // options contain vuid (which is required) and also other plugins (adapter, mapper) settings in case drawer
    // needs them
    $drawer_plugin->prepareBuild($build, $options);

    $drawer_js_id = $drawer_plugin->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['drawerId'] = $drawer_js_id;
    $build['#attached']['drupalSettings']['visualn']['handlerItems']['drawings'][$drawer_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference

    // this can be required by mappers (e.g. basic_tree_mapper)
    $options['data_keys_structure'] = $drawer_plugin->dataKeysStructure();

    // @todo: use foreach()
    if (!empty($chain['adapter'])) {
      $adapter_plugin = $chain['adapter'][0];
      $adapter_plugin->prepareBuild($build, $options);

      $adapter_js_id = $adapter_plugin->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
      $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['adapter']['adapterId'] = $adapter_js_id;
      $build['#attached']['drupalSettings']['visualn']['handlerItems']['adapters'][$adapter_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference
    }

    // @todo: use foreach()
    if (!empty($chain['mapper'])) {
      $mapper_plugin = $chain['mapper'][0];
      $mapper_plugin->prepareBuild($build, $options);

      $mapper_js_id = $mapper_plugin->jsId();  // defaults to plugin id if not overriden in drawer plugin class.
      $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['mapper']['mapperId'] = $mapper_js_id;
      $build['#attached']['drupalSettings']['visualn']['handlerItems']['mappers'][$mapper_js_id][$vuid] = $vuid;  // @todo: this settings is just for reference
    }

    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['html_selector'] = $options['html_selector'];
    // Attach visualn manager js script.
    // @todo: move this to a method in a base abstract class
    $build['#attached']['library'][] = 'visualn/visualn-manager';
  }

  /**
   * @inheritdoc
   *
   * @todo: move to interface
   * @todo: and maybe rename
   * @todo: remove options from arguments
   */
  public function composePluginsChain(VisualNStyle $visualn_style, array $options) {
    $chain = [
      'drawer' => [],
      'mapper' => [],
      'adapter' => [],
    ];
    // Apply style drawer to the view output.
    $drawer_plugin_id = $visualn_style->getDrawerId();
    // pass final config to drawer plugin to attach required js properties to the form and maybe make some
    // other changes to the resulting element markup
    $drawer_config = $options['drawer_config'];
    $drawer_plugin = $this->visualNDrawerManager->createInstance($drawer_plugin_id, $drawer_config);
    $chain['drawer'][] = $drawer_plugin;


    // @todo: add a hook so that new adapter groups could be registered or altered (e.g. group default adapter)
    $adapter_group = $options['adapter_group'];
    if ($adapter_group) {
      $adapter_plugin_id = '';
      // @todo: there should be a 'none' or FALSE/Null option if drawer doesn't use an adapter
      switch ($adapter_group) {
        case 'html_views' :
          $adapter_plugin_id = 'visualn_html_views_default'; // default adapter for "html_views" adapter group
          break;
        case 'file_dsv' : // delimiter separated values file
          $adapter_plugin_id = 'visualn_file_generic_default'; // default adapter for "file_csv" adapter group
          break;
      }

      if ($adapter_plugin_id) {
        $adapter_config = [];  // @todo:
        $adapter_plugin = $this->visualNAdapterManager->createInstance($adapter_plugin_id, $adapter_config);
        $chain['adapter'][] = $adapter_plugin;

        // get mapper for drawer-adapter pair
        $input = $drawer_plugin->getInfo()['input'];
        $output = $adapter_plugin->getInfo()['output'];
        if ($input == $output) {
          $mapper_plugin_id = '';
        }
        else {
          // @todo: mapper depends on input and output
          $mapper_plugin_id = 'visualn_default';
          // @todo: get mappers chain
          $mappers_chain = $this->buildMappersChain($output, $input);
          if (!empty($mappers_chain)) {
            // @todo: actually all the chain should be used
            $mapper_plugin_id = $mappers_chain[0];
          }
          else {
            // @todo: this is supposed to be an error
            $mapper_plugin_id = '';
          }
        }
        // we don't need a mapper if adapter isn't used
        if (!empty($mapper_plugin_id)) {
          // @todo: check if config is needed
          $mapper_config = [];
          //$mapper_plugin_id = 'visualn_default';
          $mapper_plugin = $this->visualNMapperManager->createInstance($mapper_plugin_id, $mapper_config);
          $chain['mapper'][] = $mapper_plugin;
        }
      }

    }
    return $chain;
  }

  /**
   * @inheritdoc
   *
   * @todo: move to interface
   * @todo: or buildMappersChain()
   */
  public function buildMappersChain($output, $input) {
    // @todo:
    $chain = [];
    if ($input == $output) {
      // @todo:
    }
    elseif($input == 'visualn_generic_input' && $output == 'visualn_generic_output') {
      $chain[] = 'visualn_default';
    }
    elseif($input == 'visualn_basic_tree_input' && $output == 'visualn_generic_output') {
      $chain[] = 'visualn_basic_tree';
    }
    return $chain;
  }

}
