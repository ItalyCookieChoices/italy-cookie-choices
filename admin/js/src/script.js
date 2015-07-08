// if( typeof jQuery != "undefined" )
	jQuery.noConflict()(function($){
		"use strict";

		/**
		 * [getCursorPosition description]
		 * @return {[type]} [description]
		 * @link  http://stackoverflow.com/questions/5203428/inserting-text-after-cursor-position-in-text-are%D0%B0
		 * @link https://richonrails.com/articles/text-area-manipulation-with-jquery
		 */
		$.fn.getCursorPosition = function () {
			var el = $(this).get(0);
			var pos = 0;
			if ('selectionStart' in el) {
				pos = el.selectionStart;
			} else if ('selection' in document) {
				el.focus();
				var Sel = document.selection.createRange();
				var SelLength = document.selection.createRange().text.length;
				Sel.moveStart('character', -el.value.length);
				pos = Sel.text.length - SelLength;
			}
			return pos;
		}

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

			/**
			 * Add separator on click in textarea
			 */
			$('.add-sep').click(function(){
				var thisButton = $(this);
				var textarea = thisButton.siblings('textarea');
				var curValue = textarea.val();
				var dataValue = thisButton.data('value');

				if (dataValue === '<---------SEP--------->') {
					var newValue = curValue + '\n' + dataValue + '\n';
				} else{
					// var newValue = curValue + dataValue;
					var position = textarea.getCursorPosition();
					var newValue = curValue.substr(0, position) + "<---------SOMETHING--------->" + curValue.substr(position);
		    	};
		    	textarea.val(newValue);
		    });

		    /**
		     * Autocomplete function for slug input
		     */
		    function autocomplete (source, id) {

				source = JSON.parse(source).sort();
				$( 'input[id=italy_cookie_choices\\[' + id + '\\]]' ).autocomplete({
					source: source,
					// delay: 500,
					minLength: 2,
				});

		    }
			autocomplete(slugs, 'slug');
			autocomplete(urls, 'url');

	    });
	});