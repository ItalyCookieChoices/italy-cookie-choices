/* PointerPlus Based on QueryLoop Pointer */

//Read the var with data
var pp_scripts = document.getElementsByTagName("script");
pp_scripts = pp_scripts[pp_scripts.length - 1];

jQuery(function ($) {
  'use strict';

  $.fn.onAvailable = function (fn) {
    var sel = this.selector;
    var timer;
    if (this.length > 0) {
      fn.call(this);
    } else {
      timer = setInterval(function () {
        if ($(sel).length > 0) {
          fn.call($(sel));
          clearInterval(timer);
        }
      }, 300);
    }
  };

  var pointerplus = eval(getParams(pp_scripts).var);
  $.each(pointerplus, function (key, pointer) {
    pointer.class += ' pp-' + key;
    if (!pointer.show) {
      pointer.show = 'open';
    }
    jQuery(pointer.selector).onAvailable(function () {
      $(pointer.selector).pointer({
        content: '<h3>' + pointer.title + '</h3><p>' + pointer.text + '</p>',
        position: {
          edge: pointer.edge,
          align: pointer.align
        },
        pointerWidth: parseInt(pointer.width),
        pointerClass: 'wp-pointer pointerplus' + pointer.class,
        buttons: function (event, t) {
          if (pointer.jsnext) {
            var jsnext = new Function('t', '$', pointer.jsnext);
            return jsnext(t, jQuery);
          } else {
            var close = (wpPointerL10n) ? wpPointerL10n.dismiss : 'Dismiss',
                    button = jQuery('<a class="close" href="#">' + close + '</a>');
            return button.bind('click.pointer', function (e) {
              e.preventDefault();
              t.element.pointer('close');
            });
          }
        },
        close: function () {
          $.post(ajaxurl, {
            pointer: key,
            action: 'dismiss-wp-pointer'
          });
        }
      }).pointer(pointer.show);
      // Hack for custom dashicons
      if (pointer.icon_class !== '') {
        $('.pp-' + key + ' .wp-pointer-content').addClass('pp-pointer-content').removeClass('wp-pointer-content');
        $('.pp-' + key + ' .pp-pointer-content h3').addClass('dashicons-before').addClass(pointer.icon_class);
      }
    });
  });
});

function getParams(script_choosen) {
  // Get an array of key=value strings of params
  var pa = script_choosen.src.split("?").pop().split("&");
  // Split each key=value into array, the construct js object
  var p = {};
  for (var j = 0; j < pa.length; j++) {
    var kv = pa[j].split("=");
    p[kv[0]] = kv[1];
  }
  return p;
}