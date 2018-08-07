<?php

namespace Drupal\visualn;

use Drupal\visualn\Plugin\VisualNBuilderManager;
use Drupal\visualn\Plugin\VisualNDrawerManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\ResourceInterface;

//@todo: add visualn.drawing_builder (or visualn.builder) service (as renderer analogy)
//  and move services into service dependencies

/**
 * Class BuilderService.
 */
class BuilderService implements BuilderServiceInterface {

  /**
   * Drupal\visualn\Plugin\VisualNBuilderManager definition.
   *
   * @var \Drupal\visualn\Plugin\VisualNBuilderManager
   */
  protected $visualNBuilderManager;

  /**
   * Drupal\visualn\Plugin\VisualNDrawerManager definition.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * Constructs a new BuilderService object.
   */
  public function __construct(VisualNBuilderManager $plugin_manager_visualn_builder, VisualNDrawerManager $plugin_manager_visualn_drawer, EntityTypeManager $entity_type_manager) {
    $this->visualNBuilderManager = $plugin_manager_visualn_builder;
    $this->visualNDrawerManager = $plugin_manager_visualn_drawer;
    $this->visualNStyleStorage = $entity_type_manager->getStorage('visualn_style');
  }

  /**
   * Standard entry point to create drawings based on resource and configuration data.
   *
   * @todo: add docblock
   * @todo: add to the service interface
   */
  public function makeBuildByResource(ResourceInterface $resource, $visualn_style_id, array $drawer_config, array $drawer_fields, $base_drawer_id = '') {

    $build = [];

    // @todo: move builder plugin id discovery for the drawer into DefaultManager::prepareBuild()?

    if (!empty($visualn_style_id)) {
      // load style and get builder requested by drawer from drawer plugin definition
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin_id = $visualn_style->getDrawerPlugin()->getPluginId();
      $builder_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['builder'];
    }
    elseif (!empty($base_drawer_id)) {
      $drawer_plugin_id = $base_drawer_id;
      $builder_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['builder'];
    }
    else {
      return $build;
    }


    // generate vuid for the drawing
    $vuid = \Drupal::service('uuid')->generate();

    // generate html selector for the drawing (where to attach drawing selector)
    $html_selector = 'visualn-drawing--' . substr($vuid, 0, 8);

    // @todo: maybe use a template instead of attaching html_selector as prefix when build is ready
    //   or even attach it inside builder::prepareBuild() method
    // @todo: attributes dont render if there is nothing to render
    //$build['#attributes']['class'][] = $html_selector;
    $build['visualn_build_markup'] = ['#markup' => '<div class="' . $html_selector . '"></div>'];

    // get builder configuration, load builder plugin and prepare drawing build
    $builder_config = [
      'visualn_style_id' => $visualn_style_id,
      'drawer_config' => $drawer_config,
      'drawer_fields' => $drawer_fields,
      'html_selector' => $html_selector,
      // @todo: this was introduced later, for drawer preview page
      'base_drawer_id' => $base_drawer_id,
    ];

    $builder_plugin = $this->visualNBuilderManager->createInstance($builder_plugin_id, $builder_config);
    $builder_plugin->prepareBuild($build, $vuid, $resource);

    return $build;

  }

}