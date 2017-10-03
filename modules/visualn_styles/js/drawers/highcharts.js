(function ($, Drupal, Highcharts) {
  Drupal.visualnData.drawers.visualnHighchartsDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];
    var data = drawing.adapter.responseData;
    var html_selector = drawing.html_selector;
    var highcharts_setup = drawing.drawer.config.highcharts_setup;

    var highcharts_id = html_selector + '--highcharts-id';
    $('.' + html_selector).append('<div id="' + highcharts_id + '" style="height: 300px;"></div>');

    // all settings are obtained from drawer configuration except "series" setting, which corresponds to the input data
    highcharts_setup.series = data;

    var myChart = Highcharts.chart(highcharts_id, highcharts_setup);
    /*
    var myChart = Highcharts.chart(highcharts_id, {
        chart: {
            type: 'bar'
        },
        title: {
            text: 'Fruit Consumption'
        },
        xAxis: {
            categories: ['Apples', 'Bananas', 'Oranges']
        },
        yAxis: {
            title: {
                text: 'Fruit eaten'
            }
        },
        series: [{
            name: 'Jane',
            data: [1, 0, 4]
        }, {
            name: 'John',
            data: [5, 7, 3]
        }]
    });
    */

  };

})(jQuery, Drupal, Highcharts);
