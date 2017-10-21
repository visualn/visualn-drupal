<?php

/**
 * @file
 *
 * Conatins DefaultDrawerWrapper used for subdrawers. Its primary purpose is to allow modifiers
 * to modify base drawer behaviour.
 */

namespace Drupal\visualn\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerWrapperBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Default Drawer Wrapper' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_default_drawer_wrapper",
 *  label = @Translation("Default Drawer Wrapper"),
 *  role = "wrapper"
 * )
 */
// @todo: !IMPORTANT: drawer should always be actualized with every new change to the interface and base class
//    since it must include all the methods and wrapper around them to delegate to the subdrawer_base_drawer object.
class DefaultDrawerWrapper extends VisualNDrawerWrapperBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_values = $this->subdrawer_base_drawer->defaultConfiguration();

    // Modify drawer default configuration

    if (!empty($this->methods_modifiers_substitutions['defaultConfiguration']['after'])) {
      foreach ($this->methods_modifiers_substitutions['defaultConfiguration']['after'] as $uuid => $substitution_name) {
        //dsm($uuid . ' => ' . $substitution_name);
        $default_values = $this->modifiers[$uuid]->{$substitution_name}($default_values);
      }
    }

    return $default_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    // @todo: the method may be used internally in drawer plugins methods
    $configuration = $this->subdrawer_base_drawer->getConfiguration();

    if (!empty($this->methods_modifiers_substitutions['getConfiguration']['after'])) {
      foreach ($this->methods_modifiers_substitutions['getConfiguration']['after'] as $uuid => $substitution_name) {
        //dsm($uuid . ' => ' . $substitution_name);
        $configuration = $this->modifiers[$uuid]->{$substitution_name}($configuration);
      }
    }

    return $configuration;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, array $options = []) {
    $this->subdrawer_base_drawer->prepareBuild($build, $vuid, $options);


    // @todo: do override here for getConfiguration() because in other places it is used internally

    // @todo: use getConfiguration() instead?
    $drawer_config =  $this->subdrawer_base_drawer->configuration + $this->subdrawer_base_drawer->defaultConfiguration();

    // @todo: we can't override prepareConfig directly since it is used internally inside the prepareBuild() method
    $this->prepareJSCofig($drawer_config);


    //$this->subdrawer_base_drawer->prepareJSCofig($drawer_config);
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['config'] = $drawer_config;
  }

  /**
   * {@inheritdoc}
   */
  // @todo: there is a typo in prepareJSConfig method name
  public function prepareJSCofig(array &$drawer_config) {
    $original_drawer_config = $drawer_config;
    $this->subdrawer_base_drawer->prepareJSCofig($drawer_config);

    if (!empty($this->methods_modifiers_substitutions['prepareJSCofig']['after'])) {
      foreach ($this->methods_modifiers_substitutions['prepareJSCofig']['after'] as $uuid => $substitution_name) {
        //dsm($uuid . ' => ' . $substitution_name);
        $this->modifiers[$uuid]->{$substitution_name}($drawer_config, $original_drawer_config);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: since modifiers may modify getConfiguration(), which one should be here?
    $drawer_config = $this->subdrawer_base_drawer->getConfiguration();
    $form = $this->subdrawer_base_drawer->buildConfigurationForm($form, $form_state);

    // Modify drawer configuration form

    if (!empty($this->methods_modifiers_substitutions['buildConfigurationForm']['after'])) {
      foreach ($this->methods_modifiers_substitutions['buildConfigurationForm']['after'] as $uuid => $substitution_name) {
        // @todo: maybe set a reference in drawer modifier to the original base_drawer (base drawer ?)
        //    to not pass drawer_config every time into arguments
        //    though there are security concerns in case of using third-party modifiers
        //dsm($uuid . ' => ' . $substitution_name);
        $form = $this->modifiers[$uuid]->{$substitution_name}($form, $form_state, $drawer_config);
      }
    }

    return $form;
  }

}
