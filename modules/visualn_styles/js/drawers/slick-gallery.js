(function ($, Drupal) {
  Drupal.visualnData.drawers.visualnSlickGalleryDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];
    var data = drawing.adapter.responseData;
    var html_selector = drawing.html_selector;
    var slick_setup = drawing.drawer.config.slick_setup;

    var slick_content = '';

    // @todo: add wrapper class to attach styles
    data.forEach(function(d){
      slick_content += '<div><img src="' + d.url + '" /></div>';
    });

    var slick_id = html_selector + '--slick-id';
    $('.' + html_selector).append('<div id="' + slick_id + '">' + slick_content + '</div>');

    // all settings are obtained from drawer configuration
    $('#' + slick_id).slick(
      slick_setup
    );

  };

})(jQuery, Drupal);
