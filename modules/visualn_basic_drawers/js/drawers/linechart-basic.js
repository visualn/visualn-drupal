(function ($, Drupal, c3) {
  Drupal.visualnData.drawers.visualnLinechartBasicDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];
    var html_selector = drawing.html_selector;
    //$('.' + html_selector).append('<div width="960" height="500">');
    var data = drawing.adapter.responseData;
    var linechart_selector = '.' + html_selector;

    var columns = { x: ['x'], data1: ['data1'], data2: ['data2'] };
    data.forEach(function(row){
      columns.x.push(row['x']);
      columns.data1.push(row['data1']);
      columns.data2.push(row['data2']);
    });

    // @see https://c3js.org/samples/simple_xy.html
    var chart = c3.generate({
      bindto: linechart_selector,
      data: {
        x: 'x',
        columns: [
          columns.x,
          columns.data1,
          columns.data2,
          //['x', 30, 50, 100, 230, 300, 310],
          //['data1', 30, 200, 100, 400, 150, 250],
          //['data2', 130, 300, 200, 300, 250, 450]
        ]

      }
    });
  };

})(jQuery, Drupal, c3);
