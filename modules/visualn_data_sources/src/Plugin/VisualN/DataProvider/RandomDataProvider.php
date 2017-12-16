<?php

namespace Drupal\visualn_data_sources\Plugin\VisualN\DataProvider;

use Drupal\visualn_data_sources\Plugin\VisualNDataProviderBase;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;

/**
 * Provides a 'VisualN Random data provider' VisualN data provider.
 *
 * @VisualNDataProvider(
 *  id = "visualn_random_data",
 *  label = @Translation("VisualN Random data provider (*** DO NOT USE ***)"),
 * )
 */
//class RandomDataProvider extends VisualNDataProviderBase implements ContainerFactoryPluginInterface {
class RandomDataProvider extends VisualNDataProviderBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data_type' => '',
    ] + parent::defaultConfiguration();

 }

  // @todo: add to interface
  // @todo: maybe rename the method e.g. to attachDataProviderData() or smth else
  public function prepareBuild(&$build, $vuid, $options) {
  }

  // @todo: add to interface
  public function getOutputType() {
    // @todo: if here is an anknown output_type and chaing can't be build,
    //    all drawings on the page do not render (at least block drawings)
    return 'json_generic';
  }

  // @todo: add to interface
  // @todo: this method may be renamed to getOutputGateway() or getOutputAux() or smth.
  public function getOutputInterface() {
    $url = Url::fromRoute('visualn_data_sources.data_provider_controller_data',
      array('data_type' => $this->configuration['data_type'])
    )->setAbsolute()->toString();
    // @todo: build router or link for the data source
    // @todo: review option keys names
    return [
      'file_url' => $url,
      //'file_mimetype' => 'application/json',
    ];

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

