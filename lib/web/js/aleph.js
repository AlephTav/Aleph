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

var aleph = new function()
{
  var a = this;
  
  this.trim = function(str)
  {
			 return str == null ? '' : str.toString().replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
		};
  
  this.camelCase = function(str)
  {
		  return str.replace(/^-ms-/, 'ms-').replace(/-([\da-z])/gi, function(all, letter){return (letter + '').toUpperCase();});
	 },
  
  this.dom = new function()
  {
    var Query = function(selector, context)
    {
      var self = this, ctx, readyList = [], rroot = /^(?:body|html)$/i;
      var elements = (function()
      {
        if (!selector) selector = document.body;
        else if (selector == document || selector == window) 
        {
          ctx = selector;
          return [];
        }
        if (selector.nodeType) return [selector];
        if (typeof selector !== 'string') return [];
        var el = document.getElementById(selector);
        if (el) return [el];
        context = context || document;
        if (typeof context.querySelectorAll == 'undefined')
        {
          // This code was taken from http://www.codecouch.com/2012/05/adding-document-queryselectorall-support-to-ie-7/
	         var style = document.createStyleSheet();
	         context.querySelectorAll = function(selector) 
          {
		          var all = document.all, els = [], selector = selector.replace(/\[for\b/gi, '[htmlFor').split(',');
		          for  (i = selector.length; i--;)
            {
			           style.addRule(selector[i], 'k:v');
			           for (j = all.length; j--;) all[j].currentStyle.k && els.push(all[j]);
			           style.removeRule(0);
		          }
		          return els;
	         };
        }
        return context.querySelectorAll(selector);
      })();
      
      var typeOf = function(obj)
      {
        if (obj == null) return String(obj);
        var type = Object.prototype.toString.call(obj);
        if (!type) return 'object';
        type = type.split(' ')[1].toLowerCase();
        return type.substr(0, type.length - 1);
      };      
      
      var performer = function(getter, setter, value, single)
      {
        if (typeof value != 'undefined')
        {
          if (typeOf(value) == 'function') 
          {
            if (getter) for (var i = 0; i < elements.length; i++) setter(elements[i], value(i, getter(elements[i])));
            else for (var i = 0; i < elements.length; i++) setter(elements[i], value(i, elements[i]));
          }
          else if (typeOf(value) == 'array' && !single) for (var i = 0, n = Math.min(elements.length, value.length); i < n; i++) setter(elements[i], value[i]);
          else for (var i = 0; i < elements.length; i++) setter(elements[i], value);
          return self;
        }
        else if (!getter)
        {
          for (var i = 0; i < elements.length; i++) setter(elements[i]);
          return self;
        }
        if (elements.length == 0) return;
        for (var i = 0, result = []; i < elements.length; i++) result[i] = getter(elements[i]);
        return result.length == 1 ? result[0] : result;
      };
      
      var parseHTML = function(html)
      {
        if (typeof html != 'string') return html;
        var nodes = [], span = document.createElement('span');
        span.innerHTML = html;
        span = span.firstChild;
        while (span) 
        {
          nodes[nodes.length] = span;
          span = span.nextSibling;
        }
        return nodes;
      };
      
      this.get = function()
      {
        return elements;
      };
      
      this.set = function(els)
      {
        elements = els;
        return this;
      };
      
      this.each = function(handler)
      {
        for (var i = 0; i < elements.length; i++) handler(i, elements[i]);
        return this;
      };
      
      this.val = function(value)
      {
        return performer(function(el){return el.value;}, function(el, val){el.value = val;}, value);
      };
      
      this.hasClass = function(className)
      {
        return performer(function(el)
        {
          return (' ' + a.trim(el.className) + ' ').indexOf(' ' + className + ' ') >= 0;
        });
      };
      
      this.addClass = function(className)
      {
        return performer(function(el){return el.className;}, function(el, val)
        {
          if (!new Query(el).hasClass(val)) el.className = a.trim(a.trim(el.className) + ' ' + a.trim(val));
        }, className);
      };
      
      this.removeClass = function(className)
      {
        return performer(function(el){return el.className;}, function(el, val)
        {
          el.className = a.trim(el.className.replace(new RegExp('(^|\\s)' + a.trim(val) + '(?:\\s|$)'), '$1'));
        }, className);
      };
      
      this.toggleClass = function(className)
      {
        return performer(function(el){return el.className;}, function(el, val)
        {
          el = new Query(el);
          el.hasClass(val) ? el.removeClass(val) : el.addClass(val);
        }, className);
      };
      
      this.css = function(property, value)
      {
        if (typeof property == 'object')
        {
          for (prop in property) this.css(prop, property[prop]);
          return this;
        }
        return performer(function(el)
        {
          if (el.currentStyle) style = el.currentStyle[property.replace(/-\D/g, function(match){return match.charAt(1).toUpperCase();})];
          if (document.defaultView && document.defaultView.getComputedStyle)
          {
            if (property.match(/[A-Z]/)) property = property.replace(/([A-Z])/g, '-$1').toLowerCase();
            style = document.defaultView.getComputedStyle(el, '').getPropertyValue(property);
          }
          return style || '';
        },
        function(el, val)
        {
          property = a.camelCase(property);
          if (!el || el.nodeType === 3 || el.nodeType === 8 || !el.style) return;
          if (property == 'opacity')
          {
            if (val == 0 && el.style.visibility != 'hidden') el.style.visibility = 'hidden';
            else if (el.style.visibility != 'visible') el.style.visibility = 'visible';
            if (!el.currentStyle || !el.currentStyle.hasLayout) el.style.zoom = 1;
            if (window.ActiveXObject) el.style.filter = (val == 1) ? '' : 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + val * 100 + ')';
            el.style.opacity = val;
          }
          else
          {
            try {el.style[property] = val;}
            catch(e){} 
          }
        }, value);
      };
      
      this.remove = function()
      {
        return performer(null, function(el)
        {
          if (el.parentNode) el.parentNode.removeChild(el);
        });
      };
    
      this.insert = function(html)
      {
        return performer(function(el){return el.innerHTML;}, function(el, val){el.innerHTML = val;}, html);
      };
    
      this.replace = function(html)
      {
        return performer(function(el){return el.innerHTML;}, function(el, val)
        {
          var parent = el.parentNode; if (!parent) return;
          for (var i = 0, nodes = parseHTML(val); i < nodes.length; i++) parent.insertBefore(nodes[i], el);
          parent.removeChild(el);
        }, html);
      };
    
      this.inject = function(html, mode)
      {
        return performer(function(el){return el.innerHTML;}, function(el, val)
        {
          if ((mode == 'before' || mode == 'after') && !el.parentNode) return;
          var i = 0, parent = el.parentNode, nodes = parseHTML(val), n = nodes.length;
          switch (mode)
          {
            case 'top':
              var first = el.firstChild;
              if (first) for (; i < n; i++) el.insertBefore(nodes[i], first);
              else for (; i < n; i++) el.appendChild(nodes[i]);
              break;
            case 'bottom':
              for (; i < n; i++) el.appendChild(nodes[i]);
              break;
            case 'before':
              for (; i < n; i++) parent.insertBefore(nodes[i], el);
              break;
            case 'after':
              var next = el.nextSibling;
              if (next) for (; i < n; i++) parent.insertBefore(nodes[i], next)
              else for (; i < n; i++) parent.appendChild(nodes[i]);
              break;
          }
        }, html);
      };
      
      this.display = function(display, expire)
      {
        if (typeof expire != 'undefined' && expire > 0 && elements.length > 0) 
        {
          setTimeout(function()
          {
            for (var i = 0; i < elements.length; i++) elements[i].style.display = 'none';
          }, expire);
        }
        return performer(function(el){el.style.display;}, function(el, val)
        {
          if (typeof val != 'undefined') el.style.display = val;
          else if (el.style.display == 'none') el.style.display = '';
          else el.style.display = 'none';
        }, display);
      };
      
      this.message = function(html, expire)
      {
        if (typeof expire != 'undefined' && expire > 0 && elements.length > 0) 
        {
          setTimeout(function()
          {
            for (var i = 0; i < elements.length; i++) elements[i].innerHTML = '';
          }, expire);
        }
        return performer(function(el){return el.innerHTML;}, function(el, val){el.innerHTML = val;}, html);
      };
      
      this.getFormElements = function(tags)
      {
        if (elements.length == 0) return [];
        var result = [];
        if (!tags) tags = 'input,textarea,select,checkbox,radio';
        tags = tags.split(',');
        for (var n = 0; n < elements.length; n++)
        {
          el = elements[n];
          for (var i = 0, partial; i < tags.length; i++)
          {
            partial = el.getElementsByTagName(a.trim(tags[i]));
            for (var k = 0, j = partial.length; k < j; k++) result.push(partial[k]);
          }
        }
        return result;
      };
      
      this.getFormValues = function(tags)
      {
        if (elements.length == 0) return {};
        var values = {};
        var els = this.getFormElements(tags);
        for (var i = 0; i < els.length; i++)
        {
          var el = els[i], value;
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
      
      this.cleanFormValues = function(tags)
      {
        var els = this.getFormElements(tags);
        for (var i = 0; i < els.length; i++)
        {
          var el = els[i];
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
        return this;
      };

      this.addEvent = function(type, fn)
      {
        type = type.toLowerCase();
        for (var i = 0, el; i < elements.length; i++)
        {
          el = elements[i];
          if (el.addEventListener) el.addEventListener(type, fn, false);
          else el.attachEvent('on' + type, fn);
        }
        return this;
      };

      this.removeEvent = function(type, fn)
      {
        type = type.toLowerCase();
        for (var i = 0, el; i < elements.length; i++)
        {
          el = elements[i];
          if (el.removeEventListener) el.removeEventListener(type.toLowerCase(), fn, false);
          else el.detachEvent('on' + type.toLowerCase(), fn);
        }
      };
      
      this.ready = function(handler)
      {
        var bindReady = function(handler)
        {
          var called = false, ready = function()
          {
            if (called) return;
            called = true;
            handler();
          };
          if (document.addEventListener) document.addEventListener('DOMContentLoaded', ready, false);
          else if (document.attachEvent)
          {
            if (document.documentElement.doScroll && window == window.top)
            {
              function tryScroll()
              {
                if (called) return;
                if (!document.body) return;
                try 
                {
                  document.documentElement.doScroll('left');
                  ready();
                }
                catch(e)
                {
                  setTimeout(tryScroll, 0);
                }
              }
              tryScroll();
            }
            document.attachEvent('onreadystatechange', function()
            { 
              if (document.readyState === 'complete') ready();
            });
          }
          if (window.addEventListener) window.addEventListener('load', ready, false)
          else if (window.attachEvent) window.attachEvent('onload', ready);
          else window.onload = ready;
        };
        if (readyList.length == 0)
        {
          bindReady(function() 
          {
            for (var i = 0; i < readyList.length; i++) readyList[i](); 
          });
        }
        readyList.push(handler);
        return this;
      };
      
      this.size = function(value)
      {
        if (ctx)
        {
          if (ctx == window && window.opera || typeof navigator.taintEnabled == 'undefined') return {'width': window.innerWidth, 'height': window.innerHeight};
          var doc = (!document.compatMode || document.compatMode == 'CSS1Compat') ? document.documentElement : document.body;
          if (ctx == window) return {'width': doc.clientWidth, 'height': doc.clientHeight};
          var min = new Query(window).size();
          return {'width': Math.max(doc.scrollWidth, min.width), 'height': Math.max(doc.scrollHeight, min.height)};
        }
        return performer(function(el)
        {
          return {'width': el.offsetWidth, 'height': el.offsetHeight};
        },
        function(el, val)
        {
          if (val.width != null) el.style.width = val.width + 'px';
          if (val.height != null) el.style.height = val.height + 'px';
        }, value);
      };
      
      this.width = function(value)
      {
        if (typeof value != 'undefined') return this.size({'width': value});
        var res = this.size(); if (!res) return;
        if (typeOf(res) == 'array') 
        {
          for (var i = 0, result = []; i < res.length; i++) result[i] = res[i].width; 
          return result;
        }
        return res.width;
      };
      
      this.height = function(value)
      {
        if (typeof value != 'undefined') return this.size({'height': value});
        var res = this.size(); if (!res) return;
        if (typeOf(res) == 'array') 
        {
          for (var i = 0, result = []; i < res.length; i++) result[i] = res[i].height;
          return result;
        }
        return res.height;
      };
      
      this.scrollSize = function(value)
      {
        if (ctx)
        {
          var doc = (!document.compatMode || document.compatMode == 'CSS1Compat') ? document.documentElement : document.body;
          if (typeof value != 'undefined')
          {
            window.scrollTo(typeof value.left != 'undefined' ? value.left : (window.pageXOffset || doc.scrollLeft),
                            typeof value.top != 'undefined' ? value.top : (window.pageYOffset || doc.scrollTop));
            return this;
          }
          return {'left': window.pageXOffset || doc.scrollLeft, 'top': window.pageYOffset || doc.scrollTop, 'width': doc.scrollWidth, 'height': doc.scrollHeight};
        }
        return performer(function(el)
        {
          return {'left': el.scrollLeft, 'top': el.scrollTop, 'width': el.scrollWidth, 'height': el.scrollHeight};
        },
        function(el, val)
        {
          if (val.left != null) el.scrollLeft = val.left;
          if (val.top != null) el.scrollTop = val.top;
        }, value);
      };
      
      this.scrollLeft = function(value)
      {
        if (typeof value != 'undefined') return this.scrollSize({'left': value});
        var res = this.scrollSize(); if (!res) return;
        if (typeOf(res) == 'array') 
        {
          for (var i = 0, result = []; i < res.length; i++) result[i] = res[i].left;
          return result;
        }
        return res.left;
      };
      
      this.scrollTop = function(value)
      {
        if (typeof value != 'undefined') return this.scrollSize({'top': value});
        var res = this.scrollSize(); if (!res) return;
        if (typeOf(res) == 'array') 
        {
          for (var i = 0, result = []; i < res.length; i++) result[i] = res[i].top;
          return result;
        }
        return res.top;
      };
      
      this.offset = function(value)
      {
        return performer(function(el)
        {
          var docElem, body, clientTop, clientLeft, scrollTop, scrollLeft, box, doc;
		        box = {'left': 0, 'top': 0},
		        doc = el && el.ownerDocument;
	         if ((body = doc.body) === el)
          {
		          var top = el.offsetTop, left = el.offsetLeft;
		          if (el.offsetTop !== 1)
            {
              el = new Query(el);
			           top += parseFloat(el.css('marginTop')) || 0;
		           	left += parseFloat(el.css('marginLeft')) || 0;
		          }
		          return {'top': top, 'left': left};
	         }
	         docElem = doc.documentElement;
	         if (typeof el.getBoundingClientRect !=='undefined') box = el.getBoundingClientRect();
	         clientTop = docElem.clientTop || body.clientTop || 0;
	         clientLeft = docElem.clientLeft || body.clientLeft || 0;
	         scrollTop = window.pageYOffset || docElem.scrollTop;
	         scrollLeft = window.pageXOffset || docElem.scrollLeft;
	         return {'left': box.left + scrollLeft - clientLeft, 'top': box.top  + scrollTop - clientTop};
        },
        function(el, val)
        {
          var elem, position, curOffset, curCSSTop, curCSSLeft, curPosition, curTop, curLeft;
          elem = new Query(el); position = elem.css('position');
		        if (position === 'static') elem.css('position', 'relative');
			       curOffset = elem.offset(); curCSSTop = elem.css('top'); curCSSLeft = elem.css('left');
		        if ((position === 'absolute' || position === 'fixed') && (curCSSTop == 'auto' || curCSSLeft == 'auto')) 
          {
			         curPosition = elem.position();
			         curTop = curPosition.top;
			         curLeft = curPosition.left;
		        }
          else
          {
			         curTop = parseFloat(curCSSTop) || 0;
			         curLeft = parseFloat(curCSSLeft) || 0;
		        }
          if (val.top != null) el.style.top = val.top - curOffset.top + curTop + 'px';
          if (val.left != null) el.style.left = val.left - curOffset.left + curLeft + 'px';
		      }, value);
      };
    
      this.offsetParent = function()
      {
        if (elements.length == 0) return;
        var el = elements[0], offsetParent = el.offsetParent;
			     while (offsetParent && (!rroot.test(offsetParent.nodeName) && new Query(offsetParent).css('position') === 'static'))
        {
				      offsetParent = offsetParent.offsetParent;
			     }
			     return new Query(offsetParent || document.body);
      };
      
      this.position = function()
      {
        return performer(function(el)
        {
          var elem = new Query(el), offsetParent = self.offsetParent(), offset = self.offset();
          var parentOffset = rroot.test(offsetParent.get()[0].nodeName) ? {'left': 0, 'top': 0} : offsetParent.offset();
		        offset.top -= parseFloat(elem.css('marginTop')) || 0;
		        offset.left -= parseFloat(elem.css('marginLeft')) || 0;
		        parentOffset.top += parseFloat(offsetParent.css('borderTopWidth')) || 0;
	      	  parentOffset.left += parseFloat(offsetParent.css('borderLeftWidth')) || 0;
		        return {'left': offset.left - parentOffset.left, 'top': offset.top - parentOffset.top};
        });
      };
      
      this.fade = function(show, opacity)
      {
        if (elements.length == 0) return this;
        if (!show) 
        {
          this.display('none');
          return this;
        }
        if (typeof opacity == 'undefined') opacity = 0.5;
        var size = new Query(document).size(), el = elements[0];
        el.style.position = 'fixed';
        el.style.top = '0px';
        el.style.left = '0px';
        el.style.width = size.width + 'px';
        el.style.height = size.height + 'px';
        new Query(el).css('opacity', opacity).display('');
        return this;
      };
      
      this.centre = function(overflow)
      {
        if (elements.length == 0) return this;
        var el = new Query(elements[0]), size = el.size();
        var win = new Query(window), winSize = win.size(), scrollSize = win.scrollSize();
        var left = (winSize.width - size.width) / 2 + scrollSize.left, top = (winSize.height - size.height) / 2 + scrollSize.top;
        if (!overflow)
        {
          if (top < 0) top = 0;
          if (left < 0) left = 0;
        }
        el.offset({'left': left, 'top': top});
        return this;
      };
      
      this.scroll = function()
      {
        if (elements.length == 0) return this;
        var offset = new Query(elements[0]).offset();
        window.scrollTo(offset.left, offset.top);
        return this;
      };
      
      this.focus = function(x, y)
      {
        if (elements.length == 0) return this;
        var el = elements[0], elem = new Query(el);
        var parent = el.parentNode, flag = false;
        if (elem.css('position') !== 'fixed')
        {
          while (parent && !rroot.test(parent.nodeName))
          {
            if (new Query(parent).css('position') === 'fixed')
            {
              flag = true;
              break;
            } 
            parent = parent.parentNode;
          }
        }
        else flag = true;
        if (!flag)
        {
          x = x || 0;
          y = y || 0;
          var offset = elem.offset(), winSize = new Query(document).size(), scroll = new Query(document).scrollSize();
          if (offset.left > winSize.width + scroll.left || offset.left < scroll.left || offset.top > winSize.height + scroll.top || offset.top < scroll.top) 
          {
            window.scrollTo(offset.left + parseFloat(x), offset.top + parseFloat(y));
          }
        }
        try {el.focus();} catch (e){}
      };
      
      this.select = function() 
      {
        return performer(function(el)
        {
          var range;
          if (document.selection) 
          {
            range = document.body.createTextRange();
            range.moveToElementText(el);
            range.select()
          }
          else if (window.getSelection) 
          {
            range = document.createRange();
            range.selectNode(el);
            window.getSelection().addRange(range);
          }
        });
      };
    };
  
    this.$ = function(selector, context)
    {
      return new Query(selector, context);
    };
    
    this.query = function(selector, context)
    {
      return new Query(selector, context).elements;
    };
    
    this.each = function(selector, handler)
    {
      return new Query(selector).each(handler);
    };
    
    this.val = function(selector, value)
    {
      return new Query(selector).val(value);
    };
    
    this.hasClass = function(selector, className)
    {
      return new Query(selector).hasClass(className);
    };
    
    this.addClass = function(selector, className)
    {
      return new Query(selector).addClass(className);
    };
    
    this.removeClass = function(selector, className)
    {
      return new Query(selector).removeClass(className);
    };
    
    this.toggleClass = function(selector, className)
    {
      return new Query(selector).toggleClass(className);
    };
    
    this.css = function(selector, property, value)
    {
      return new Query(selector).css(property, value);
    };
    
    this.display = function(selector, display, expire)
    {
      return new Query(selector).display(display, expire);
    };
    
    this.message = function(selector, html, expire)
    {
      return new Query(selector).message(html, expire);
    };
    
    this.remove = function(selector)
    {
      return new Query(selector).remove();
    };
    
    this.insert = function(selector, html)
    {
      return new Query(selector).insert(html);
    };
    
    this.replace = function(selector, html)
    {
      return new Query(selector).replace(html);
    };
    
    this.inject = function(selector, html, mode)
    {
      return new Query(selector).inject(html, mode);
    }
    
    this.getFormElements = function(selector, tags)
    {
      return new Query(selector).getFormElements(tags);
    };
    
    this.getFormValues = function(selector, tags)
    {
      return new Query(selector).getFormValues(tags);
    };
    
    this.cleanFormValues = function(selector, tags)
    {
      return new Query(selector).cleanFormValues(tags);
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
    
    this.stopEvent = function(event)
    {
      if (event.stopPropagation) event.stopPropagation();
      else event.cancelBubble = true;
      if (event.preventDefault) event.preventDefault();
      else event.returnValue = false;
    };
    
    this.addEvent = function(selector, type, fn)
    {
      return new Query(selector).addEvent(type, fn);
    };
    
    this.removeEvent = function(selector, type, fn)
    {
      return new Query(selector).removeEvent(type, fn);
    };
    
    this.ready = function(handler)
    {
      return new Query(document).ready(handler);
    };
    
    this.size = function(selector, value)
    {
      return new Query(selector).size(value);
    };
    
    this.width = function(selector, value)
    {
      return new Query(selector).width(value);
    };
    
    this.height = function(selector, value)
    {
      return new Query(selector).height(value);
    };
    
    this.scrollSize = function(selector, value)
    {
      return new Query(selector).scrollSize(value);
    };
    
    this.scrollLeft = function(selector, value)
    {
      return new Query(selector).scrollLeft(value);
    };
    
    this.scrollTop = function(selector, value)
    {
      return new Query(selector).scrollTop(value);
    };
    
    this.offset = function(selector, value)
    {
      return new Query(selector).offset(value);
    };
    
    this.offsetParent = function(selector)
    {
      return new Query(selector).offsetParent();
    };
    
    this.position = function(selector)
    {
      return new Query(selector).position();
    };
    
    this.fade = function(selector, show, opacity)
    {
      return new Query(selector).fade(show, opacity);
    };
    
    this.centre = function(selector, overflow)
    {
      return new Query(selector).centre(overflow);
    };
    
    this.scroll = function(selector)
    {
      return new Query(selector).scroll();
    };
    
    this.focus = function(selector, x, y)
    {
      return new Query(selector).focus(x, y);
    };
    
    this.select = function(selector)
    {
      return new Query(selector).select();
    };
  };
  
  this.ajax = new function()
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
          if (typeof onComplete == 'function') onComplete(JSON.parse(ajx.response), xhr);
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
          case 'inject':
            a.dom.inject(args[0], args[1], args[2]);
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
};

var a = aleph;