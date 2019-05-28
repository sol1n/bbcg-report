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
						fontSize: 18
					},
					pointLabels: {
				    	fontSize: 30
				    }
				},
				legend: {
				    position: 'left'
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
	})
})