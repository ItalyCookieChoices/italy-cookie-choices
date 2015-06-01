// if( typeof jQuery != "undefined" )
	jQuery.noConflict()(function($){
	    "use strict";
	    $(document).ready(function() {
	    	/**
	    	 * Snippet per il color picker di WordPress
	    	 */
		    $(function() {
				var options = {
					// you can declare a default color here,
					// or in the data-default-color attribute on the input
					defaultColor: '#fff',
					// a callback to fire whenever the color changes to a valid color
					// change: function(event, ui){},
					// a callback to fire when the input is emptied or an invalid color
					// clear: function() {},
					// hide the color picker controls on load
					// hide: true,
					// show a group of common colors beneath the square
					// or, supply an array of colors to customize further
					// palettes: true
				};
		        $('.color-field').wpColorPicker(options);
		    });
	    });
	});