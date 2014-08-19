// Base class of controls.
// *************************************************************************************************************

var CONTAINER_PREFIX = 'cont-';

var Control = (function(undefined)
{
  var Control = function(el, pom)
  {
    this.el = el;
    this.pom = pom;
    this.events = {};
  };
  
  var vsAttributes = function(el, res, prefix, ignore)
  {
    var i, attr, attrs = el.get(0).attributes;
    for (i = attrs.length - 1; i >= 0; i--)
    {
      attr = attrs[i].name;
      if (ignore.indexOf(attr) < 0) res[prefix + (attr.substr(0, 5) == 'data-' ? attr.substr(5) : attr)] = el.attr(attr);
    }
  };
  
  Control.prototype.vs = function(ignore)
  {
    ignore = ignore || [];
    ignore.push('id');
    ignore.push('data-ctrl');
    var res = {attrs: {}};
    if (this.el.get(0)['value'] != undefined) res['value'] = this.value();
    vsAttributes(this.el, res['attrs'], '', ignore);
    var el = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
    if (el.length) vsAttributes(el, res['attrs'], CONTAINER_PREFIX, ignore);
    return res;
  };
  
  Control.prototype.container = function()
  {
    var el = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
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
  
  Control.prototype.focus = function()
  {
    this.el.focus();
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
    var container = this.container(), cntlen = CONTAINER_PREFIX.length;
    for (var attr in removedAttributes) 
    {
      attr = removedAttributes[attr];
      if (attr.substr(0, cntlen) != CONTAINER_PREFIX) this.el.removeAttr(attr);
      else container.removeAttr(attr.substr(cntlen));
    }
    var value;
    for (var attr in attributes)
    {
      value = attributes[attr];
      if (attr.substr(0, cntlen) != CONTAINER_PREFIX)
      {
        if (attr == 'checked') this.el.prop(attr, value);
        this.el.attr(attr, value);
      }
      else
      {
        if (attr == 'checked') container.prop(attr.substr(cntlen), value);
        container.attr(attr.substr(cntlen), value);
      }
    }
    return this;
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
    return this;
  };
    
  Control.prototype.bind = function(euid, type, callback, check, toContainer)
  {
    this.unbind(euid);
    this.addEvent(euid, type, callback, check, toContainer);
    if (!check || check()) (toContainer ? $('#' + CONTAINER_PREFIX + this.el.attr('id')) : this.el).on(type, callback);
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
    if (e != undefined) (e.toContainer ? $('#' + CONTAINER_PREFIX + this.el.attr('id')) : this.el).off(e.type, e.callback);
    return this;
  };
    
  Control.prototype.removeEvents = function(completely)
  {
    var container = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
    if (container) container.off();
    this.el.off();
    if (completely) this.events = {};
    return this;
  };
    
  Control.prototype.restoreEvents = function()
  {
    var e, container = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
    for (var euid in this.events)
    {
      e = this.events[euid];
      if (!e.check || e.check()) (e.toContainer ? container : this.el).on(e.type, e.callback);
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
    if (!context && controls[id]) return controls[id];
    var el = $('#' + id, context);
    if (el.length == 0)
    {
      if (!context)
      {
        for (var cid in controls)
        {
          if (controls[cid].el.attr('data-fullid').substr(-id.length) == id) return controls[cid];
        }
      }
      el = $("[data-fullid$='" + id + "'][data-ctrl]", context);
      if (el.length == 0)
      {
        if (!context) delete controls[id];
        return false;
      }
      el = $(el.get(0));
    }
    id = el.attr('id');
    if (id == '') return false;
    if (controls[id]) return controls[id];
    var type = el.attr('data-ctrl').toLowerCase();
    if (tags[type] == undefined) return false;
    return controls[id] = new tags[type](el, this).init();
  };
  
  POM.prototype.setVS = function()
  {
    vs = {}; var bind = this;
    $('[data-ctrl]').each(function()
    {
      var ctrl = bind.get(this.id);
      if (ctrl) vs[this.id] = ctrl.vs();
    });
    return this;
  };
  
  POM.prototype.getVS = function()
  {
    var res = {}; var bind = this;
    $('[data-ctrl]').each(function(index, el)
    {
      var cvs = bind.get(el.id).vs();
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
            res[el.id]['removed'].push(attr);
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
      ctrl = this.get(id);
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
      ctrl = $('#' + CONTAINER_PREFIX + cid).length && $("[id='" + cid + "'][data-ctrl]").length ? '#' + CONTAINER_PREFIX + cid : '#' + cid;
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
      ctrl = $('#' + CONTAINER_PREFIX + id).length && $("[id='" + id + "'][data-ctrl]").length ? '#' + CONTAINER_PREFIX + id : '#' + id;
      $('[data-ctrl]', ctrl).each(function()
      {
        delete controls[this.id];
      });
    }
    
    // Deleting of controls
    params = data.removed;
    for (id in params)
    {
      ctrl = this.get(id);
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
      ctrl = this.get(id);
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
      ctrl = this.get(id);
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
      ctrl = this.get(id);
      if (typeof params[id] == 'object') ctrl.refresh(params[id]['attrs'], params[id]['removed']);
      else ctrl.refresh(params[id]);
    }
    return this;
  };
  
  POM.prototype.getValidators = function(groups)
  {
    var validators = [], bind = this;
    if (groups == '*')
    {
      $('[data-groups][data-controls]').each(function()
      {
        var validator = bind.get($(this).attr('id'));
        if (validator && !validator.isLocked()) validators.push(validator);
      });
    }
    else
    {
      groups = (groups || 'default').split(/\s*,\s*/);
      $('[data-groups][data-controls]').each(function()
      {
        var validator = bind.get($(this).attr('id'));
        if (validator && !validator.isLocked() && validator.hasGroup(groups)) validators.push(validator);
      });
    }
    return validators;
  };
  
  POM.prototype.validate = function(groups, classInvalid, classValid)
  {
    var validators = this.getValidators(groups);
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
      ctrl = $('#' + CONTAINER_PREFIX + cid);
      ctrl = ctrl.length > 0 ? ctrl : $('#' + cid); 
      if (result[cid]) ctrl.removeClass(classInvalid).addClass(classValid);
      else
      {
        if (ctrl.css('display') == 'none') 
        {
          offset = ctrl.show().offset();
          ctrl.hide();
        }
        else
        {
          offset = ctrl.offset();
        }
        ctrl.removeClass(classValid).addClass(classInvalid);
        if (!first || firstOffset.top > offset.top || firstOffset.top == offset.top && firstOffset.left > offset.left) 
        {
          first = ctrl;
          firstOffset = offset;
        }
      }
    }
    if (first) 
    {
      ctrl = this.get(first.attr('id'));
      if (ctrl) ctrl.focus();
      else first.focus();      
    }
    return flag;
  };
  
  POM.prototype.reset = function(groups, classInvalid, classValid)
  {
    var ctrl, validators = this.getValidators(groups);
    $.each(validators, function(index, validator)
    {
      var controls = validator.getControls();
      for (var i = 0; i < controls.length; i++)
      {
        ctrl = $('#' + CONTAINER_PREFIX + controls[i]);
        ctrl = ctrl.length > 0 ? ctrl : $('#' + controls[i]);
        ctrl.removeClass(classInvalid).addClass(classValid);
      }
      validator.setState(true);
    });
    return this;
  };
  
  return POM;
})();

var $pom = new POM();

// Initialization of the controls.
$(function(){$pom.setVS();});

// Class for sending and processing Ajax requests.
// *************************************************************************************************************
 
var Ajax = (function(undefined)
{
  var options, cursor, lock = {};
  
  var showLoader = function()
  {
    cursor = document.body.style.cursor;
    document.body.style.cursor = 'wait';
  };
  
  var hideLoader = function()
  {
    document.body.style.cursor = cursor;
  };

  var Ajax = function(pom)
  {
    this.pom = pom;
    this.setup({
      'global': true,
      'type': 'POST',
      'url': window.location.href,
      'ajaxStart': showLoader,
      'ajaxStop': hideLoader,
      'dataFilter': function(data, type)
      {
        if ($.type(data) == 'object') data = $(data[0].body).text();
        if (data.length == 0)
        {
          pom.setVS();
          return 'null';
        }
        var sep = data.substr(0, 13);
        var script, p = data.lastIndexOf(sep);
        if (p < 1) script = data;
        else script = data.substr(13, p - 13);
        if (script.length) $.globalEval(script);
        pom.setVS();
        return p > 0 ? data.substr(p + 13) : 'null';
      }
    });
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
                'ajax-vs': this.pom.getVS()};
    var settings = {'data': data};
    if (options) 
    {
      settings = options;
      settings.data = data;
      options = null;
    }
    return $.ajax(settings);
  };
  
  Ajax.prototype.submit = function(delegate, groups, classInvalid, classValid)
  {
    if (!lock[delegate] && this.pom.validate(groups, classInvalid, classValid)) 
    {
      lock[delegate] = true;
      return this.doit.apply(this, $.merge([delegate], Array.prototype.slice.call(arguments, 4))).always(function()
      {
        delete lock[delegate];
      });
    }
    return false;
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
      case 'script':
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

var $ajax = new Ajax($pom);

// Registering standard controls.
// *************************************************************************************************************

var Any = function(el, pom)
{
  Any.superclass.constructor.call(this, el, pom);
};
$pom.registerControl('any', Any);

var HyperLink = function(el, pom)
{
  HyperLink.superclass.constructor.call(this, el, pom);
};
$pom.registerControl('hyperlink', HyperLink);

var Image = function(el, pom)
{
  Image.superclass.constructor.call(this, el, pom);
};
$pom.registerControl('image', Image);

var Button = function(el, pom)
{
  Button.superclass.constructor.call(this, el, pom);
};
$pom.registerControl('button', Button);

var Input = function(el, pom)
{
  Input.superclass.constructor.call(this, el, pom);
  
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};
$pom.registerControl('input', Input);

var Hidden = function(el, pom)
{
  Hidden.superclass.constructor.call(this, el, pom);
};
$pom.registerControl('hidden', Hidden);

var TextBox = function(el, pom)
{
  TextBox.superclass.constructor.call(this, el, pom);
 
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
$pom.registerControl('textbox', TextBox);

var DropDownBox = function(el, pom)
{
  DropDownBox.superclass.constructor.call(this, el, pom);
  
  this.validate = function(validator)
  {
    var value = this.value();
    if ($.isArray(value)) value = value.join('');
    return validator.check(value);
  };
};
$pom.registerControl('dropdownbox', DropDownBox);

var CheckBox = function(el, pom)
{
  CheckBox.superclass.constructor.call(this, el, pom);
  
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
$pom.registerControl('checkbox', CheckBox);

var Radio = function(el, pom)
{
  Radio.superclass.constructor.call(this, el, pom);
  
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
$pom.registerControl('radio', Radio);

var Autofill = function(el, pom)
{
  Autofill.superclass.constructor.call(this, el, pom);
   
  var i, t, xhr, id = el.attr('id');
  var hideList = function()
  {
    $('#list-' + id).hide();
    $(document.body).off('click', hideList);
  };

  this.addEvent(id + '_af_e1', 'keyup', function(e)
  {
    if (e.keyCode == 13 || e.keyCode == 9 || e.keyCode >= 37 && e.keyCode <= 40) return false;
    if (t) clearTimeout(t);
    t = setTimeout(function()
    {
      var el = $('#' + id), list = $('#list-' + id), callback = el.attr('data-callback') || '@' + id + '->search';
      list.hide();
      if (xhr) xhr.abort();
      xhr = $ajax.setup({'global': false}, true).doit(el.attr('data-callback'), e.currentTarget.value).done(function(html)
      {
        if (html != '')
        {
          var offset = el.offset(), width = parseInt(list.css('width'));
          list.html(html);
          list.css({'position': 'absolute', 'display': 'block', 'width': width ? width : el.width()});
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
    var el = $('#' + id), list = $('#list-' + id);
    if (e.keyCode == 13) 
    {
      if (list.css('display') != 'none') $('#list-' + id + ' li:eq(' + i + ')').click();
    }
    else
    {
      var css = el.attr('data-activeitemclass'), items = $('#list-' + id + ' li').removeClass(css).length;
      if (items == 0) return;
      if (list.css('display') == 'none') list.show();
      i += e.keyCode - 39;
      if (i < 0) i = items - 1;
      else if (i >= items) i = 0;
      var item = $('#list-' + id + ' li:eq(' + i + ')').addClass(css);
      list.scrollTop(item[0].offsetTop - (list.height() - item.height()) / 2);
    }
  });
  
  this.addEvent(id + '_af_e3', 'blur', function(e)
  {
    if (xhr) xhr.abort();
  });
};
$pom.registerControl('autofill', Autofill, TextBox);

var Paginator = function(el, pom)
{
  Paginator.superclass.constructor.call(this, el, pom);
};
$pom.registerControl('paginator', Paginator);

var CKEditor = function(el, pom)
{
  CKEditor.superclass.constructor.call(this, el, pom);
  
  this.vs = function()
  {
    return CKEditor.superclass.vs.call(this, ['style', 'class']);
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
    return this;
  };
  
  this.focus = function()
  {
    this.editor.focus();
    return this;
  };
  
  this.value = function()
  {
    if (this.editor) return this.editor.getData();
    return CKEditor.superclass.value.call(this);
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
$pom.registerControl('ckeditor', CKEditor);

var DateTimePicker = function(el, pom)
{
  DateTimePicker.superclass.constructor.call(this, el, pom);
  
  this.init = function()
  {
    DateTimePicker.superclass.init.call(this);
    this.el.datetimepicker('destroy').datetimepicker(eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  this.remove = function()
  {
    this.el.datetimepicker('destroy');
    DateTimePicker.superclass.remove.call(this);
    return this;
  };
 
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};
$pom.registerControl('datetimepicker', DateTimePicker);

var ColorPicker = function(el, pom)
{
  ColorPicker.superclass.constructor.call(this, el, pom);
  
  this.init = function()
  {
    ColorPicker.superclass.init.call(this);
    this.el.spectrum(eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  this.refresh = function(obj, removedAttributes)
  {
    this.el.spectrum('destroy');
    ColorPicker.superclass.refresh.call(this, obj, removedAttributes);
    return this;
  }
  
  this.remove = function()
  {
    this.el.spectrum('destroy');
    ColorPicker.superclass.remove.call(this);
    return this;
  };
 
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};
$pom.registerControl('colorpicker', ColorPicker);

var Slider = function(el, pom)
{
  Slider.superclass.constructor.call(this, el, pom);
  
  this.init = function()
  {
    Slider.superclass.init.call(this);
    this.el.noUiSlider(eval('(' + (this.el.attr('data-settings') || '{}') + ')'), this.el.html() ? true : false);
    return this;
  };
};
$pom.registerControl('slider', Slider);

var Upload = function(el, pom)
{
  Upload.superclass.constructor.call(this, el, pom);
  
  this.init = function()
  {
    var bind = this, id = this.el.attr('id'), callback = this.el.attr('data-callback');
    var submit = function(e, data) 
    {
      data.formData = {'ajax-method': callback,
                       'ajax-args[]': id,
                       'ajax-key': $(document.body).attr('id'), 
                       'ajax-vs': JSON.stringify(bind.pom.getVS())};
    };
    var always = function(e, data) 
    {
      bind.el = $('#' + id);
    };
    Upload.superclass.init.call(this);
    var ops = eval('(' + (this.el.attr('data-settings') || '{}') + ')');
    ops.dataType = 'json';
    ops.paramName = id + (this.el.attr('multiple') == 'multiple' ? '[]' : '');
    ops.multipart = true;
    this.el.fileupload(ops)
           .off('fileuploadsubmit', submit)
           .off('fileuploadalways', always)
           .on('fileuploadalways', always);
    if (callback) this.el.on('fileuploadsubmit', submit);
    return this;
  };
  
  this.remove = function()
  {
    this.el.fileupload('destroy');
    Upload.superclass.remove.call(this);
    return this;
  };
  
  this.value = function()
  {
    return '';
  }
};
$pom.registerControl('upload', Upload);

var Panel = function(el, pom)
{
  Panel.superclass.constructor.call(this, el, pom);

  this.get = function(id)
  {
    return this.pom.get(id, this.el);
  };
  
  this.has = function(id)
  {
    if (id instanceof Control) id = id.el.attr('id');
    var ctrl = this.get(id);
    return ctrl !== false && ctrl !== this; 
  };
  
  this.getControls = function(isRecursion)
  {
    var ctrl, ctrls = [], bind = this;
    if (isRecursion)
    {
      $('[data-ctrl]', this.el).each(function()
      {
        ctrl = bind.pom.get(this.id);
        if (ctrl) ctrls.push(ctrl);
      });
    }
    else
    {
      var flag, panels = [];
      $('[data-ctrl]', this.el).each(function()
      {
        ctrl = bind.pom.get(this.id);
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
$pom.registerControl('panel', Panel);

var visiblePopupIdentifiers = [];
var Popup = function(el, pom)
{
  Popup.superclass.constructor.call(this, el, pom);
  
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
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
    var parentID = visiblePopupIdentifiers.pop();
    if (parentID) 
    {
      this.el.css('z-index', $('#' + parentID).css('z-index') + 1000);
      visiblePopupIdentifiers.push(parentID);
    }
    visiblePopupIdentifiers.push(id);
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
      $(this.el).off('click', stop).click(stop);
      $(document.body).off('click', hide).click(hide);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).off('keyup', escape).keyup(escape);
    }
    var btnClose = this.el.attr('data-closebuttons');
    if (btnClose) $(btnClose).off('click', hide).click(hide);
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
    return this;
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
      $(document.body).off('click', hide);
      $(this.el).off('click', stop);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).off('keyup', escape);
    }
    if (this.el.attr('data-closebuttons')) $(this.el.attr('data-closebuttons')).off('click', hide);
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
    return this;
  };
  
  this.remove = function()
  {
    this.hide();
    Popup.superclass.remove.call(this);
    return this;
  };
};
$pom.registerControl('popup', Popup, Panel);

var Grid = function(el, pom)
{
  Grid.superclass.constructor.call(this, el, pom);
};
$pom.registerControl('grid', Grid, Panel);

var ImgEditor = function(el, pom)
{
  ImgEditor.superclass.constructor.call(this, el, pom);
  
  var id = el.attr('id'), ops, paper, image;
  var defaults = {scale: 100,
                  angle: 0,
                  cropEnabled: true,
                  cropResizable: false,
                  cropWidth: 50,
                  cropHeight: 50,
                  cropMinWidth: 10,
                  cropMinHeight: 10,
                  cropMaxWidth: 0,
                  cropMaxHeight: 0};
  
  paper = new Raphael('canvas_' + id);
  
  var resize = function()
  {
    if (!ops) return;
    var canvas = $('#canvas_' + id), crop = $('#crop_' + id);
    var canvasWidth = canvas.width(), canvasHeight = canvas.height();
    if (crop.width() > canvasWidth)
    {
      ops.cropWidth = canvasWidth;
      crop.width(canvasWidth);
    }
    if (crop.height() > canvasHeight)
    {
      ops.cropHeight = canvasHeight;
      crop.height(canvasHeight);
    }
    var offset = crop.position(), right = offset.left + ops.cropWidth, bottom = offset.top + ops.cropHeight;
    if (right > canvasWidth) offset.left = canvasWidth - ops.cropWidth;
    if (bottom > canvasHeight) offset.top = canvasHeight - ops.cropHeight;
    draw(offset.left, offset.top);
  };
  
  var changeAngle = function()
  {
    transform(image.attrs.x, image.attrs.y, $(this).val(), ops.scale);
  };
  
  var changeScale = function()
  {
    transform(image.attrs.x, image.attrs.y, ops.angle, $(this).val());
  };
  
  var transform = function(x, y, angle, scale)
  {
    image.transform('');
    image.attr({'x': x, 'y': y});
    ops.angle = angle;
    image.rotate(angle);
    ops.scale = scale;
    scale /= 100;
    image.scale(scale, scale);
  };
  
  var draw = function(cropLeft, cropTop)
  {
    $('#crop_' + id).css({left: cropLeft, top: cropTop, width: ops.cropWidth, height: ops.cropHeight});
    $('#cropLineLeft_' + id).css({left: cropLeft, top: cropTop, width: 1, height: ops.cropHeight});
    $('#cropLineRight_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop, width: 1, height: ops.cropHeight});
    $('#cropLineTop_' + id).css({left: cropLeft, top: cropTop, width: ops.cropWidth, height: 1});
    $('#cropLineBottom_' + id).css({left: cropLeft, top: cropTop + ops.cropHeight, width: ops.cropWidth, height: 1});
    $('#shadowTop_' + id).css({left: 0, top: 0, right: 0, height: cropTop});
    $('#shadowBottom_' + id).css({left: 0, top: cropTop + ops.cropHeight, right: 0, bottom: 0});
    $('#shadowLeft_' + id).css({left: 0, top: cropTop, width: cropLeft, height: ops.cropHeight});
    $('#shadowRight_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop, right: 0, height: ops.cropHeight});
    if (ops.cropResizable)
    {
      $('#cropSnapNW_' + id).css({left: cropLeft, top: cropTop});
      $('#cropSnapN_' + id).css({left: cropLeft + ops.cropWidth / 2, top: cropTop});
      $('#cropSnapNE_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop});
      $('#cropSnapW_' + id).css({left: cropLeft, top: cropTop + ops.cropHeight / 2});
      $('#cropSnapE_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop + ops.cropHeight / 2});
      $('#cropSnapSW_' + id).css({left: cropLeft, top: cropTop + ops.cropHeight});
      $('#cropSnapS_' + id).css({left: cropLeft + ops.cropWidth / 2, top: cropTop + ops.cropHeight});
      $('#cropSnapSE_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop + ops.cropHeight});
    }
    $('#width_' + id).text(ops.cropWidth);
    $('#height_' + id).text(ops.cropHeight);
  };
  
  var crop = function()
  {
    var crop = $('#crop_' + id), canvas = $('#canvas_' + id);
    if (ops.cropEnabled)
    {
      crop.off().on('mousedown', function(e)
      {
        var canvasWidth = canvas.width(), canvasHeight = canvas.height();
        var offset = $(this).position(), cropmove = {left: e.pageX - offset.left, top: e.pageY - offset.top};
        $(document).on('mousemove', function(e)
        {
          var left = e.pageX - cropmove.left, top = e.pageY - cropmove.top;
          if (left < 0) left = 0;
          if (top < 0) top = 0;
          if (left > canvasWidth - ops.cropWidth - 1) left = canvasWidth - ops.cropWidth - 1;
          if (top > canvasHeight - ops.cropHeight - 1) top = canvasHeight - ops.cropHeight - 1;
          draw(left, top);
          e.preventDefault();
        }).on('mouseup', function(e)
        {
          $(this).off('mousemove');
        });
        e.preventDefault();
        e.stopPropagation();
      });
      if (ops.cropResizable)
      {
        $('.crop-snap', '#' + id).off().show().on('mousedown', function(e)
        {
          var canvasWidth = canvas.width(), canvasHeight = canvas.height();
          var snap = $(this), offset = snap.position(), snapmove = {left: e.pageX - offset.left, top: e.pageY - offset.top};
          var sid = snap.attr('id').substr(8, snap.attr('id').length - id.length - 9);
          var cropMinWidth = ops.cropMinWidth, cropMinHeight = ops.cropMinHeight;
          var cropMaxWidth = (ops.cropMaxWidth < 1 || ops.cropMaxWidth > canvasWidth) ? canvasWidth - 1: ops.cropMaxWidth;
          var cropMaxHeight = (ops.cropMaxHeight < 1 || ops.cropMaxHeight > canvasHeight) ? canvasHeight - 1: ops.cropMaxHeight;
          $(document).on('mousemove', function(e)
          {
            var x = e.pageX - snapmove.left, y = e.pageY - snapmove.top;
            var offset = crop.position(), crp = {left: offset.left, top: offset.top, width: crop.width(), height: crop.height()};
            crp.right = crp.left + crp.width;
            crp.bottom = crp.top + crp.height;
            switch (sid)
            {
              case 'N':
                if (crp.bottom - y > cropMaxHeight) y = crp.bottom - cropMaxHeight;
                if (crp.bottom - y < cropMinHeight) y = crp.bottom - cropMinHeight;
                ops.cropHeight = crp.bottom - y;
                crp.top = y;
                break;
              case 'S':
                if (y - crp.top > cropMaxHeight) y = crp.top + cropMaxHeight;
                if (y - crp.top < cropMinHeight) y = crp.top + cropMinHeight;
                ops.cropHeight = y - crp.top;
                break;
              case 'W':
                if (crp.right - x > cropMaxWidth) x = crp.right - cropMaxWidth;
                if (crp.right - x < cropMinWidth) x = crp.right - cropMinWidth;
                ops.cropWidth = crp.right - x;
                crp.left = x;
                break;
              case 'E':
                if (x - crp.left > cropMaxWidth) x = crp.left + cropMaxWidth;
                if (x - crp.left < cropMinWidth) x = crp.left + cropMinWidth;
                ops.cropWidth = x - crp.left;
                break;
              case 'NW':
                if (crp.bottom - y > cropMaxHeight) y = crp.bottom - cropMaxHeight;
                if (crp.bottom - y < cropMinHeight) y = crp.bottom - cropMinHeight;
                if (crp.right - x > cropMaxWidth) x = crp.right - cropMaxWidth;
                if (crp.right - x < cropMinWidth) x = crp.right - cropMinWidth;
                ops.cropHeight = crp.bottom - y;
                ops.cropWidth = crp.right - x;
                crp.top = y;
                crp.left = x;
                break;
              case 'NE':
                if (crp.bottom - y > cropMaxHeight) y = crp.bottom - cropMaxHeight;
                if (crp.bottom - y < cropMinHeight) y = crp.bottom - cropMinHeight;
                if (x - crp.left > cropMaxWidth) x = crp.left + cropMaxWidth;
                if (x - crp.left < cropMinWidth) x = crp.left + cropMinWidth;
                ops.cropHeight = crp.bottom - y;
                ops.cropWidth = x - crp.left;
                crp.top = y;
                break;
              case 'SW':
                if (y - crp.top > cropMaxHeight) y = crp.top + cropMaxHeight;
                if (y - crp.top < cropMinHeight) y = crp.top + cropMinHeight;
                if (crp.right - x > cropMaxWidth) x = crp.right - cropMaxWidth;
                if (crp.right - x < cropMinWidth) x = crp.right - cropMinWidth;
                ops.cropHeight = y - crp.top;
                ops.cropWidth = crp.right - x;
                crp.left = x;
                break;
              case 'SE':
                if (y - crp.top > cropMaxHeight) y = crp.top + cropMaxHeight;
                if (y - crp.top < cropMinHeight) y = crp.top + cropMinHeight;
                if (x - crp.left > cropMaxWidth) x = crp.left + cropMaxWidth;
                if (x - crp.left < cropMinWidth) x = crp.left + cropMinWidth;
                ops.cropHeight = y - crp.top;
                ops.cropWidth = x - crp.left;
                break;
            }
            if (crp.left < 0) crp.left = 0;
            if (crp.top < 0) crp.top = 0;
            if (crp.left > canvasWidth - ops.cropWidth - 1) crp.left = canvasWidth - ops.cropWidth - 1;
            if (crp.top > canvasHeight - ops.cropHeight - 1) crp.top = canvasHeight - ops.cropHeight - 1;
            draw(crp.left, crp.top);
            e.preventDefault();
          }).on('mouseup', function(e)
          {
            $(this).off('mousemove');
          });
          e.preventDefault();
          e.stopPropagation();
        });
      }
      crop.show();
      $('.shadow, .crop-line', '#' + id).show();
      draw((canvas.width() - ops.cropWidth) / 2, (canvas.height() - ops.cropHeight) / 2);
    }
    else
    {
      crop.off().hide();
      $('.shadow, .crop-line, .crop-snap', '#' + id).off().hide();
    }
    canvas.off().on('mousedown', function(e)
    {
      var cursor = $(document.body).css('cursor');
      $(document.body).css('cursor', 'move');
      var left = e.pageX - image.attrs.x, top = e.pageY - image.attrs.y;
      $(document).on('mousemove', function(e)
      {
        transform(e.pageX - left, e.pageY - top, ops.angle, ops.scale);
        e.preventDefault();
      }).on('mouseup', function()
      {
        $(this).off('mousemove');
        $(document.body).css('cursor', cursor);
      });
      e.preventDefault();
      e.stopPropagation();
    });
  };
  
  $(window).resize(resize);
  
  this.load = function(url, width, height, options)
  {
    ops = $.extend({}, defaults, options);
    paper.clear();
    this.show(true);
    paper.setSize('100%', '100%');
    image = paper.image(url, 0, 0, width, height);
    var canvas = $('#canvas_' + id), canvasWidth = canvas.width(), canvasHeight = canvas.height();
    if (ops.cropEnabled)
    {
      if (ops.cropMinWidth < 1) ops.cropMinWidth = 1;
      if (ops.cropMinHeight < 1) ops.cropMinHeight = 1;
      if (ops.cropWidth < 1) ops.cropWidth = 1;
      if (ops.cropHeight < 1) ops.cropHeight = 1;
      if (ops.cropWidth > canvasWidth) ops.cropWidth = canvasWidth;
      if (ops.cropHeight > canvasHeight) ops.cropHeight = canvasHeight;
    }
    else
    {
      $('#width_' + id).text(width);
      $('#height_' + id).text(height);
    }
    transform((canvasWidth - image.attrs.width) / 2, (canvasHeight - image.attrs.height) / 2, ops.angle, ops.scale);
    this.get('rotate').el.off().on('slide', changeAngle).on('set', changeAngle).val(ops.angle);
    this.get('zoom').el.off().on('slide', changeScale).on('set', changeScale).val(ops.scale);
    crop();
    return this;
  };
  
  this.getTransformData = function()
  {
    if (!image) return [];
    var data = {};
    if (ops.cropEnabled)
    {
      var box = image.getBBox(), crop = $('#crop_' + id), offset = crop.position();
      data.cropLeft = offset.left - box.x;
      data.cropTop = offset.top - box.y;
      data.cropWidth = crop.width();
      data.cropHeight = crop.height();
    }
    data.angle = -ops.angle;
    data.scale = ops.scale / 100;
    data.bgcolor = this.get('bgcolor').value();
    data.isSmartCrop = this.get('smartCrop').el.prop('checked') ? 1 : 0;
    return data;
  };
  
  this.remove = function()
  {
    $(window).off('resize', resize);
    this.hide();
    Popup.superclass.remove.call(this);
    return this;
  };
};
$pom.registerControl('imgeditor', ImgEditor, Popup);

// Base class of validators.
// *************************************************************************************************************

var Validator = function(el, pom)
{
  Validator.superclass.constructor.call(this, el, pom);
     
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
  
  this.lock = function(flag)
  {
    if (flag) this.el.attr('data-locked', 1);
    else this.el.removeAttr('data-locked');
    return this;
  };
  
  this.isLocked = function()
  {
    return this.el.attr('data-locked');
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
          ctrl = this.pom.get(ctrls[i]);
          if (!ctrl) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          if (ctrl.validate(this)) this.result[ctrl.el.attr('id')] = true;
          else this.result[ctrl.el.attr('id')] = flag = false;
        }
        break;
      case 'OR':
        flag = false;
        for (i = 0; i < ctrls.length; i++) 
        {
          ctrl = this.pom.get(ctrls[i]);
          if (!ctrl) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          if (ctrl.validate(this)) this.result[ctrl.el.attr('id')] = flag = true;
          else this.result[ctrl.el.attr('id')] = false;
        }
        break;
      case 'XOR':
        var n = 0;
        for (i = 0; i < ctrls.length; i++)
        {
          ctrl = this.pom.get(ctrls[i]);
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
    return this.setState(this.el.attr('data-state') == '1');
  };
};
$pom.registerControl('validator', Validator);

// Registering standard validators.
// *************************************************************************************************************

var VRequired = function(el, pom)
{
  VRequired.superclass.constructor.call(this, el, pom);
    
  this.check = function(value)
  {
    if (value === false || value === true) return value;
    if (this.el.attr('data-trim')) return $.trim(value) != '';
    return value != '';
  };
};
$pom.registerValidator('vrequired', VRequired);

var VRegExp = function(el, pom)
{
  VRegExp.superclass.constructor.call(this, el, pom);
    
  this.check = function(value)
  {
    var flag, exp = this.el.attr('data-expression');
    if (exp == '' || this.el.attr('data-empty') == '1' && value == '') return true;
    if (exp.charAt(0) == 'i') eval('flag = !' + exp.substr(1) + '.test(value);');
    else eval('flag = ' + exp + '.test(value);');
    return flag;
  };
};
$pom.registerValidator('vregexp', VRegExp);

var VEmail = function(el, pom)
{
  VEmail.superclass.constructor.call(this, el, pom);
};
$pom.registerValidator('vemail', VRegExp);

var VCompare = function(el, pom)
{
  VCompare.superclass.constructor.call(this, el, pom);
    
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
          ctrl1 = this.pom.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = this.pom.get(ctrls[j]);
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
          ctrl1 = this.pom.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = this.pom.get(ctrls[j]);
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
          ctrl1 = this.pom.get(ctrls[i]);
          if (!ctrl1) throw new Error('Control with ID = ' + ctrls[i] + ' is not found.');
          value1 = ctrl1.validate(this);
          for (j = i + 1; j < ctrls.length; j++)
          {
            ctrl2 = this.pom.get(ctrls[j]);
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
$pom.registerValidator('vcompare', VCompare);

var VCustom = function(el, pom)
{
  VCustom.superclass.constructor.call(this, el, pom);
    
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
    return true;
  };
};
$pom.registerValidator('vcustom', VCustom);