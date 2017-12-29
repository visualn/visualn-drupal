(function ($, Drupal, d3) {
  Drupal.visualnData.adapters.visualnHtmlViewsDefaultAdapter = function(drawings, vuid, managerCallback) {
    var drawing = drawings[vuid];
    var viewsContentWrapperSelector = drawing.adapter.viewsContentWrapperSelector;

    var sourceD3 = [];
    var classSuffix = '-' + drawing.adapter.dataClassSuffix;

    // var keys = ['date', 'close'];
    var keys = drawing.adapter.dataKeys;
    var keyMappings = {};
    $(keys).each(function(k, value){
      keyMappings[value] = "." + value + classSuffix;
    });
    //var keyMappings = { date : '.date' + classSuffix, close : '.close' + classSuffix };

    // @todo: generally this already does mapper functionality so mapper is not needed here
    //   at least if plain structure is considered (mapper could be required if data needs to be restructured)
    // @todo: do nothing or just run managerCallback if keyMappings is empty
    $("." + viewsContentWrapperSelector + " .view-content div.visualn-container").each(function(){
      var item = {};
      var container = this;
      $.each(keyMappings, function(key, elemClass){
        item[key] = $(container).find(elemClass).text();
      });
      //var item = { date : $(this).find('.date').text(), close : $(this).find('.close').text() };
      sourceD3.push(item);
    });

    // attach link to show/hide results
    // @todo: view-content should be hidden in twig template (exclude view preview case)
    $("." + viewsContentWrapperSelector + " .view-content").hide();
    var toggleResultsLink = $( "<div><a href=''>show results</a></div>" );
    toggleResultsLink.click(function(e){
      e.preventDefault();
      $("." + viewsContentWrapperSelector + " .view-content").toggle( "slow", function() {
        var linkText = $(this).is(':visible') ? 'hide results' : 'show results';
        toggleResultsLink.find('a').text(linkText);
      });
    });
    $("." + viewsContentWrapperSelector + " .view-content").before(toggleResultsLink);

    var data = sourceD3;
    managerCallback(data);
    return sourceD3;
  };

})(jQuery, Drupal, d3);

