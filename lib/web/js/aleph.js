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
    
    this.display = function(el, display, expire)
    {
      el = this.$(el);
      var disp = el.style.display;
      if (display !== undefined) el.style.display = display;
      else if (el.style.display == 'none') el.style.display = '';
      else el.style.display = 'none';
      if (expire !== undefined && expire > 0) setTimeout(function(){el.style.display = disp;}, expire);
      return this;
    };
    
    this.message = function(el, html, expire)
    {
      el = this.$(el);
      this.insert(el, html)
      setTimeout(function(){if (el) el.innerHTML = '';}, expire);
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
    
    this.focus = function(el)
    {
      el = this.$(el);
      try {el.focus();} catch(e){}
      return this;
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