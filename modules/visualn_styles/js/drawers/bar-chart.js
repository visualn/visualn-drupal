(function ($, Drupal, d3) {
  Drupal.visualnData.drawers.visualnBarChartDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];
    var data = drawing.adapter.responseData;
    var html_selector = drawing.html_selector;
    var y_axis_label = drawing.drawer.config.y_label;
    var y_axis_tick = drawing.drawer.config.y_axis_tick;

    var margin = {top: 40, right: 20, bottom: 30, left: 40},
        width = 960 - margin.left - margin.right,
        height = 500 - margin.top - margin.bottom;

    var formatPercent = d3.format(".0%");

    var x = d3.scale.ordinal()
        .rangeRoundBands([0, width], .1);

    var y = d3.scale.linear()
        .range([height, 0]);

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom");

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left");
    if (y_axis_tick == 'percent') {
      yAxis.tickFormat(formatPercent);
    }

    var tip = d3.tip()
      .attr('class', 'd3-tip')
      .offset([-10, 0])
      .html(function(d) {
        return "<strong>" + y_axis_label + ":</strong> <span style='color:red'>" + d.frequency + "</span>";
      })

    //var svg = d3.select("body").append("svg")
    var svg = d3.select('.' + html_selector).append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    svg.call(tip);

    //d3.tsv("data.tsv", type, function(error, data) {
      $(data).each(function(index, d){
        data[index].frequency = +d.frequency;
      });
      x.domain(data.map(function(d) { return d.letter; }));
      y.domain([0, d3.max(data, function(d) { return d.frequency; })]);

      svg.append("g")
          .attr("class", "x axis")
          .attr("transform", "translate(0," + height + ")")
          .call(xAxis);

      svg.append("g")
          .attr("class", "y axis")
          .call(yAxis)
        .append("text")
          .attr("transform", "rotate(-90)")
          .attr("y", 6)
          .attr("dy", ".71em")
          .style("text-anchor", "end")
          .text(y_axis_label);

      svg.selectAll(".bar")
          .data(data)
        .enter().append("rect")
          .attr("class", "bar")
          .attr("x", function(d) { return x(d.letter); })
          .attr("width", x.rangeBand())
          .attr("y", function(d) { return y(d.frequency); })
          .attr("height", function(d) { return height - y(d.frequency); })
          .on('mouseover', tip.show)
          .on('mouseout', tip.hide)

    //});

    function type(d) {
      d.frequency = +d.frequency;
      return d;
    }


  };

})(jQuery, Drupal, d3v3);
