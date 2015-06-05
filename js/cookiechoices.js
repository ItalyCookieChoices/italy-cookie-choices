/*
 Copyright 2014 Google Inc. All rights reserved.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */

function allowCookie() {

  cookieChoices.removeCookieConsent();

    var x=document.getElementsByClassName("el");

    // var patt = new RegExp("<script.*?\/script>");
    var patt = new RegExp("<script.*?");

    var i;
    for (i = 0; i < x.length; i++) {

        x[i].removeChild(x[i].childNodes[0]);

        // console.log(jsArr[i]);

        var res = patt.test(jsArr[i]);
        // console.log(res);

        if (res) {
            // console.log(jsArr[i]);


            var regexURL = /<script.*?src="(.*?)"/;

            var URL = regexURL.test(jsArr[i]);

            if (URL) {
                URL = regexURL.exec(jsArr[i]);
                loadJS(URL[1]);
            }


            var regex = /<script\b[^>]*>([\s\S]*?)<\/script>/gm;

            var code = regex.exec(jsArr[i]);

            if ( code[1] ) appendJS(code[1]);




        } else {

            var str = x[i].innerHTML;
            // var res = str.replace(/<!--(.*?)-->/g, "$1");
            // Prendo l\'array creato e all\'accettazione ogni valore è messo al suo posto
            res = str.replace(/<cookie>/g, jsArr[i]);
            x[i].innerHTML = res;
        }

    // var cookieName=coNA;var expiryDate=new Date();expiryDate.setFullYear(expiryDate.getFullYear()+1);document.cookie=cookieName+"=; expires="+expiryDate.toGMTString()+"; path=/";

    // var cookieName=coNA;var expiryDate=new Date();expiryDate.setFullYear(expiryDate.getFullYear() + 1);
      // document.cookie = cookieName + '=y; expires=' + expiryDate.toGMTString();
      // document.cookie = cookieName + '=' + coVA + '; expires=' + expiryDate.toGMTString() + ';path=/';

    }
}

function loadJS(file) {
    // DOM: Create the script element
    var jsElm = document.createElement("script");
    // set the type attribute
    jsElm.type = "application/javascript";
    // make the script element load file
    jsElm.src = file;
    // finally insert the element to the body element in order to load the script
    document.body.appendChild(jsElm);
}

function appendJS(script){
    var s = document.createElement("script");
    s.type = "text/javascript";
    var code = script;
    try {
        s.appendChild(document.createTextNode(code));
        document.body.appendChild(s);
    } catch (e) {
        s.text = code;
        document.body.appendChild(s);
    }
}

(function(window) {

  if (!!window.cookieChoices) {
    return window.cookieChoices;
  }

  var document = window.document;
  var html = document.documentElement;//Per aggiungere un margin-top al tag HTML
  // IE8 does not support textContent, so we should fallback to innerText.
  var supportsTextContent = 'textContent' in document.body;

  var cookieChoices = (function() {

    // var cookieName = 'displayCookieConsent';
    var cookieName = coNA;
    var cookieConsentId = 'cookieChoiceInfo';
    var dismissLinkId = 'cookieChoiceDismiss';

    function _createHeaderElement(cookieText, dismissText, linkText, linkHref) {

      if( htmlM ) html.className += ' icc';

      var butterBarStyles = 'color:'+ btcB +';position:' + elPos + ';width:100%;background-color:' + bgB + ';' +
          'margin:0; left:0; top:0;padding:4px;z-index:9999;text-align:left;';
  
  // Aggiungo contenitore esterno per migliorare il layout
      var contenitore = document.createElement('div');
      var contenutoContenitoreStyle =  'max-width:980px;margin-right:auto;margin-left:auto;padding:15px;';
      contenitore.id = cookieConsentId;
      contenitore.style.cssText = butterBarStyles;

      var cookieConsentElement = document.createElement('div');
      //cookieConsentElement.id = cookieConsentId;
      cookieConsentElement.style.cssText = contenutoContenitoreStyle;
      cookieConsentElement.appendChild(_createConsentText(cookieText));
  cookieConsentElement.appendChild(_createSpace());

      if (!!linkText && !!linkHref) {
        cookieConsentElement.appendChild(_createInformationLink(linkText, linkHref));
      }
      cookieConsentElement.appendChild(_createDismissLink(dismissText));
      
      // Inglobo contenuto in contenitore.
      contenitore.appendChild(cookieConsentElement);

      return contenitore;
      //return cookieConsentElement;
    }

    function _createDialogElement(cookieText, dismissText, linkText, linkHref) {
      var glassStyle = 'position:fixed;width:100%;height:100%;z-index:999;' +
          'top:0;left:0;opacity:0.5;filter:alpha(opacity=50);' +
          'background-color:#ccc;';
      var dialogStyle = 'z-index:9999;position:fixed;left:50%;top:50%;bottom:0%;';
      var contentStyle = 'position:relative;left:-50%;margin-top:-25%;' +
          'background-color:' + bgB + ';padding:20px;box-shadow:4px 4px 25px #888;';

      var cookieConsentElement = document.createElement('div');
      cookieConsentElement.id = cookieConsentId;

      var glassPanel = document.createElement('div');
      glassPanel.style.cssText = glassStyle;

      var content = document.createElement('div');
      content.style.cssText = contentStyle;
      
  
      
      
      var dialog = document.createElement('div');
      dialog.style.cssText = dialogStyle;

      var dismissLink = _createDismissLink(dismissText);
      //dismissLink.style.display = 'block';
      //dismissLink.style.textAlign = 'right';
      //dismissLink.style.marginTop = '8px';

      content.appendChild(_createConsentText(cookieText));
      
      content.appendChild(_createSpace());
      
      if (!!linkText && !!linkHref) {
        content.appendChild(_createInformationLink(linkText, linkHref));
      }
      content.appendChild(dismissLink);
      dialog.appendChild(content);
      cookieConsentElement.appendChild(glassPanel);
      cookieConsentElement.appendChild(dialog);
      return cookieConsentElement;
    }

    function _setElementText(element, text) {
      if (supportsTextContent) {
        element.textContent = text;
      } else {
        element.innerText = text;
      }
    }

    function _createSpace(){
      var hrStyle='clear:both;border-color:transparent;margin-top:5px;margin-bottom:5px';
  var hr = document.createElement("hr");
  hr.style.cssText = hrStyle;
  return hr;
    }
    
    function _createConsentText(cookieText) {
      var consentText = document.createElement('span');
      _setElementText(consentText, cookieText);
      return consentText;
    }

    function _createDismissLink(dismissText) {
      var buttonStyle='color: '+ btcB +';padding: 7px 12px;font-size: 18px;line-height: 18px;text-decoration: none;text-transform: uppercase;margin:0;margin-bottom:2px;letter-spacing: 0.125em;' +
      'display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;  cursor: pointer;  border: 1px solid '+ btcB +';background: rgba(255, 255, 255, 0.03);'
      
      var dismissLink = document.createElement('a');
      _setElementText(dismissLink, dismissText);
      dismissLink.id = dismissLinkId;
      dismissLink.className = closeClass;
      dismissLink.href = '#';
      //dismissLink.style.marginLeft = '24px';
      dismissLink.style.cssText = buttonStyle;
      return dismissLink;
    }

    function _createInformationLink(linkText, linkHref) {
      var buttonStyle='color: '+ btcB +';padding: 7px 12px;font-size: 18px;line-height: 18px;text-decoration: none;text-transform: uppercase;margin-right: 20px;margin-bottom:2px;letter-spacing: 0.125em;' +
      'display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;  cursor: pointer;  border: 1px solid '+ btcB +';background: rgba(255, 255, 255, 0.03);';
  
      var infoLink = document.createElement('a');
      _setElementText(infoLink, linkText);
      infoLink.className = infoClass;
      infoLink.href = linkHref;
      if (tar) infoLink.target = '_blank';
      infoLink.style.cssText = buttonStyle;
      return infoLink;
    }

    function _dismissLinkClick() {
     if ( htmlM ) html.classList.remove("icc");
      _saveUserPreference();
      _removeCookieConsent();
      if ( rel ) document.location.reload();
      return false;
    }

    function _showCookieConsent(cookieText, dismissText, linkText, linkHref, isDialog) {
      if (_shouldDisplayConsent()) {
        _removeCookieConsent();
        var consentElement = (isDialog) ?
            _createDialogElement(cookieText, dismissText, linkText, linkHref) :
            _createHeaderElement(cookieText, dismissText, linkText, linkHref);
        var fragment = document.createDocumentFragment();
        fragment.appendChild(consentElement);
        document.body.appendChild(fragment.cloneNode(true));
        document.getElementById(dismissLinkId).onclick = _dismissLinkClick;
        // document.onclick = _dismissLinkClick;
        if (scroll) document.onscroll = _dismissLinkClick;
      }
    }

    function showCookieConsentBar(cookieText, dismissText, linkText, linkHref) {
      _showCookieConsent(cookieText, dismissText, linkText, linkHref, false);
    }

    function showCookieConsentDialog(cookieText, dismissText, linkText, linkHref) {
      _showCookieConsent(cookieText, dismissText, linkText, linkHref, true);
    }

    function _removeCookieConsent() {
      var cookieChoiceElement = document.getElementById(cookieConsentId);
      if (cookieChoiceElement !== null) {
        cookieChoiceElement.parentNode.removeChild(cookieChoiceElement);
      }
    }

    function removeCookieConsent(){
      // _removeCookieConsent();
      _dismissLinkClick();
    }

    function _saveUserPreference() {
      // Set the cookie expiry to one year after today.
      var expiryDate = new Date();
      expiryDate.setFullYear(expiryDate.getFullYear() + 1);
      // document.cookie = cookieName + '=y; expires=' + expiryDate.toGMTString();
      document.cookie = cookieName + '=' + coVA + '; expires=' + expiryDate.toGMTString() + ';path=/';
    }

    function _shouldDisplayConsent() {
      // Display the header only if the cookie has not been set.
      return !document.cookie.match(new RegExp(cookieName + '=([^;]+)'));
    }

    var exports = {};
    exports.showCookieConsentBar = showCookieConsentBar;
    exports.showCookieConsentDialog = showCookieConsentDialog;
    exports.removeCookieConsent = removeCookieConsent;
    return exports;
  })();

  window.cookieChoices = cookieChoices;
  return cookieChoices;
})(this);