<?php

/**
 * @file
 * Conatins VimeoStandaloneDrawer class
 */

namespace Drupal\visualn_styles\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Vimeo Standalone' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_vimeo_standalone",
 *  label = @Translation("Vimeo (stadalone)"),
 * )
 */
class VimeoStandaloneDrawer extends VisualNDrawerBase {

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach iframe markup directly without using js

    // @todo: a template could be used to generate html markup for the vimeo iframe
    if ($this->configuration['video_id']) {
      $video_id = $this->configuration['video_id'];
      $build['vimeo_markup'] = [
        '#markup' => '<iframe src="https://player.vimeo.com/video/' . $video_id . '" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>',
        '#allowed_tags' => ['iframe'],
      ];
    }

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    // @todo: maybe add "Placeholder for URL" setting as in YouTube Drupal module
    $default_config = [
      'video_id' => '',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['video_id'] = [
      '#type' => 'textfield',
      '#title' => t('Vimeo video id'),
      '#default_value' => $this->configuration['video_id'],
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    // js not needed here
    return '';
  }

}
