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
 
// Base class of controls.
// *************************************************************************************************************

var Control = (function(undefined)
{
  var Control = function(el)
  {
    this.el = el;
    this.events = {};
  };
  
  Control.prototype.vs = function(ignore)
  {
    var el = this.el.get(0), attr, attrs = el.attributes, res = {attrs: {}};
    ignore = ignore || [];
    for (var i = attrs.length - 1; i >= 0; i--)
    {
      attr = attrs[i].name;
      if (attr == 'id' || attr == 'data-ctrl' || attr == 'value' && el['value'] != undefined || ignore.indexOf(attr) >= 0) continue;
      res.attrs[attr.substr(0, 5) == 'data-' ? attr.substr(5) : attr] = this.el.attr(attr);
    }
    if (el['value'] != undefined) res['value'] = this.value();
    return res;
  };
  
  Control.prototype.container = function()
  {
    var el = $('#container_' + this.el.attr('id'));
    return el.length ? el : this.el;
  };

  Control.prototype.type = function()
  {
    return this.el.attr('data-ctrl');
  };

  Control.prototype.value = function()
  {
    return this.el.val();
  };
  
  Control.prototype.clean = function()
  {
    this.el.val('');
    return this;
  };
  
  Control.prototype.validate = function()
  {
    return true;
  };
  
  Control.prototype.init = function()
  {
    this.el = $('#' + this.el.attr('id'));
    return this.refreshEvents();
  };
  
  Control.prototype.refreshAttributes = function(attributes, removedAttributes)
  {
    var container = this.container();
    for (var attr in removedAttributes) 
    {
      attr = removedAttributes[attr];
      if (attr.substr(0, 10) != 'container-') this.el.removeAttr(attr);
      else container.removeAttr(attr.substr(10));
    }
    var value;
    for (var attr in attributes)
    {
      value = attributes[attr];
      if (attr.substr(0, 10) != 'container-')
      {
        if (attr == 'checked') this.el.prop(attr, value);
        this.el.attr(attr, value);
      }
      else
      {
        if (attr == 'checked') container.prop(attr.substr(10), value);
        container.attr(attr.substr(10), value);
      }
    }
  };
  
  Control.prototype.refresh = function(obj, removedAttributes)
  {
    if (typeof obj == 'object') 
    {
      this.refreshAttributes(obj, removedAttributes);
    }
    else
    {
      var id = this.el.attr('id');
      this.container().replaceWith(obj);
      this.el = $('#' + id);
    }
    return this.init();
  };
  
  Control.prototype.remove = function()
  {
    this.removeEvents(true);
    this.container().remove();
  };
    
  Control.prototype.bind = function(euid, type, callback, check, toContainer)
  {
    this.unbind(euid);
    this.addEvent(euid, type, callback, check, toContainer);
    if (!check || check()) (toContainer ? $('#container_' + this.el.attr('id')) : this.el).bind(type, callback);
    return this;
  };
    
  Control.prototype.unbind = function(euid)
  {
    this.removeEvent(euid);
    delete this.events[euid];
    return this;
  };
    
  Control.prototype.addEvent = function(euid, type, callback, check, toContainer)
  {
    this.events[euid] = {'type': type, 'callback': callback, 'check': check, 'toContainer': toContainer};
    return this;
  };
    
  Control.prototype.removeEvent = function(euid)
  {
    var e = this.events[euid];
    if (e != undefined) (e.toContainer ? $('#container_' + this.el.attr('id')) : this.el).unbind(e.type, e.callback);
    return this;
  };
    
  Control.prototype.removeEvents = function(completely)
  {
    var container = $('#container_' + this.el.attr('id'));
    if (container) container.unbind();
    this.el.unbind();
    if (completely) this.events = {};
    return this;
  };
    
  Control.prototype.restoreEvents = function()
  {
    var e, container = $('#container_' + this.el.attr('id'));
    for (var euid in this.events)
    {
      e = this.events[euid];
      if (!e.check || e.check()) (e.toContainer ? container : this.el).bind(e.type, e.callback);
    }
    return this;
  };
   
  Control.prototype.refreshEvents = function()
  {
    return this.removeEvents().restoreEvents();
  };
  
  return Control;
})();

// Class for manipulation with POM.
// *************************************************************************************************************

var POM = (function(undefined)
{
  var vs = {}, tags = {}, controls = {};
 
  var inherit = function(subClass, parentClass)
  {
    var F = function(){}
    F.prototype = parentClass.prototype;
    subClass.prototype = new F();
    subClass.prototype.constructor = subClass;
    subClass.superclass = parentClass.prototype;
    return subClass;
  };

  var POM = function(){};
  
  POM.prototype.registerControl = function(tag, newControl, parentControl)
  {
    return tags[tag] = inherit(newControl, parentControl || Control);
  };
  
  POM.prototype.registerValidator = function(tag, newValidator, parentValidator)
  {
    return tags[tag] = inherit(newValidator, parentValidator || Validator);
  };
  
  POM.prototype.get = function(id, context)
  {
    if (controls[id]) return controls[id];
    var el = $('#' + id, context);
    if (el.length == 0)
    {
      el = $("[id^='" + id + "'][data-ctrl]", context);
      if (el.length == 0)
      {
        delete controls[id];
        return false;
      }
    }
    id = el.attr('id');
    if (id == '') return false;
    var type = el.attr('data-ctrl').toLowerCase();
    if (tags[type] == undefined) return false;
    return controls[id] = new tags[type](el).init();
  };
  
  POM.prototype.setVS = function()
  {
    vs = {};
    $('[data-ctrl]').each(function()
    {
      var ctrl = POM.prototype.get(this.id);
      if (ctrl) vs[this.id] = ctrl.vs();
    });
    for (var id in controls) if (!vs[id]) delete controls[id];
  };
  
  POM.prototype.getVS = function()
  {
    var res = {};
    $('[data-ctrl]').each(function(index, el)
    {
      var cvs = POM.prototype.get(el.id).vs();
      if (typeof vs[el.id] == 'undefined') res[el.id] = cvs;
      else
      {
        res[el.id] = {};
        for (var attr in cvs['attrs'])
        {
          if (JSON.stringify(vs[el.id]['attrs'][attr]) !== JSON.stringify(cvs['attrs'][attr])) 
          {
            if (res[el.id]['attrs'] == undefined) res[el.id]['attrs'] = {};
            res[el.id]['attrs'][attr] = cvs['attrs'][attr];
          }
        }
        for (var attr in vs[el.id]['attrs'])
        {
          if (cvs['attrs'][attr] == undefined) 
          {
            if (res[el.id]['removed'] == undefined) res[el.id]['removed'] = [];
            res[el.id]['removed'].push(vs[el.id]['attrs'][attr]);
          }
        }
        if (JSON.stringify(vs[el.id]['value']) !== JSON.stringify(cvs['value'])) res[el.id]['value'] = cvs['value'];
        if (res[el.id]['attrs'] == undefined && res[el.id]['removed'] == undefined && res[el.id]['value'] == undefined) delete res[el.id];
      }
    });
    return {'ts': new Date().valueOf(), 'vs': res};
  };
  
  POM.prototype._refreshPOMTree = function(data)
  {
    var id, ctrl, params;
    
    // Creating (moving) controls.
    params = data.created;
    for (id in params)
    {
      ctrl = POM.prototype.get(id);
      if (ctrl) 
      {
        ctrl.remove();
        delete controls[id];
        delete data.removed[id];
      }
    }
    for (id in params)
    {
      var mode = params[id]['mode'], cid = params[id]['id'], html = params[id]['html']
      if (!mode || !cid) continue;
      ctrl = $('#container_' + cid).length && $("[id='" + cid + "'][data-ctrl]").length ? '#container_' + cid : '#' + cid;
      switch (mode)
      {
        case 'top':
          $(ctrl).prepend(html);
          break;
        case 'bottom':
          $(ctrl).append(html);
          break;
        case 'before':
          $(ctrl).before(html);
          break;
        case 'after':
          $(ctrl).after(html);
          break;
        case 'replace': 
          delete controls[cid];
          delete vs[cid];
          $(ctrl).replaceWith(html)
          break;
      }
      ctrl = $('#container_' + id).length && $("[id='" + id + "'][data-ctrl]").length ? '#container_' + id : '#' + id;
      $('[data-ctrl]', ctrl).each(function()
      {
        delete controls[this.id];
      });
    }
    
    // Deleting of controls
    params = data.removed;
    for (id in params)
    {
      ctrl = POM.prototype.get(id);
      if (ctrl) 
      {
        ctrl.remove();
        delete controls[id];
      }
    }
    
    // Refreshing of controls.
    params = data.refreshed;
    var i, flag, panels = {};
    for (id in params)
    {
      ctrl = POM.prototype.get(id);
      if (!ctrl) 
      {
        delete params[id];
        continue;
      }
      if (ctrl.getControls && (typeof params[id] != 'object'))
      {
        for (i in panels)
        {
          if (ctrl.has(panels[i]))
          {
            delete panels[i];
          }
        }
        panels[id] = ctrl;
      }
    }
    for (id in params)
    {
      ctrl = POM.prototype.get(id);
      for (i in panels)
      {
        if (panels[i].has(ctrl))
        {
          delete params[id];
          break;
        }
      }
    }
    for (id in params)
    {
      ctrl = POM.prototype.get(id);
      if (typeof params[id] == 'object') ctrl.refresh(params[id]['attrs'], params[id]['removed']);
      else ctrl.refresh(params[id]);
    }
  };
  
  POM.prototype.getValidators = function(groups)
  {
    var validators = [];
    if (groups == '*')
    {
      $('[data-groups][data-controls]').each(function()
      {
        var validator = POM.prototype.get($(this).attr('id'));
        if (validator) validators.push(validator);
      });
    }
    else
    {
      groups = (groups || 'default').split(/\s*,\s*/);
      $('[data-groups][data-controls]').each(function()
      {
        var validator = POM.prototype.get($(this).attr('id'));
        if (validator && validator.hasGroup(groups)) validators.push(validator);
      });
    }
    return validators;
  };
  
  POM.prototype.validate = function(groups, classInvalid, classValid)
  {
    var validators = POM.prototype.getValidators(groups);
    validators.sort(function(validator1, validator2)
    {
      return validator1.getIndex() - validator2.getIndex();
    });
    var i, cid, ctrl, validator, first, offset, firstOffset, flag = true, result = {};
    for (i = 0; i < validators.length; i++)
    {
      validator = validators[i];
      if (validator.validate()) 
      {
        for (cid in validator.result)
        {
          if (result[cid] == undefined) result[cid] = true;
        }
      }
      else
      {
        flag = false;
        for (var cid in validator.result)
        {
          if (!validator.result[cid]) result[cid] = false;
          else if (result[cid] == undefined) result[cid] = true;
        }
      }
    }
    for (cid in result)
    {
      ctrl = $('#container_' + cid);
      ctrl = ctrl.length > 0 ? ctrl : $('#' + cid); 
      if (result[cid]) ctrl.removeClass(classInvalid).addClass(classValid);
      else
      {
        offset = ctrl.offset();
        ctrl.removeClass(classValid).addClass(classInvalid);
        if (!first || firstOffset.top > offset.top || firstOffset.top == offset.top && firstOffset.left > offset.left) 
        {
          first = ctrl;
          firstOffset = offset;
        }
      }
    }
    if (first) first.focus();  
    return flag;
  };
  
  POM.prototype.reset = function(groups, classInvalid, classValid)
  {
    var validators = POM.prototype.getValidators(groups);
    $.each(validators, function(index, validator)
    {
      var controls = validator.getControls();
      for (var i = 0; i < controls.length; i++)
      {
        POM.prototype.get(controls[i]).el.removeClass(classInvalid).addClass(classValid);
      }
      validator.setState(true);
    });
  };
  
  return POM;
})();

// Class for sending and processing Ajax requests.
// *************************************************************************************************************
 
var Ajax = (function(undefined)
{
  var options, cursor;
  
  var showLoader = function()
  {
    cursor = document.body.style.cursor;
    document.body.style.cursor = 'wait';
  };
  
  var hideLoader = function()
  {
    document.body.style.cursor = cursor;
  };
  
  var filter = function(data, type)
  {
    if (data.length == 0) 
    {
      POM.prototype.setVS();
      return data;
    }
    var sep = data.substr(0, 13);
    var script, p = data.lastIndexOf(sep);
    if (p < 1) script = data;
    else script = data.substr(13, p - 13);
    if (script.length) $.globalEval(script);
    POM.prototype.setVS();
    return p > 0 ? data.substr(p + 13) : 'null';
  };

  var Ajax = function()
  {
    this.setup({'global': true,
                'type': 'POST',
                'url': window.location.href,
                'ajaxStart': showLoader,
                'ajaxStop': hideLoader,
                'dataFilter': filter});
  };
  
  Ajax.prototype.setup = function(settings, isOneOff)
  {
    if (isOneOff) 
    {
      options = settings;
      return this;
    }
    if (settings.global)
    {
      if (settings.ajaxStart) $(document).ajaxStart(settings.ajaxStart);
      if (settings.ajaxStop) $(document).ajaxStop(settings.ajaxStop);
      if (settings.ajaxComplete) $(document).ajaxComplete(settings.ajaxComplete);
      if (settings.ajaxError) $(document).ajaxError(settings.ajaxError);
      if (settings.ajaxSuccess) $(document).ajaxSuccess(settings.ajaxSuccess);
      if (settings.ajaxSend) $(document).ajaxSend(settings.ajaxSend);
    }
    $.ajaxSetup(settings);
    return this;
  };
  
  Ajax.prototype.doit = function(delegate)
  {
    var data = {'ajax-method': delegate,
                'ajax-args': Array.prototype.slice.call(arguments, 1),
                'ajax-key': $(document.body).attr('id'), 
                'ajax-vs': POM.prototype.getVS()};
    var settings = {'data': data};
    if (options) 
    {
      settings = options;
      settings.data = data;
      options = null;
    }
    return $.ajax(settings);
  };
  
  Ajax.prototype.action = function(act)
  {
    var time, args = Array.prototype.slice.call(arguments, 1);
    act = act.toLowerCase();
    switch (act)
    {
      case 'reload':
        time = 0;
        break;
      case 'alert':
      case 'redirect':
      case 'focus':
      case 'remove':
        time = 1;
        break;
      case 'display':
      case 'message':
      case 'inject':
        time = 3;
        break;
      default:
        time = 2;
        break;
    }
    time = typeof args[time] == 'undefined' ? 0 : args[time];
    var process = function()
    {
      switch (act)
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
          if (typeof args[1] == 'undefined') args[1] = $(args[0]).css('display') == 'none' ? '' : 'none';
          if (typeof args[2] == 'undefined' || args[2] == 0) $(args[0]).css('display', args[1]);
          else
          {
            var display = $(args[0]).css('display');
            $(args[0]).css('display', args[1]);
            setTimeout(function(){$(args[0]).css('display', display);}, args[2]);
          }
          break;
        case 'message':
          if (typeof args[2] == 'undefined' || args[2] == 0) $(args[0]).html(args[1]);
          else 
          {
            var html = $(args[0]).html();
            $(args[0]).html(args[1]);
            setTimeout(function(){$(args[0]).html(html);}, args[2]);
          }
          break;
        case 'focus':
          $(args[0]).focus();
          break;
        case 'addclass':
          $(args[0]).addClass(args[1]);
          break;
        case 'removeclass':
          $(args[0]).removeClass(args[1]);
          break;
        case 'toggleclass':
          $(args[0]).toggleClass(args[1]);
          break;
        case 'remove':
          $(args[0]).remove();
          break;
        case 'insert':
          $(args[0]).html(args[1]);
          break;
        case 'replace':
          $(args[0]).replaceWith(args[1]);
          break;
        case 'inject':
          switch (args[2].toLowerCase())
          {
            case 'top':
              $(args[0]).prepend(args[1]);
              break;
            case 'botom':
              $(args[0]).append(args[1]);
              break;
            case 'before':
              $(args[0]).before(args[1]);
              break;
            case 'after':
              $(args[0]).after(args[1]);
              break;
          }
          break;
        case 'script':
          $.globalEval(args[0]);
          break;
      }
    };
    time > 0 ? setTimeout(process, time) : process();
  };
  
  return Ajax;
})();

// Class, that aggregates POM and Ajax instances.
// *************************************************************************************************************

var Aleph = (function(undefined)
{
  var Aleph = function()
  {
    this.pom = new POM();
    this.ajax = new Ajax();
  };
  
  $(function()
  {
    POM.prototype.setVS();
  });
  
  return Aleph;
})();

var $a = new Aleph();

// Registering standard controls.
// *************************************************************************************************************

var Any = function(el)
{
  Any.superclass.constructor.call(this, el);
};
$a.pom.registerControl('any', Any);

var HyperLink = function(el)
{
  HyperLink.superclass.constructor.call(this, el);
};
$a.pom.registerControl('hyperlink', HyperLink);

var Image = function(el)
{
  Image.superclass.constructor.call(this, el);
};
$a.pom.registerControl('image', Image);

var Button = function(el)
{
  Button.superclass.constructor.call(this, el);
};
$a.pom.registerControl('button', Button);

var Input = function(el)
{
  Input.superclass.constructor.call(this, el);
  
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};
$a.pom.registerControl('input', Input);

var Hidden = function(el)
{
  Hidden.superclass.constructor.call(this, el);
};
$a.pom.registerControl('hidden', Hidden);

var TextBox = function(el)
{
  TextBox.superclass.constructor.call(this, el);
 
  var id = el.attr('id');
  
  var focus = function()
  {
    var el = $(this);
    if (el.val() == el.attr('data-default')) el.val('');
  };
  
  var blur = function()
  {
    var el = $(this);
    if (!el.val()) el.val(el.attr('data-default'));
  };
  
  var check = function()
  {
    return !!$('#' + id).attr('data-default');
  };
    
  this.addEvent(id + '_txt_e1', 'focus', focus, check);
  this.addEvent(id + '_txt_e2', 'blur', blur, check);
    
  this.init = function()
  {
    TextBox.superclass.init.call(this);
    var dv = this.el.attr('data-default');
    if (dv) if (!this.el.val()) this.el.val(dv);
    return this;
  };
  
  this.refresh = function(obj, removedAttributes)
  {
    focus.call(this.el);
    return TextBox.superclass.refresh.call(this, obj, removedAttributes);
  };
    
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
    
  this.clean = function()
  {
    this.el.val(this.el.attr('data-default') || '');
    return this;
  };
};
$a.pom.registerControl('textbox', TextBox);

var DropDownBox = function(el)
{
  DropDownBox.superclass.constructor.call(this, el);
  
  this.validate = function(validator)
  {
    var value = this.value();
    if ($.isArray(value)) value = value.join('');
    return validator.check(value);
  };
};
$a.pom.registerControl('dropdownbox', DropDownBox);

var CheckBox = function(el)
{
  CheckBox.superclass.constructor.call(this, el);
  
  this.vs = function()
  {
    var res = CheckBox.superclass.vs.call(this);
    res['attrs']['checked'] = this.el.prop('checked') ? 'checked' : '';
    return res;
  }
    
  this.validate = function(validator)
  {
    if (validator.type() == 'vrequired') return validator.check(this.el.prop('checked'));
    return true;
  };
  
  this.clean = function()
  {
    this.el.prop('checked', false);
    return this;
  };
};
$a.pom.registerControl('checkbox', CheckBox);

var Radio = function(el)
{
  Radio.superclass.constructor.call(this, el);
  
  this.vs = function()
  {
    var res = Radio.superclass.vs.call(this);
    res['attrs']['checked'] = this.el.prop('checked') ? 'checked' : '';
    return res;
  }
    
  this.validate = function(validator)
  {
    if (validator.type() == 'vrequired') return validator.check(this.el.prop('checked'));
    return true;
  };
  
  this.clean = function()
  {
    this.el.prop('checked', false);
    return this;
  };
};
$a.pom.registerControl('radio', Radio);

var Autofill = function(el)
{
  Autofill.superclass.constructor.call(this, el);
   
  var i, t, xhr, id = el.attr('id');
  var hideList = function()
  {
    $('#list_' + id).hide();
    $(document.body).unbind('click', hideList);
  };

  this.addEvent(id + '_af_e1', 'keyup', function(e)
  {
    if (e.keyCode == 13 || e.keyCode == 9 || e.keyCode >= 37 && e.keyCode <= 40) return false;
    if (t) clearTimeout(t);
    t = setTimeout(function()
    {
      var el = $('#' + id), list = $('#list_' + id), callback = el.attr('data-callback') || '@' + id + '->search';
      list.hide();
      if (xhr) xhr.abort();
      xhr = $a.ajax.setup({'global': false}, true).doit(el.attr('data-callback'), e.currentTarget.value).done(function(html)
      {
        if (html != '')
        {
          var offset = el.offset();
          list.html(html);
          list.css({'position': 'absolute', 'display': 'block'});
          list.offset({'left': offset.left, 'top': offset.top + el.outerHeight()});
          list.scrollTop(0);
          $(document.body).click(hideList);
        }
        else list.hide();
        xhr = null; i = -1;
      });
    }, el.attr('data-timeout') || 0);
  });
    
  this.addEvent(id + '_af_e2', 'keydown', function(e)
  {
    if (xhr || e.keyCode != 13 && e.keyCode != 38 && e.keyCode != 40) return;
    var el = $('#' + id), list = $('#list_' + id);
    if (e.keyCode == 13) 
    {
      if (list.css('display') != 'none') $('#list_' + id + ' li:eq(' + i + ')').click();
    }
    else
    {
      var css = el.attr('data-activeitemclass'), items = $('#list_' + id + ' li').removeClass(css).length;
      if (items == 0) return;
      if (list.css('display') == 'none') list.show();
      i += e.keyCode - 39;
      if (i < 0) i = items - 1;
      else if (i >= items) i = 0;
      var item = $('#list_' + id + ' li:eq(' + i + ')').addClass(css);
      list.scrollTop(item[0].offsetTop - (list.height() - item.height()) / 2);
    }
  });
  
  this.addEvent(id + '_af_e3', 'blur', function(e)
  {
    if (xhr) xhr.abort();
  });
};
$a.pom.registerControl('autofill', Autofill, TextBox);

var Paginator = function(el)
{
  Paginator.superclass.constructor.call(this, el);
};
$a.pom.registerControl('paginator', Paginator);

var CKEditor = function(el)
{
  CKEditor.superclass.constructor.call(this, el);
  
  this.vs = function()
  {
    return CKEditor.superclass.vs.call(this, ['style']);
  }
  
  this.init = function()
  {
    CKEditor.superclass.init.call(this);
    if (this.editor) this.editor.destroy();
    this.editor = CKEDITOR.replace(el.attr('id'), eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  this.remove = function()
  {
    if (this.editor) this.editor.destroy();
    CKEditor.superclass.remove.call(this);
  };
  
  this.value = function()
  {
    return CKEDITOR.instances[this.el.attr('id')].getData();
  }
  
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
  
  this.clean = function()
  {
    CKEDITOR.instances[this.el.attr('id')].setData('');
    return this;
  };
};
$a.pom.registerControl('ckeditor', CKEditor);

var Upload = function(el)
{
  Upload.superclass.constructor.call(this, el);
  
  this.init = function()
  {
    var bind = this, id = this.el.attr('id'), callback = this.el.attr('data-callback');
    var submit = function(e, data) 
    {
      data.formData = {'ajax-method': callback,
                       'ajax-args[]': id,
                       'ajax-key': $(document.body).attr('id'), 
                       'ajax-vs': JSON.stringify(POM.prototype.getVS())};
    };
    var always = function(e, data) 
    {
      bind.el = $('#' + id);
    };
    Upload.superclass.init.call(this);
    var ops = eval('(' + (this.el.attr('data-settings') || '{}') + ')');
    ops.dataType = 'json';
    ops.paramName = id + (this.el.attr('multiple') ? '[]' : '');
    ops.multipart = true;
    this.el.fileupload(ops)
           .unbind('fileuploadsubmit', submit)
           .unbind('fileuploadalways', always)
           .bind('fileuploadalways', always);
    if (callback) this.el.bind('fileuploadsubmit', submit);
    return this;
  };
  
  this.remove = function()
  {
    this.el.fileupload('destroy');
    Upload.superclass.remove.call(this);
  };
  
  this.value = function()
  {
    return '';
  }
};
$a.pom.registerControl('upload', Upload);

var Panel = function(el)
{
  Panel.superclass.constructor.call(this, el);
    
  this.get = function(id)
  {
    return $a.pom.get(id, this.el);
  };
  
  this.has = function(id)
  {
    if (id instanceof Control) id = id.el.attr('id');
    var ctrl = this.get(id);
    return ctrl !== false && ctrl !== this; 
  };
  
  this.getControls = function(isRecursion)
  {
    var ctrl, ctrls = [];
    if (isRecursion)
    {
      $('[data-ctrl]', this.el).each(function()
      {
        ctrl = $a.pom.get(this.id);
        if (ctrl) ctrls.push(ctrl);
      });
    }
    else
    {
      var flag, panels = [];
      $('[data-ctrl]', this.el).each(function()
      {
        ctrl = $a.pom.get(this.id);
        if (ctrl)
        {
          flag = true;
          for (var i in panels)
          {
            if (panels[i].has(ctrl))
            {
              flag = false;
              break;
            }
          }
          if (flag)
          {
            if (ctrl instanceof Panel) panels.push(ctrl);
            ctrls.push(ctrl);
          }
        }
      });
    }
    return ctrls;
  };
    
  this.clean = function(isRecursion)
  {
    $.each(this.getControls(isRecursion), function(index, ctrl)
    {
      ctrl.clean();
    });
    return this;
  };
  
  this.check = function(flag, isRecursion)
  {
    $.each(this.getControls(isRecursion), function(index, ctrl)
    {
      if (ctrl instanceof CheckBox) ctrl.el.prop('checked', flag);
    });
    return this;
  };
  
  this.refresh = function(obj, removedAttributes)
  {
    Panel.superclass.refresh.call(this, obj, removedAttributes);
    if (typeof obj != 'object')
    {
      $.each(this.getControls(true), function(index, ctrl)
      {
        ctrl.init();
      });
    }
    return this.init();
  };
};
$a.pom.registerControl('panel', Panel);

var visiblePopupIdentifiers = [];
var Popup = function(el)
{
  Popup.superclass.constructor.call(this, el);
  
  var bind = this, id = el.attr('id');
  
  var stop = function(e)
  {
    e.stopPropagation();
  };
  
  var hide = function(e)
  {
    var cid = visiblePopupIdentifiers.pop();
    if (id != cid) visiblePopupIdentifiers.push(cid);
    else
    {
      bind.hide();
      e.stopPropagation();
    } 
  };
  
  var escape = function(e)
  {
    if (e.which == 27) 
    {
      var cid = visiblePopupIdentifiers.pop();
      if (id != cid) visiblePopupIdentifiers.push(cid);
      else
      {
        bind.hide();
        e.stopPropagation();
      } 
    }
  };
  
  this.show = function(center)
  {
    if (this.el.attr('data-overlay'))
    {
      var overlay, selector = this.el.attr('data-overlayselector');
      if (selector) overlay = $(selector);
      else
      {
        overlay = $('#overlay_' + id);
        if (overlay.length == 0)
        {
          var overlayClass = this.el.attr('data-overlayclass');
          overlay = $(document.createElement('div'));
          overlay.attr('id', 'overlay_' + id);
          if (overlayClass) overlay.addClass(overlayClass);
          else overlay.css({position: 'fixed', left: 0, top: 0, width: '100%', height: '100%', backgroundColor: '#000000', opacity: 0.5});
          $(document.body).append(overlay);
        }        
      }  
      if (overlay) 
      {
        overlay.css('z-index', this.el.css('z-index') - 1);
        overlay.show();
      }
    }
    if (this.el.attr('data-closebydocument'))
    {
      $(this.el).unbind('click', stop).click(stop);
      $(document.body).unbind('click', hide).click(hide);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).unbind('keyup', escape).keyup(escape);
    }
    var btnClose = this.el.attr('data-closebuttons');
    if (btnClose) $(btnClose).unbind('click', hide).click(hide);
    var position = this.el.css('position');
    if (position != 'fixed' && position != 'absolute') 
    {
      this.el.css('position', 'fixed');
      position = 'fixed';
    }
    if (center)
    {
      this.el.css({top: (($(window).height() - this.el.outerHeight()) / 2) + (position != 'fixed' ? $(window).scrollTop() : 0) + 'px',
                   left: (($(window).width() - this.el.outerWidth()) / 2) + (position != 'fixed' ? $(window).scrollLeft() : 0) + 'px'});
    }
    this.el.show();
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
    visiblePopupIdentifiers.push(id);
  };
  
  this.hide = function()
  {
    this.el.hide();
    if (this.el.attr('data-overlay'))
    {
      var overlay = $(this.el.attr('data-overlayselector') || '#overlay_' + this.el.attr('id'));
      if (overlay) overlay.hide();
    }
    if (this.el.attr('data-closebydocument'))
    {
      $(document.body).unbind('click', hide);
      $(this.el).unbind('click', stop);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).unbind('keyup', escape);
    }
    if (this.el.attr('data-closebuttons')) $(this.el.attr('data-closebuttons')).unbind('click', hide);
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
  };
  
  this.remove = function()
  {
    this.hide();
    Popup.superclass.remove.call(this);
  };
};
$a.pom.registerControl('popup', Popup, Panel);

var Grid = function(el)
{
  Grid.superclass.constructor.call(this, el);
};
$a.pom.registerControl('grid', Grid, Panel);

// Base class of validators.
// *************************************************************************************************************

var Validator = function(el)
{
  Validator.superclass.constructor.call(this, el);
     
  this.result = {};
      
  this.setState = function(flag)
  {
    if (this.el.attr('data-hiding') == 1)
    {
      if (flag) this.el.hide();
      else this.el.show();
    }
    else
    {
      if (flag) this.el.html('');
      else this.el.html(this.el.attr('data-text'));
    }
    this.el.attr('data-state', flag ? '1' : '');
    this.el.trigger(flag ? 'valid' : 'invalid', [this]);
    return this;
  };
    
  this.getState = function()
  {
    return this.el.attr('data-state') == '1';
  };
    
  this.getControls = function()
  {
    var ctrls = this.el.attr('data-controls');
    if (!ctrls) return [];
    return ctrls.split(/\s*,\s*/);
  };
    
  this.getGroups = function()
  {
    return (this.el.attr('data-groups') || 'default').split(/\s*,\s*/)
  };
  
  this.getMode = function()
  {
    return (this.el.attr('data-mode') || 'AND').toUpperCase();
  };
   
  this.getIndex = function()
  {
    return parseInt(this.el.attr('data-index')) || 0;
  };

  this.hasGroup = function(group)
  {
    var groups = this.getGroups();
    if (!$.isArray(group)) return $.inArray(group, groups);
    for (var i = 0; i < group.length; i++) if ($.inArray(group[i], groups) != -1) return true;
    return false;
  };
    
  this.clean = function()
  {
    return this.setState(true);
  };

  this.validate = function()
  {
    this.result = {};
    var ctrls = this.getControls();
    if (ctrls.length == 0) 
    {
      this.setState(true);
      return true;
    }
    var i, flag, ctrl;
    switch (this.getMode())
    {
      default:
      case 'AND':
        flag = true;
        for (i = 0; i < ctrls.length; i++) 
        {
          ctrl = POM.prototype.get(ctrls[i]);
          if (!ctrl) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          if (ctrl.validate(this)) this.result[ctrl.el.attr('id')] = true;
          else this.result[ctrl.el.attr('id')] = flag = false;
        }
        break;
      case 'OR':
        flag = false;
        for (i = 0; i < ctrls.length; i++) 
        {
          ctrl = POM.prototype.get(ctrls[i]);
          if (!ctrl) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          if (ctrl.validate(this)) this.result[ctrl.el.attr('id')] = flag = true;
          else this.result[ctrl.el.attr('id')] = false;
        }
        break;
      case 'XOR':
        var n = 0;
        for (i = 0; i < ctrls.length; i++)
        {
          ctrl = POM.prototype.get(ctrls[i]);
          if (!ctrl) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          if (ctrl.validate(this))
          {
            this.result[ctrl.el.attr('id')] = n < 1;
            n++;
          }
          else this.result[ctrl.el.attr('id')] = false;
        }
        flag = n == 1;
        break;
    }
    this.setState(flag);
    return flag;
  };
  
  this.refresh = function(obj, removedAttributes)
  {
    Validator.superclass.refresh.call(this, obj, removedAttributes);
    this.setState(this.el.attr('data-state') == '1');
  };
};
$a.pom.registerControl('validator', Validator);

// Registering standard validators.
// *************************************************************************************************************

var VRequired = function(el)
{
  VRequired.superclass.constructor.call(this, el);
    
  this.check = function(value)
  {
    if (value === false || value === true) return value;
    if (this.el.attr('data-trim')) return $.trim(value) != '';
    return value != '';
  };
};
$a.pom.registerValidator('vrequired', VRequired);

var VRegExp = function(el)
{
  VRegExp.superclass.constructor.call(this, el);
    
  this.check = function(value)
  {
    var flag, exp = this.el.attr('data-expression');
    if (exp == '' || this.el.attr('data-empty') == '1' && value == '') return true;
    if (exp.charAt(0) == 'i') eval('flag = !' + exp.substr(1) + '.test(value);');
    else eval('flag = ' + exp + '.test(value);');
    return flag;
  };
};
$a.pom.registerValidator('vregexp', VRegExp);

var VEmail = function(el)
{
  VEmail.superclass.constructor.call(this, el);
};
$a.pom.registerValidator('vemail', VRegExp);

var VCompare = function(el)
{
  VCompare.superclass.constructor.call(this, el);
    
  this.validate = function(value)
  {
    var ctrls = this.getControls();
    this.result = {};
    if (ctrls.length == 0) 
    {
      this.setState(true);
      return true;
    }
    var i, j, flag, value1, value2, ctrl1, ctrl2;
    switch (this.getMode())
    {
      default:
      case 'AND':
        flag = true;
        for (i = 0; i < ctrls.length - 1; i++) 
        {
          ctrl1 = POM.prototype.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = POM.prototype.get(ctrls[j]);
            if (!ctrl2) throw new Error('Control with ID = ' + ctrls[j] + ' is not found.');
            value2 = ctrl2.validate(this);
            if (value1 === value2) this.result[ctrls[i]] = this.result[ctrls[j]] = true;
            else this.result[ctrls[i]] = this.result[ctrls[j]] = flag = false;
          }
        }
        break;
      case 'OR':
        flag = false;
        for (i = 0; i < ctrls.length - 1; i++)
        {
          ctrl1 = POM.prototype.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = POM.prototype.get(ctrls[j]);
            if (!ctrl2) throw new Error('Control with ID = ' + ctrls[j] + ' is not found.');
            value2 = ctrl2.validate(this);
            if (value1 === value2) this.result[ctrls[i]] = this.result[ctrls[j]] = flag = true;
            else this.result[ctrls[i]] = this.result[ctrls[j]] = false; 
          }
        }
        break;
      case 'XOR':
        var n = 0;
        for (i = 0; i < ctrls.length - 1; i++)
        {
          ctrl1 = POM.prototype.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = POM.prototype.get(ctrls[j]);
            if (!ctrl2) throw new Error('Control with ID = ' + ctrls[j] + ' is not found.');
            value2 = ctrl2.validate(this);
            if (value1 === value2)
            {
              this.result[ctrls[i]] = this.result[ctrls[j]] = n < 1;
              n++;
            }
            else this.result[ctrls[i]] = this.result[ctrls[j]] = false;
          }
        }
        flag = n == 1;
        break;
    }
    this.setState(flag);
    return flag;
  };
  
  this.check = function(value)
  {
    return this.el.attr('data-caseinsensitive') == '1' ? (value + '').toLowerCase() : value;
  };
};
$a.pom.registerValidator('vcompare', VCompare);  

var VCustom = function(el)
{
  VCustom.superclass.constructor.call(this, el);
    
  this.validate = function()
  {
    var flag, validate = this.el.attr('data-clientfunction');
    if (validate)
    {    
      flag = window[validate](this);
      this.setState(flag);
      return flag;
    }
    var ctrls = this.getControls();
    flag = this.el.attr('data-serverfunction') ? this.el.attr('data-state') == '1' : true;
    for (var i = 0; i < ctrls.length; i++) this.result[ctrls[i]] = flag;
    this.setState(flag);
    return flag;
  };
};
$a.pom.registerValidator('vcustom', VCustom);