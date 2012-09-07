/*
 * Copyright (c) 2012 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

var Aleph = function()
{
  var a = this;
  
  this.trim = function(str)
  {
			 return str == null ? '' : str.toString().replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
		};
  
  var DOM = function()
  {
    this.$ = function(el)
    {
      return (typeof el == 'string') ? document.getElementById(el) : el;
    };
    
    this.hasClass = function(el, className)
    {
      el = this.$(el);
      return (' ' + el.className + ' ').indexOf(' ' + className + ' ') >= 0;
    };

    this.addClass = function(el, className)
    {
      el = this.$(el);
      if (!this.hasClass(el, className)) el.className = a.trim((el.className + ' ' + className).replace(/\s+/g, ' '));
    };
   
    this.removeClass = function(el, className)
    {
      el = this.$(el);
      el.className = el.className.replace(new RegExp('(^|\\s)' + className + '(?:\\s|$)'), '$1');
    };
   
    this.toggleClass = function(el, className)
    {
      if (this.hasClass(el, className)) this.removeClass(el, className);
      else this.addClass(el, className);
    };
    
    this.getStyle = function(el, property)
    {
      el = this.$(el);
      if (el.currentStyle) style = el.currentStyle[property.replace(/-\D/g, function(match){return match.charAt(1).toUpperCase();})];
      if (document.defaultView && document.defaultView.getComputedStyle)
      {
        if (property.match(/[A-Z]/)) property = property.replace(/([A-Z])/g, '-$1').toLowerCase();
        style = document.defaultView.getComputedStyle(el, '').getPropertyValue(property);
      }
      if (!style) style = '';
      if (style == 'auto') style = '0px';
      return style;
    };
    
    this.display = function(el, display, expire)
    {
      el = this.$(el);
      if (display !== undefined) el.style.display = display;
      else if (el.style.display == 'none') el.style.display = '';
      else el.style.display = 'none';
      if (expire !== undefined && expire > 0) setTimeout(function(){el.style.display = 'none';}, expire);
      return this;
    };
    
    this.message = function(el, html, expire)
    {
      el = this.$(el);
      this.insert(el, html)
      setTimeout(function(){if (el) el.innerHTML = '';}, expire);
    };
    
    this.getCompatElement = function()
    {
      return ((!document.compatMode || document.compatMode == 'CSS1Compat')) ? document.documentElement : document.body;
    };

    this.isBody = function(el)
    {
      return (/^(?:body|html)$/i).test(el.tagName);
    };

    this.getClientPosition = function(event)
    {
      return {x: (event.pageX) ? event.pageX - window.pageXOffset : event.clientX, y: (event.pageY) ? event.pageY - window.pageYOffset : event.clientY};
    };

    this.getPagePosition = function(event)
    {
      return {x: event.pageX || event.clientX + document.scrollLeft, y: event.pageY || event.clientY + document.scrollTop};
    };
    
    this.getEventTarget = function(event)
    {
      var target = event.target || event.srcElement;
      while (target && target.nodeType == 3) target = target.parentNode;
      return target;
    };

    this.addEvent = function(el, type, fn)
    {
      el = this.$(el);
      if (el.addEventListener) el.addEventListener(type.toLowerCase(), fn, false);
      else el.attachEvent('on' + type.toLowerCase(), fn);
    };

    this.removeEvent = function(el, type, fn)
    {
      el = this.$(el);
      if (el.removeEventListener) el.removeEventListener(type.toLowerCase(), fn, false);
      else el.detachEvent('on' + type.toLowerCase(), fn);
    };

    this.stopEvent = function(event)
    {
      if (event.stopPropagation) event.stopPropagation();
      else event.cancelBubble = true;
      if (event.preventDefault) event.preventDefault();
      else event.returnValue = false;
    };
    
    this.getWindowSize = function()
    {
      if (window.opera || typeof navigator.taintEnabled == 'undefined') return {x: window.innerWidth, y: window.innerHeight};
      var doc = this.getCompatElement();
      return {x: doc.clientWidth, y: doc.clientHeight};
    };

    this.getWindowScroll = function()
    {
      var doc = this.getCompatElement();
      return {x: window.pageXOffset || doc.scrollLeft, y: window.pageYOffset || doc.scrollTop};
    };

    this.getWindowScrollSize = function()
    {
      var doc = this.getCompatElement(), min = this.getWindowSize();
      return {x: Math.max(doc.scrollWidth, min.x), y: Math.max(doc.scrollHeight, min.y)};
    };

    this.getWindowCoordinates = function()
    {
      var size = this.getWindowSize();
      return {top: 0, left: 0, bottom: size.y, right: size.x, height: size.y, width: size.x};
    };
    
    this.getSize = function(el)
    {
      el = this.$(el);
      return (this.isBody(el)) ? this.getWindowSize() : {x: el.offsetWidth, y: el.offsetHeight};
    };

    this.getScrollSize = function(el)
    {
      el = this.$(el);
      return (this.isBody(el)) ? this.getWindowScrollSize() : {x: el.scrollWidth, y: el.scrollHeight};
    };

    this.getScroll = function(el)
    {
      el = this.$(el);
      return (this.isBody(el)) ? this.getWindowScroll() : {x: el.scrollLeft, y: el.scrollTop};
    };

    this.getScrolls = function(el)
    {
      el = this.$(el);
      var position = {x: 0, y: 0};
      while (el && !this.isBody(el))
      {
        position.x += el.scrollLeft;
        position.y += el.scrollTop;
        el = el.parentNode;
      }
      return position;
    };

    this.getOffsets = function(el)
    {
      el = this.$(el);
      if (el.getBoundingClientRect)
      {
        var bound = el.getBoundingClientRect(), html = this.$(document.documentElement), htmlScroll = this.getScroll(html), elemScrolls = this.getScrolls(el), elemScroll = this.getScroll(el), isFixed = (this.getStyle(el, 'position') == 'fixed');
        return {x: parseInt(bound.left) + elemScrolls.x - elemScroll.x + ((isFixed) ? 0 : htmlScroll.x) - html.clientLeft, y: parseInt(bound.top)  + elemScrolls.y - elemScroll.y + ((isFixed) ? 0 : htmlScroll.y) - html.clientTop};
      }
      var sel = el, position = {x: 0, y: 0};
      if (this.isBody(el)) return position;
      while (el && !this.isBody(el))
      {
        position.x += el.offsetLeft;
        position.y += el.offsetTop;
        if (document.getBoxObjectFor || window.mozInnerScreenX != null)
        {
          if (this.getStyle(el, '-moz-box-sizing') != 'border-box')
          {
            position.x += parseInt(this.getStyle(el, 'border-left-width'));
            position.y += parseInt(this.getStyle(el, 'border-top-width'));
          }
          var parent = el.parentNode;
          if (parent && this.getStyle(parent, 'overflow') != 'visible')
          {
            position.x += parseInt(this.getStyle(parent, 'border-left-width'));
            position.y += parseInt(this.getStyle(parent, 'border-top-width'));
          }
        }
        else if (el != sel && !navigator.taintEnabled)
        {
          position.x += parseInt(this.getStyle(el, 'border-left-width'));
          position.y += parseInt(this.getStyle(el, 'border-top-width'));
        }
        el = el.offsetParent;
      }
      if ((document.getBoxObjectFor || window.mozInnerScreenX != null) && this.getStyle(sel, '-moz-box-sizing') != 'border-box')
      {
        position.x += parseInt(this.getStyle(sel, 'border-left-width'));
        position.y += parseInt(this.getStyle(sel, 'border-top-width'));
      }
      return position;
    };

    this.getPosition = function(el, relative)
    {
      el = this.$(el);
      if (this.isBody(el)) return {x: 0, y: 0};
      var offset = this.getOffsets(el), scroll = this.getScrolls(el);
      var position = {x: offset.x - scroll.x, y: offset.y - scroll.y};
      var relativePosition = (relative && (relative = this.$(relative))) ? this.getPosition(relative) : {x: 0, y: 0};
      return {x: position.x - relativePosition.x, y: position.y - relativePosition.y};
    };

    this.getCoordinates = function(el, relative)
    {
      el = this.$(el);
      if (this.isBody(el)) return this.getWindowCoordinates();
      var position = this.getPosition(el, relative), size = this.getSize(el);
      var obj = {left: position.x, top: position.y, width: size.x, height: size.y};
      obj.right = obj.left + obj.width;
      obj.bottom = obj.top + obj.height;
      return obj;
    };

    this.setPosition = function(el, pos)
    {
      el = this.$(el);
      var position = {left: pos.x - parseInt(this.getStyle(el, 'margin-left')), top: pos.y - parseInt(this.getStyle(el, 'margin-top'))}
      var parent = el.parentNode;
      if (this.getStyle(el, 'position') != 'fixed')
      {
        while (parent && !this.isBody(parent))
        {
          pos = this.getStyle(parent, 'position');
          if (pos == 'absolute' || pos == 'relative')
          {
            var pos = this.getPosition(parent);
            position.left -= pos.x;
            position.top -= pos.y;
            break;
          }
          parent = parent.parentNode;
        }
      }
      else
      {
        var scroll = this.getWindowScroll();
        position.left -= scroll.x;
        position.top -= scroll.y;
      }
      el.style.left = position.left + 'px';
      el.style.top = position.top + 'px';
    };
    
    this.centre = function(el, overflow)
    {
      el = this.$(el);
      var size = this.getSize(el), winSize = this.getWindowSize();
      var scroll = this.getWindowScroll();
      var xx = (winSize.x - size.x) / 2 + scroll.x, yy = (winSize.y - size.y) / 2 + scroll.y;
      if (!overflow)
      {
        if (xx < 0) xx = 0;
        if (yy < 0) yy = 0;
      }
      this.setPosition(el, {x: xx, y: yy});
    };

    this.scrollTo = function(el)
    {
      var pos = this.getPosition(el);
      window.scrollTo(pos.x, pos.y);
    };
    
    this.remove = function(el)
    {
      el = this.$(el);
      if (el && el.parentNode) el.parentNode.removeChild(el);
      return this;
    };
    
    this.insert = function(el, html)
    {
      el = this.$(el);
      if (el) el.innerHTML = html;
      return this;
    };
    
    this.replace = function(el, html)
    {
      var span = document.createElement('span');
      span.innerHTML = html;
      if (span.firstChild && span.firstChild.nodeName && span.firstChild.nodeType == 1) span = span.firstChild;
      else span = document.createTextNode(span.innerHTML);
      el = this.$(el);
      el.parentNode.replaceChild(span, el);
      return this;
    };
    
    this.focus = function(el, x, y)
    {
      el = this.$(el);
      if (!el) return;
      var parent = el.parentNode, flag = false;
      if (this.getStyle(el, 'position') != 'fixed')
      {
        while (parent && !this.isBody(parent))
        {
          if (this.getStyle(parent, 'position') == 'fixed')
          {
            flag = true;
            break;
          }
          parent = parent.parentNode;
        }
      }
      else flag = true;
      el = this.$(el);
      if (!flag)
      {
        x = x || 0;
        y = y || 0;
        var pos = this.getPosition(el), winSize = this.getWindowSize(), scroll = this.getWindowScroll();
        if (pos.x > winSize.x + scroll.x || pos.x < scroll.x || pos.y > winSize.y + scroll.y || pos.y < scroll.y) window.scrollTo(pos.x + parseInt(x), pos.y + parseInt(y));
      }
      try {el.focus();} catch (e){}
    };
    
    this.setOpacity = function(el, opacity)
    {
      el = this.$(el);
      if (opacity == 0 && el.style.visibility != 'hidden') el.style.visibility = 'hidden';
      else if (el.style.visibility != 'visible') el.style.visibility = 'visible';
      if (!el.currentStyle || !el.currentStyle.hasLayout) el.style.zoom = 1;
      if (window.ActiveXObject) el.style.filter = (opacity == 1) ? '' : 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + opacity * 100 + ')';
      el.style.opacity = opacity;
    };
    
    this.select = function(el) 
    {
      el = this.$(el);
      if (document.selection) 
      {
        var range = document.body.createTextRange();
        range.moveToElementText(el);
        range.select()
      }
      else if (window.getSelection) 
      {
        var range = document.createRange();
        range.selectNode(el);
        window.getSelection().addRange(range);
      }
    };
    
    this.getFormElements = function(el, tags)
    {
      el = this.$(el) || document;
      if (!tags) tags = 'input,textarea,select,checkbox,radio';
      tags = tags.split(',');
      var elements = [];
      for (var i = 0; i < tags.length; i++)
      {
        var partial = el.getElementsByTagName(a.trim(tags[i]));
        for (var k = 0, j = partial.length; k < j; k++) elements.push(partial[k]);
      }
      return elements;
    };
    
    this.getFormValues = function(el, tags)
    {
      var values = {};
      var elements = this.getFormElements(el, tags);
      for (var i = 0; i < elements.length; i++)
      {
        var el = elements[i], value;
        if (!el.id || el.tagName == 'INPUT' && (el.type == 'submit' || el.type == 'image' || el.type == 'reset' || el.type == 'button')) continue;
        switch (el.type)
        {
          default:
            value = el.value;
            break;
          case 'textarea':
            if (typeof CKEDITOR != 'undefined' && CKEDITOR.instances[el.id]) value = CKEDITOR.instances[el.id].getData();
            else if (typeof tinyMCE != 'undefined' && tinyMCE.get(el.id)) value = tinyMCE.get(el.id).getContent();
            else value = el.value;
            break;
          case 'radio':
          case 'checkbox':
            value = new Array();
            value['state'] = (el.checked) ? 1 : 0;
            value['value'] = el.value;
            break;
          case 'select-multiple':
            value = new Array();
            for (var j = 0; j < el.length; j++)
            {
              if (el.options[j].selected == true) value[j] = el.options[j].value;
            }
            break;
        }
        if (el.name && el.name.substr(el.name.length - 2) == '[]')
        {
          if (typeof values[el.id] == 'undefined') values[el.id] = {};
          values[el.id][values[el.id].length] = value;
        }
        else values[el.id] = value;
      }
      return values;
    };
    
    this.cleanFormValues = function(el)
    {
      var elements = this.getFormElements(el);
      for (var i = 0; i < elements.length; i++)
      {
        var el = elements[i];
        switch (el.type)
        {
          case 'text':
          case 'hidden':
          case 'select-one':
          case 'select-multiple':
          case 'textarea':
            if (typeof CKEDITOR != 'undefined' && CKEDITOR.instances[el.id]) value = CKEDITOR.instances[el.id].setData('');
            else if (typeof tinyMCE != 'undefined' && tinyMCE.get(el.id)) value = tinyMCE.get(el.id).setContent('');
            else el.value = '';
            break;
          case 'checkbox':
          case 'radio':
            el.checked = false;
            break;
        }
      }
    };
    
    this.fade = function(el, show, opacity)
    {
      if (!show) this.display(el, 'none');
      else
      {
        el = this.$(el);
        if (opacity == undefined) opacity = 0.5;
        var size = this.getWindowSize();
        el.style.position = 'fixed';
        el.style.top = '0px';
        el.style.left = '0px';
        el.style.width = size.x + 'px';
        el.style.height = size.y + 'px';
        this.setOpacity(el, opacity);
        this.display(el, '');
      }
    };
  };
  
  this.dom = new DOM();
  
  var Ajax = function()
  {
    var ajx = this, processes = 0;
  
    this.url = window.location.href;
    this.headers = {'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
                    'Accept': 'text/javascript, text/html, application/xml, text/xml, */*'};
    this.async = true;
    this.method = 'get';
    this.charset = 'utf-8';
    this.response = null;
    this.enableLoader = true;
    this.onShowLoader = null;
    this.onHideLoader = null;
    this.onComplete = null;
    this.onSuccess = null;
    this.onError = null;
    this.onException = null;
    this.onHistory = null;
  
    var exec = function(data) 
    {
		    if (data && /\S/.test(data)) 
      {
			     (window.execScript || function(data) {window['eval'].call(window, data);})(data);
		    }
	   };
    
    var XHR = function(method, arguments)
    {
      var xhr, onComplete, onSuccess, onError, data, url;
      try 
      {
        xhr = new XMLHttpRequest();
      }
      catch(e) 
      {
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
      }
      this.complete = function(handler)
      {
        onComplete = handler;
        return this;
      };
      this.success = function(handler)
      {
        onSuccess = handler;
        return this;
      };
      this.error = function(handler)
      {
        onError = handler;
        return this;
      };
      processes++;
      ajx.showLoader();
      data = 'ajax-method=' + encodeURIComponent(method);
      data += '&ajax-args=' + encodeURIComponent(JSON.stringify(arguments));
      url = ajx.url;
      if (ajx.method.toUpperCase() == 'GET') url += (url.indexOf('?') >= 0 ? '&' : '?') + data;
      xhr.open(ajx.method.toUpperCase(), url, ajx.async);
      xhr.onreadystatechange = function()
      {
        if (xhr.readyState != 4) return;
        var statusDescription = (xhr.status == 0) ? 'abort' : 'error';
        if (xhr.status >= 200 && xhr.status < 300)
        {
          processes--;
          exec(xhr.responseText);
          onSuccess = onSuccess || ajx.onSuccess;
          if (typeof onSuccess == 'function') onSuccess(JSON.parse(ajx.response), xhr);
        }
        else 
        {
          onError = onError || ajx.onError;
          if (typeof onError == 'function') onError(xhr);
        }
        xhr.onreadystatechange = null;
        if (processes < 1)
        {
          processes = 0;
          ajx.hideLoader();
          onComplete = onComplete || ajx.onComplete;
          if (typeof onComplete == 'function') onComplete(ajx.response, xhr);
        }
      };
      for (var key in ajx.headers)
      {
        try 
        {
          xhr.setRequestHeader(key, ajx.headers[key]);
        }
        catch (e)
        {
          if (typeof ajx.onException == 'function') ajx.onException(key, ajx.headers[key]);
        }
      }
      xhr.send(data);
    };
    
    this.action = function(act)
    {
      var time = arguments[arguments.length - 1], args = Array.prototype.slice.call(arguments, 1);
      var process = function()
      {
        switch (act.toLowerCase())
        {
          case 'alert':
            alert(args[0]);
            break;
          case 'redirect':
            window.location.assign(args[0]);
            break;
          case 'reload':
            window.location.reload(true);
            break;
          case 'display':
            a.dom.display(args[0], args[1], args[2]);
            break;
          case 'focus':
            a.dom.focus(args[0]);
            break;
          case 'addclass':
            a.dom.addClass(args[0], args[1]);
            break;
          case 'removeclass':
            a.dom.removeClass(args[0], args[1]);
            break;
          case 'toggleclass':
            a.dom.toggleClass(args[0], args[1]);
            break;
          case 'remove':
            a.dom.remove(args[0]);
            break;
          case 'insert':
            a.dom.insert(args[0], args[1]);
            break;
          case 'replace':
            a.dom.replace(args[0], args[1]);
            break;
          case 'message':
            a.dom.message(args[0], args[1], args[2]);
            break;
        }
      };
      setTimeout(process, time);
    };
    
    this.showLoader = function()
    {
      if (!this.enableLoader) return this;
      if (document.body) document.body.style.cursor = 'wait';
      if (typeof this.onShowLoader == 'function') onShowLoader();
      return this;
    };

    this.hideLoader = function()
    {
      if (!this.enableLoader) return this;
      if (document.body) document.body.style.cursor = 'default';
      if (typeof this.onHideLoader == 'function') this.onHideLoader();
      return this;
    };
    
    this.request = function(method)
    {
      return new XHR(method, Array.prototype.slice.call(arguments, 1));
    };
  };
  
  this.ajax = new Ajax();

};

var aleph = new Aleph();