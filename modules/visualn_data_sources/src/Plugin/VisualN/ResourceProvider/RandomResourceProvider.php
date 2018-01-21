<?php

namespace Drupal\visualn_data_sources\Plugin\VisualN\ResourceProvider;

use Drupal\visualn_data_sources\Plugin\VisualNResourceProviderBase;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\visualn\Helpers\VisualN;

/**
 * Provides a 'VisualN Random resource provider' VisualN resource provider.
 *
 * @VisualNResourceProvider(
 *  id = "visualn_random_data",
 *  label = @Translation("VisualN Random resource provider (*** DO NOT USE ***)"),
 * )
 */
//class RandomResourceProvider extends VisualNResourceProviderBase implements ContainerFactoryPluginInterface {
class RandomResourceProvider extends VisualNResourceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data_type' => '',
    ] + parent::defaultConfiguration();

 }

  // @todo: add to interface
  // @todo: maybe rename the method e.g. to attachResourceProviderData() or smth else
  public function prepareBuildByOptions(&$build, $vuid, $options) {
  }



  public function getResource() {
    // @todo: if here is an anknown output_type and chain can't be build,
    //    all drawings on the page do not render (at least block drawings)
    $output_type = 'json_generic';
    $url = Url::fromRoute('visualn_data_sources.resource_provider_controller_data',
      array('data_type' => $this->configuration['data_type'])
    )->setAbsolute()->toString();
    // @todo: build router or link for the data source
    // @todo: review option keys names

    $adapter_settings =  [
      'file_url' => $url,
      //'file_mimetype' => 'application/json',
    ];

    // @todo: load resource plugin
    $resource = VisualN::getResourceByOptions($output_type, $adapter_settings);

    return $resource;
  }



  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: AggregatorFeedBlock::blockForm() and others
    //    use $this->configuration[] without using $form_state values
    //    maybe form_state should be used only in case of having ajaxified elements
    //    inside configuration form
    // @todo: add extractFormValues() method
    //$configuration = $this->extractFormValues($form, $form_state);
    $configuration = $form_state->getValues();
    $configuration =  $configuration + $this->configuration;

    // @todo: add default settings
    $form['data_type'] = [
      '#type' => 'select',
      '#title' => t('Data type'),
      '#options' => ['leaflet' => 'Leaflet (title, lat, lon)'],
      '#default_value' => $configuration['data_type'],
      '#required' => TRUE,
      '#empty_option' => t('- Select Data Type -'),
      '#empty_value' => '',
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    //dsm('test submit');
    //dsm($form_state->getValues());
  }

}

