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
						max: 12,
						stepSize: 1,
						fontSize: 14
					},
					pointLabels: {
				    	fontSize: 14,
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

    $('.share button').on('click', function() {
    	var input = $(this).siblings('input');

		var range,
			selection;
		
		if (navigator.userAgent.match(/ipad|iphone/i)) {
			range = document.createRange();
			range.selectNodeContents(input);
			selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(range);
			input.setSelectionRange(0, 999999);
		}
		else {
			input.select();
		}
		document.execCommand('copy');

    	$(this).text('Скопировано');

    	console.log(input);
    });
})