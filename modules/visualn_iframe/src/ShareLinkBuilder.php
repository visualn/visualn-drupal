<?php

// @todo: maybe implement as a service

namespace Drupal\visualn_iframe;

class ShareLinkBuilder {

  // @todo: check visualn_iframe.install schema. maybe add $configuration
  //    into arguments along with options (currently configuration is stored in options)
  public function createIframeDbRecord($key, $options, $hash) {
    // if $hash is empty, generate a new one
    if (empty($hash)) {
      // generate a new hash
      $hash = substr(\Drupal::service('uuid')->generate(), 0, 8);
    }

    // if record with hash exists, update else create a record
    \Drupal::database()->merge('visualn_iframes_data')
      ->insertFields(array(
          'hash' => $hash,
          'handler_key' => $key,
          // @todo: wouldn't it be serialized automatically (see below too)?
          'options' => serialize($options),
      ))
      ->updateFields(array(
          'handler_key' => $key,
          'options' => serialize($options),
      ))
      ->key(array('hash' => $hash))
      ->execute();


    return $hash;
  }

  public function getRecord($hash) {
    $iframe_record = \Drupal::database()->select('visualn_iframes_data', 'v')
      ->fields('v', ['handler_key', 'options'])
      ->condition('v.hash', $hash)
      ->range(0, 1)
    ->execute()->fetchAssoc();

    return $iframe_record;
  }

  public function buildIframeUrl($key, $options) {
    // @todo:

    // return emcoded key and options
  }

  public function getIframeUrl($hash) {
    // @todo: get url by hash, if not empty
    //return 'some url';
    return 'http://vzn.drupalbase.ru/visualn-iframe/embed/' . $hash;
  }

  public function buildLink($iframe_url) {
    $build = [];
    // generate link uid
    $link_uid = 'link-uid-' .  substr(\Drupal::service('uuid')->generate(), 0, 5);
    // @todo: maybe use ajax callback instead of link click hander
    $build['#markup'] = "<div class='visualn-iframe-share-link'><a href='' rel='".$link_uid."'>Share</a></div>";
    // @todo: attach js script for the Share link
    $build['#attached']['library'][] = 'visualn_iframe/visualn-iframe-share-link';
    // @todo: generate and #ajax url or even share link url (as a temporary solution) to the script
    $build['#attached']['drupalSettings']['visualn_iframe']['share_iframe_links'][$link_uid] = $iframe_url;

    return $build;
  }

}
