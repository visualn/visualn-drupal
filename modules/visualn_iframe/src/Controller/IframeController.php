<?php

namespace Drupal\visualn_iframe\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\block\Entity\Block;
use Drupal\visualn_iframe\ShareLinkBuilder;

/**
 * Class IframeController.
 */
class IframeController extends ControllerBase {

  /**
   * Build.
   *
   * @return string
   *   Return content for the iframe.
   */
  public function build($hash) {

    // @todo: get corresponding record from the iframe hash info table
    //    if not found return empty content|message content|page not found 404 code.
    //    else
    //      get 'key' from the record and supplemental 'info' for the recored
    //      and call hook or even subscribers.
    //      traverse all subscribers.
    //      if subscriber found
    //        get content from subscriber
    //        if content found
    //          return content
    //        else (if content not returned by subscriber (e.g. subscriber checked options from info and didn't found coincidence)
    //          return empty|message|code
    //      else
    //        return empty|message|code

    // @todo: temporary: get key for the hash

    // @todo: decide how to store iframe records: plain table or content entities or smth else
    // @todo: decide if hash should be back deciphered (we could use site secret key for that), or even add a setting
    //    for user (site builder, admin) to decide how/if hashes should be ciphered.
    //    Plain hashes could even need no record in the database table (there should be some convention developed).
    $record_key = '';
    $options = [];

    // @todo: get key and options from database
    // @todo: maybe use a service
    $share_link_builder = new ShareLinkBuilder();
    $iframe_record = $share_link_builder->getRecord($hash);
    if (!empty($iframe_record)) {
      $record_key = $iframe_record['handler_key'];
      $configuration = unserialize($iframe_record['data']);

      // collect services (via tag), get responsibe iframe content provider
      // and get content if any
      $content_provider = \Drupal::service('visualn_iframe.content_provider');
      // @todo: this is somehow cached but is it proper behaviour?
      $options = $configuration;
      $render = $content_provider->provideContent($record_key, $options);
      // @todo: create DefaultContentProvider class (see DefaultNegotiator in case of ThemeNegotiator)
    }

    else {
      // @todo:
      $render = ['#markup' => 'record not found'];
    }

    return $render;
  }

}
