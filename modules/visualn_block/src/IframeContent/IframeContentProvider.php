<?php

//namespace Drupal\visualn_iframe\Theme;
namespace Drupal\visualn_block\IframeContent;

use Drupal\visualn_iframe\ContentProvider\ContentProviderInterface;
use Drupal\block\Entity\Block;
//use Drupal\Core\Routing\RouteMatchInterface;
//use Drupal\Core\Theme\ThemeNegotiatorInterface;
//use Symfony\Component\Routing\Route;

/**
 * Class IframeContentProvider.
 */
// @todo: rename to BlockIframeContentProvider or smth other
class IframeContentProvider implements ContentProviderInterface {

  /**
   * {@inheritdoc}
   */
  //public function applies(RouteMatchInterface $route_match) {
    //$route = $route_match->getRouteObject();
    //if (!$route instanceof Route) {
      //return FALSE;
    //}
    //$option = $route->getOption('_custom_theme');
    //if (!$option) {
      //return FALSE;
    //}

    //return $option == 'stable';
  //}
  public function applies($record_key, $options) {
    $configuration = $options;
    // @todo: move record_key into class property
    //dsm(time());
    // @todo: check configuration here (if needed)
    // @todo: maybe set record_key in .services.yml file as it is done for ThemeNegotiator
    //    so that other modules content providers could use the current IframeContentProvider class
    if ($record_key == 'visualn_block_key' && !empty($configuration)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function provideContent($record_key, $options) {
    // @todo: keep in mind that this configuration for the block, not drawer
    $configuration = $options;
    $block_id = $configuration['id'];
    $block_manager = \Drupal::service('plugin.manager.block');
    // @todo: get block config
    //$plugin_block = $block_manager->createInstance('visualn_block', $configuration);
    $plugin_block = $block_manager->createInstance($block_id, $configuration);
    // Some blocks might implement access check.
    $access_result = $plugin_block->access(\Drupal::currentUser());
    // Return empty render array if user doesn't have access.
    // $access_result can be boolean or an AccessResult class
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      // You might need to add some cache tags/contexts.
      return [];
    }
    $render = $plugin_block->build();
    // do not add share link for embedded content
    unset($render['share_iframe_link']);
    return $render;
  }
}
