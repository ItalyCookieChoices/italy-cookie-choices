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
	    	 * Funzione per le tab nel pannello admin
	    	 */
	    	// $('#tabs').tabs();

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
				// var prova = thisButton.parent().children('textarea');
				var curValue = textarea.val();

				var dataValue = thisButton.data('value');

				if ( dataValue === '<---------SEP--------->' ) {

					var newValue = curValue + '\n' + dataValue + '\n';

				}else if( dataValue === '<---------SOMETHING--------->' ){

					/**
					 * Snippet for remove selected text from textarea
					 * link http://stackoverflow.com/questions/18133776/how-do-i-remove-selected-text-from-an-input-control
					 * link http://jsfiddle.net/z36Px/2/
					 */
					var ele  = textarea.get(0); // or textarea[0]
				    curValue = curValue.slice(0, ele.selectionStart) + curValue.slice(ele.selectionEnd);

					var position = textarea.getCursorPosition();
					var newValue = curValue.substr(0, position) + "<---------SOMETHING--------->" + curValue.substr(position);
		    	}else{
		    		var newValue = curValue + dataValue;
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

			function callback( input ) {
				setTimeout(function() {
					input.removeAttr( "style" );
				}, 1000 );
			};

			/**
			 * Add element
			 */
			$(document).on('click' , '.add', function(){

				var container = $(this).parent().parent();

				var input = container.children('input');

				var arr_name = input.data('type');

				if ( !arr_name ) {
					var options = {};
					container.effect( 'shake', options, 500, callback(container) );
					return;
				}

				var select = container.children('select').get(0).innerHTML;

				$('<div class="italy-cookie-choices-clone-div"><input type="text" class="regular-text" data-type="' + arr_name + '" value="" name="italy_cookie_choices[' + arr_name + '][]"/> <select>' + select + '</select> <span><a class="button add" style="font-size:22px"> + </a> <a class=" button remove" style="font-size:22px"> × </a></span></div>').appendTo(container.parent());

			});

			/**
			 * Remove element
			 */
			$(document).on('click', '.remove', function(){
				$(this).parent().parent().remove();
			});

			/**
			 * Cancel value in first input
			 */
			$(document).on('click', '.cancel', function(){
				$(this).parent().parent().children('input').val('');
			});

			/**
			 * Update input value on keyup
			 */
			$(document).on('keyup', '.regular-text', function(){

				var arr_name = $(this).data('type');

				$(this).attr('name', 'italy_cookie_choices[' + arr_name + '][' + $(this).val() + ']');

			});

			/**
			 * Change input value on select change
			 */
			$(document).on('change', 'select', function(){

				var input = $(this).parent().children('input');

				input.val($(this).val());

				var arr_name = input.data('type');

				input.attr('name', 'italy_cookie_choices[' + arr_name + '][' + $(this).val() + ']');

			});

	    });
	});

/**
 * {@link http://code.tutsplus.com/tutorials/adding-a-custom-css-editor-to-your-theme-using-ace--wp-29451}
 * {@link http://ace.c9.io/#nav=about}
 */
// ( function( global, $ ) {

// 		var scriptArea = $( '#italy_cookie_choices\\[custom_script_block_body_exclude\\]' );
// 		var editor = ace.edit( 'editor' );
// 		editor.$blockScrolling = Infinity;
// 		var
// 		syncCSS = function() {
// 			scriptArea.val( editor.getSession().getValue() );
// 		},
// 		loadAce = function() {
// 			// editor = ace.edit( 'editor' );
// 			global.safecss_editor = editor;
// 			editor.getSession().setUseWrapMode( true );
// 			editor.setShowPrintMargin( false );
// 			editor.getSession().setValue( scriptArea.val() );
// 			editor.getSession().setMode( "ace/mode/javascript" );
// 			jQuery.fn.spin&&$( '#editor' ).spin( false );
// 			$( '#italy-cookie-choices-ID' ).submit( syncCSS );
// 		};
// 		if ( $.browser.msie&&parseInt( $.browser.version, 10 ) <= 7 ) {
// 			$( '#editor' ).hide();
// 			scriptArea.show();
// 			return false;
// 		} else {
// 			scriptArea.hide();
// 			$( global ).load( loadAce );
// 		}
// 		global.aceSyncCSS = syncCSS;

// } )( this, jQuery );