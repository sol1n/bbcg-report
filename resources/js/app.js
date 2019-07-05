/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
require('chart.js');

$(function(){
	$('[data-plot]').each(function(){
		var data = $(this).data('plot');

		var radarChart = new Chart(this, {
			type: 'radar',
			data: data,
			options: {
				fill: false,
				scale: {
					ticks: {
						beginAtZero: true,
						min: 0,
						max: 4,
						stepSize: 1,
						fontSize: 8
					},
					pointLabels: {
				    	fontSize: 8,
				    	fontColor: '#111'
				    }
				},
				legend: {
				    display: false
				},
				tooltips: {
		            callbacks: {
		                label: function(tooltipItems, data) {
		                    return data.datasets[tooltipItems.datasetIndex].label +': ' + tooltipItems.yLabel;
		                }
		            }

		        }
			}
		});
	});

	var outerContent = $('.plot');
    var innerContent = $('.plot-wrapper');

    outerContent.scrollLeft((innerContent.width() - outerContent.width()) / 2);
})