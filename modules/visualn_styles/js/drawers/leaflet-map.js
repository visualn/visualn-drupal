(function ($, Drupal, L) {
  Drupal.visualnData.drawers.visualnLefletMapDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];
    var data = drawing.adapter.responseData;
    var html_selector = drawing.html_selector;

    var center_lat = drawing.drawer.config.center_lat;
    var center_lon = drawing.drawer.config.center_lon;
    // set center as a midpoint of all points (if not empty)
    var calculate_center = drawing.drawer.config.calculate_center;

    var locations = data;

    var leaflet_map_id = html_selector + '--leaflet-map';
    $('.' + html_selector).append('<div id="' + leaflet_map_id + '" style="height: 300px;"></div>');

    if (calculate_center && locations.length) {
      var points = [];
      for (var i = 0; i < locations.length; i++) {
        points.push([locations[i].lat,locations[i].lon]);
      }
      var bounds = new L.LatLngBounds(points);
      var centerLatLon = bounds.getCenter();
      var map = L.map(leaflet_map_id).setView([centerLatLon.lat, centerLatLon.lng], 8);
      map.fitBounds(bounds);
    }
    else {
      // @todo: get default zoom from settings
      var map = L.map(leaflet_map_id).setView([center_lat, center_lon], 8);
    }



    L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // add markers to the map
    for (var i = 0; i < locations.length; i++) {
      marker = new L.marker([locations[i].lat,locations[i].lon])
        .bindPopup(locations[i].title)
        .addTo(map);
    }

  };
})(jQuery, Drupal, L);
