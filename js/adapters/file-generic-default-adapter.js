// @todo: maybe rename the file (to comply library name) or the library itself
(function ($, Drupal, d3, xml2json) {
  Drupal.visualnData.adapters.visualnFileGenericDefaultAdapter = function(drawings, vuid, managerCallback) {
    var fileType = drawings[vuid].adapter.fileType;

    if (typeof(fileType) == 'undefined' || fileType == '') {
      // @todo: set a warning and return empty data (or false or some kind of result code)
    }
    else {
      // @todo: check if file type is supported (or maybe the should be done by adapter php class)
      // @todo: process request errors if any
      // @todo: use parsing function depending on fileTyle
      //var mimeType = '';
      switch (fileType) {
        case 'csv' :
          var sourceD3 = d3.csv(drawings[vuid].adapter.fileUrl, function(error, data) {
            // pass data to manager callback when request successfully finished
            managerCallback(data);
          });
          break;
        case 'tsv' :
          //mimeType = 'text/tab-separated-values';
          var sourceD3 = d3.tsv(drawings[vuid].adapter.fileUrl, function(error, data) {
            // pass data to manager callback when request successfully finished
            managerCallback(data);
          });
          break;
        // @todo: currently this adapter should always load xml2json library
        //    even if it is not used (e.g. when csv is processed)
        case 'xml' :
          //mimeType = 'text/xml';
          var sourceD3 = d3.xml(drawings[vuid].adapter.fileUrl, function(error, data) {
            // xml parsing returns an xml object
            var jsonData = xml2json(data, "");
            jsonData = JSON.parse(jsonData);
            // @todo: wrapper can be other than "element". maybe make adapter configurable
            data = jsonData['root']['element'];
            // pass data to manager callback when request successfully finished
            managerCallback(data);
          });
        case 'json' :
          //mimeType = 'application/json';
          var sourceD3 = d3.json(drawings[vuid].adapter.fileUrl, function(error, data) {
            // pass data to manager callback when request successfully finished
            managerCallback(data);
          });
          break;
      }
      //if (mimeType != '') {
        //var requestCallback = function(error, data) {
          //// pass data to manager callback when request successfully finished
          //managerCallback(data);
        //};
        //d3.request(drawings[vuid].adapter.fileUrl)
            //.mimeType("text/tab-separated-values")
            //.response(function(xhr) { return d3.tsvParse(xhr.responseText, row); })
            ////.response()
            //.get(requestCallback);
      //}
    }
    // @todo: return result code or some other usefull info
    //return sourceD3;
  };

})(jQuery, Drupal, d3, xml2json);

