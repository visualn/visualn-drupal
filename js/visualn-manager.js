// @todo: rename the file into visualn-default-manager.js
(function ($, Drupal, d3) {
window.addEventListener('visualnCoreProcessed', function (e) {
  console.log(JSON.stringify(Drupal.visualnData));
});
  //Drupal.behaviors.visualnDefaultManagerBehaviour = {
    //attach: function (context, settings) {
window.addEventListener('visualnCoreProcessed', function (e) {
console.log('some test');
      // @todo: this seems to not work together with ajax
      // @todo: consider implementing updating events and
      // behaviours (e.g. depending on interactions or new data available in the source/resource)
      //$(context).find('body').once('visualn-manager').each(function () {
        // Register all graphs/visualizations which want to be managed by the manager
        // the settings about the graphs are provided by views style or field formatter (or other).
        // Then for each one the correponding Drawer is asked to register and provide info about its
        // dependencies and requirements (Mappers, Adapters, data sources).
        // Then each of them is processed in order.
        // A Drawer can do everything on it's own without registering to manager or even use a custom
        // manager with its custom arbitrary logic.

var settings = e.detail;
        console.log(Drupal.visualnData);
        console.log(settings.visualn.handlerItems);

        // @todo: object to array conversion would better be done before sending settings to browser
        var handlerItems = settings.visualn.handlerItems.managers.visualnDefaultManager;
        handlerItems = Object.keys(handlerItems).map(function (key) { return handlerItems[key]; });
        settings.visualn.handlerItems.managers.visualnDefaultManager = handlerItems;
        //var arr = Object.keys(obj).map(function (key) { return obj[key]; });
        // @todo: process all drawings, managed by the manager
        // @todo: use "vuid" instead of "value" in arguments for better readability
        $(settings.visualn.handlerItems.managers.visualnDefaultManager).each(function(index, value){
          console.log(Drupal.visualnData.drawings[value]);
          // drawing.drawer is considered to be always set, since there is no need to have a drawing w/o a drawer
          var drawing = Drupal.visualnData.drawings[value];
          // @todo: this is temporary solution to exclude drawers that don't use js,
          //    actually there should be no settings at all at clientside for such drawers
          if (typeof drawing.drawer == 'undefined') {
            return;
          }
          var drawerId = drawing.drawer.drawerId;
          if (typeof drawing.adapter != 'undefined') {
            var adapterId = drawing.adapter.adapterId;
            // @todo: maybe pass just a drawing or also a drawing
            // @todo: pass also a callback to run when adapter result is ready (e.g. for requesting urls)
            var callback = function(data){
              drawing.adapter.responseData = data;
              console.log(data);
              //console.log(drawing);
              console.log(drawerId);
              //var drawerId = 'visualnLineChartDrawerBehaviour';

              // apply mapper if any
              if (typeof drawing.mapper != 'undefined') {
                var mapperId = drawing.mapper.mapperId;
                Drupal.visualnData.mappers[mapperId](Drupal.visualnData.drawings, value);
              }

              // draw final image
              Drupal.visualnData.drawers[drawerId](Drupal.visualnData.drawings, value);
            };
            // @todo: in some cases we need to pass row conversion function (see https://github.com/d3/d3-request#tsv)
            //   which depends on a given drawer. so maybe give drawer a chance to make some tuning on adapter before request.
            //   But in this case adapter and drawer should use the same library (d3.js in our case).
            Drupal.visualnData.adapters[adapterId](Drupal.visualnData.drawings, value, callback);
          }
          else {
            // there is no use in mapper if adapter isn't used, because there is no response data to map

            Drupal.visualnData.drawers[drawerId](Drupal.visualnData.drawings, value);
          }

        });

      //});
});
    //}
  //};
})(jQuery, Drupal, d3);

