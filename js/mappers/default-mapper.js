// @todo: maybe rename the file (to comply library name) or the library itself
(function ($, Drupal, d3) {
  Drupal.visualnData.mappers.visualnDefaultMapper = function(drawings, vuid) {

    var drawing = drawings[vuid];
    var data = drawing.adapter.responseData;

    // @todo: Array.prototype.filter() can be used instead

    // @todo: return if data is empty

    //console.log(data);
    //console.log(drawing.mapper);

    // @todo: check if needs remapping and remap if true
    // if those that are not empty, have the same key and value, there is no need in remapping
    // or if a special flag is set by adapter (or even a drawer), then don't do remapping also
    // also a flag can be set by the mapper itself if there was a chance to remap values while
    // adapter processing

    var keysMap = drawing.mapper.dataKeysMap;
    console.log(keysMap);

    var count = 0;
    var key;
    var newKeysMap = {};

    // get new keysMap with only non-empty values
    for (key in keysMap) {
      if (keysMap.hasOwnProperty(key)) {
        if (keysMap[key] != '' && keysMap[key] != key) {
          newKeysMap[key] = keysMap[key];
          count++;
        }
      }
    }

    // @todo: it is also possible to generate function code here (see basic-tree-mapper.js)

    // add mapping functionality (replace data keys)
    if (count) {
      // @todo:
      // foreach row in data replace keys
      // if a key already exists but it is used in remapping for another key (which is not recommeded),
      // create temporary value for that key
      console.log(newKeysMap);
      data.forEach( function (o) {
        //console.log(o);
        for (key in newKeysMap) {
          if (newKeysMap.hasOwnProperty(key)) {
            var oldKey = newKeysMap[key];
            var newKey = key;
            // http://stackoverflow.com/questions/4647817/javascript-object-rename-key
            if (oldKey !== newKey) {
              Object.defineProperty(o, newKey,
                Object.getOwnPropertyDescriptor(o, oldKey));
              delete o[oldKey];
            }
          }
        }
      });
    }
    console.log(data);

    // @todo: since drawers execute after mappers, there should be a way to set that special flag
    // to avoid remapping (or just explicitly disable mapper somewhere else before page rendering,
    // e.g. in manager, or in drawer prepareBuild() method)

  };
})(jQuery, Drupal, d3);

