/**
 * Created by mmwebaze on 12/27/2016.
 */

(function ($) {
    'use strict';

    Drupal.behaviors.chartsC3 = {
        attach: function(context, settings) {
            var c3Chart = $('.charts-c3').attr('data-chart');
            var c3ChartData = JSON.parse(c3Chart);
            var formattedType = c3ChartData.chart.type;
            var c3ChartDataSeries = c3ChartData.series;

            switch (formattedType) {
                 case 'bar':
                     formattedType = 'bar';
                     var rotatedAxis = true;
                     break;
                case 'column':
                    formattedType = 'bar';
                    rotatedAxis = false;
                    break;
            }
            var chart = c3.generate({
                bindto: '#chart',
                data: {
                    columns: c3ChartDataSeries,
                    type: formattedType
                },
                bar: {
                    width: {
                        ratio: 0.5
                    }
                },
                axis: {
                    rotated: rotatedAxis
                }
            });
        }
    }
}(jQuery));

