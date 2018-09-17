<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer;

use Drupal\visualn\Core\DrawerWithJsBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Slick Gallery' VisualN drawer.
 *
 * @ingroup drawer_plugins
 *
 * @VisualNDrawer(
 *  id = "visualn_slick_gallery_basic",
 *  label = @Translation("Slick Gallery Basic"),
 * )
 */
class SlickGalleryBasicDrawer extends DrawerWithJsBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'slide_content' => 'image_url',
      'controls_color' => '#42aaff',
      //'controls_color' => '#ffffff',
      'show_dots' => TRUE,
    ] + parent::defaultConfiguration();
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [
      'image_url' => t('Image URL'),
      'html' => t('HTML markup'),
      // @todo: what about svg ?
    ];
    $form['slide_content'] = [
      '#type' => 'radios',
      '#title' => t('Slide content'),
      '#options' => $options,
      '#default_value' => $this->configuration['slide_content'],
      '#required' => TRUE,
    ];
    $form['controls_color'] = [
      '#type' => 'color',
      '#title' => t('Controls color'),
      '#default_value' => $this->configuration['controls_color'],
      '#required' => TRUE,
    ];
    $form['show_dots'] = [
      '#type' => 'checkbox',
      '#title' => t('Show dots'),
      '#default_value' => $this->configuration['show_dots'],
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function prepareJsConfig(array &$drawer_config) {
    $drawer_config['show_dots'] = $drawer_config['show_dots'] ? TRUE : FALSE;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_basic_drawers/slick-gallery-basic-drawer';
    $build['#prefix'] = isset($build['#prefix']) ? $build['#prefix'] : '';
    $build['#suffix'] = isset($build['#suffix']) ? $build['#suffix'] : '';
    $build['#prefix'] .= $build['#prefix'] . '<div class="visualn-slick-gallery-basic-drawer-wrapper">';
    $build['#suffix'] .= '</div>' . $build['#suffix'];

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnSlickGalleryBasicDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'url',
      'html',
    ];

    return $data_keys;
  }

}
