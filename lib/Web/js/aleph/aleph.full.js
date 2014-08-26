/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

/**
 * Prefix of the container element's ID attribute.
 */
var CONTAINER_PREFIX = 'cont-';

/**
 * The base class of all controls.
 */
var Control = (function(undefined)
{
  /**
   * Initializes the basic properties of the control.
   *
   * @constructor
   * @this {Control}
   * @param {jQuery} el - jQuery instance of the main element of the control.
   * @param {POM} pom - the instance of the POM object.
   */
  var Control = function(el, pom)
  {
    this.el = el;
    this.pom = pom;
    this.events = {};
  };
  
  /**
   * Collects values of all control attributes.
   *
   * @this {Window}
   * @param {jQuery} el - jQuery instance of the main element of the control.
   * @param {object} res - the collection of the control attributes and their values.
   * @param {string} prefix - the prefix of the attribute names in res object.
   * @param {array} ignore - the array of the ignored attributes.
   * @private
   */
  var vsAttributes = function(el, res, prefix, ignore)
  {
    var i, attr, attrs = el.get(0).attributes;
    for (i = attrs.length - 1; i >= 0; i--)
    {
      attr = attrs[i].name;
      if (ignore.indexOf(attr) < 0) res[prefix + (attr.substr(0, 5) == 'data-' ? attr.substr(5) : attr)] = el.attr(attr);
    }
  };
  
  /**
   * Returns the view state (collection of the control attributes and their values) of the control.
   *
   * @this {Control}
   * @param {array} ignore - the array of the attributes that are not to be included in the control view state.
   * @return {object}
   */
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
  
  /**
   * Returns jQuery instance of the container element of the control.
   *
   * @this {Control}
   * @return {jQuery}   
   */
  Control.prototype.container = function()
  {
    var el = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
    return el.length ? el : this.el;
  };

  /**
   * Returns the control type.
   *
   * @this {Control}
   * @return {string}
   */
  Control.prototype.type = function()
  {
    return this.el.attr('data-ctrl');
  };

  /**
   * Returns the control value (if it exists).
   *
   * @this {Control}
   * @return {mixed}
   */
  Control.prototype.value = function()
  {
    return this.el.val();
  };

  /**
   * Removes the control value.
   *
   * @this {Control}
   * @return {self}
   */
  Control.prototype.clean = function()
  {
    this.el.val('');
    return this;
  };
  
  /**
   * Sets focus to the control.
   *
   * @this {Control}
   * @return {self}
   */
  Control.prototype.focus = function()
  {
    this.el.focus();
    return this;
  };
  
  /**
   * Validates the control value.
   * By default this method always returns TRUE. But you can override it in child classes.
   *
   * @this {Control}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  Control.prototype.validate = function(validator)
  {
    return true;
  };
  
  /**
   * Initializes the control.
   *
   * @this {Control}
   * @return {self}
   */
  Control.prototype.init = function()
  {
    this.el = $('#' + this.el.attr('id'));
    return this.refreshEvents();
  };
  
  /**
   * Updates values of the control attributes.
   *
   * @this {Control}
   * @param {object} attributes - the collection of attributes that should be updated.
   * @param {object} removedAttributes - the collection of attributes that should be removed.
   * @return {self}
   */
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
  
  /**
   * Redraws the control.
   *
   * @this {Control}
   * @param {object|string} obj - determines HTML of the control or its attributes which need to be updated.
   * @param {object} removedAttributes - determines the control attributes which need to be removed.
   * @return {self}
   */
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
  
  /**
   * Removes the control.
   *
   * @this {Control}
   * @return {self}
   */
  Control.prototype.remove = function()
  {
    this.removeEvents(true);
    this.container().remove();
    return this;
  };
    
  /**
   * Attaches an event handler function for event to the main element or container element of the control.
   *
   * @this {Control}
   * @param {string} euid - the unique identifier of the event handler.
   * @param {string} type - one or more space-separated event types and optional namespaces, such as "click" or "keydown.myPlugin".
   * @param {function} callback - a function to execute when the event is triggered. The value FALSE is also allowed as a shorthand for a function that simply does return FALSE.
   * @param {function} check - a function, the execution result of which determines whether (it returns TRUE) or not (it returns FALSE) the event handler will be attached to the control element.
   * @param {boolean} toContainer - determines whether the event handler will be attached to the container element of the control.  
   * @param {mixed} data - data to be passed to the handler in event.data when an event is triggered.
   * @return {self}
   */
  Control.prototype.bind = function(euid, type, callback, check, toContainer, data)
  {
    this.unbind(euid);
    this.addEvent(euid, type, callback, check, toContainer, data);
    if (!check || check()) 
    {
      var el = toContainer ? $('#' + CONTAINER_PREFIX + this.el.attr('id')) : this.el;
      if (data != undefined) el.on(type, data, callback);
      else el.on(type, callback);
    }
    return this;
  };
    
  /**
   * Removes an event handler that were attached with Control.bind() and unregisters the event.
   *
   * @this {Control}
   * @param {string} euid - the unique identifier of the event handler.
   * @return {self}
   */
  Control.prototype.unbind = function(euid)
  {
    this.removeEvent(euid);
    delete this.events[euid];
    return this;
  };
    
  /**
   * Registers an event handler function for event that will be attached to the main element or container element of the control.
   *
   * @this {Control}
   * @param {string} euid - the unique identifier of the event handler.
   * @param {string} type - one or more space-separated event types and optional namespaces, such as "click" or "keydown.myPlugin".
   * @param {function} callback - a function to execute when the event is triggered. The value FALSE is also allowed as a shorthand for a function that simply does return FALSE.
   * @param {function} check - a function, the execution result of which determines whether (it returns TRUE) or not (it returns FALSE) the event handler will be attached to the control element.
   * @param {boolean} toContainer - determines whether the event handler will be attached to the container element of the control.  
   * @param {mixed} data - data to be passed to the handler in event.data when an event is triggered.
   * @return {self}
   */
  Control.prototype.addEvent = function(euid, type, callback, check, toContainer, data)
  {
    this.events[euid] = {'type': type, 'callback': callback, 'check': check, 'toContainer': toContainer, 'data': data};
    return this;
  };
    
  /**
   * Removes an event handler, but keeps the event as registered.
   *
   * @this {Control}
   * @param {string} euid - the unique identifier of the event handler.
   * @return {self}
   */
  Control.prototype.removeEvent = function(euid)
  {
    var e = this.events[euid];
    if (e != undefined) (e.toContainer ? $('#' + CONTAINER_PREFIX + this.el.attr('id')) : this.el).off(e.type, e.callback);
    return this;
  };
    
  /**
   * Removes all events of the control.
   *
   * @this {Control}
   * @param {boolean} completely - determines whether the control events will be unregistered.
   * @return {self}
   */
  Control.prototype.removeEvents = function(completely)
  {
    this.el.off();
    $('#' + CONTAINER_PREFIX + this.el.attr('id')).off();
    if (completely) this.events = {};
    return this;
  };
    
  /**
   * Attaches the previously registered events to the control element.
   *
   * @this {Control}
   * @return {self}
   */
  Control.prototype.restoreEvents = function()
  {
    var e, el, container = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
    for (var euid in this.events)
    {
      e = this.events[euid];
      if (!e.check || e.check()) 
      {
        el = e.toContainer ? container : this.el;
        if (e.data != undefined) el.on(e.type, e.data, e.callback);
        else el.on(e.type, e.callback);
      }
    }
    return this;
  };
   
  /**
   * Updates the registered events.
   *
   * @this {Control}
   * @return {self}
   */
  Control.prototype.refreshEvents = function()
  {
    return this.removeEvents().restoreEvents();
  };
  
  return Control;
})();

/**
 * Container for genera functions that facilitate interaction with Page Object Model (POM).
 * Also this class is responsible for synchronization between controls of the server and client sides.
 */
var POM = (function(undefined)
{
  var vs = {}, tags = {}, controls = {};
 
  /**
   * Inherits one class (object) from another.
   *
   * @this {Window}
   * @param {object} subClass - the child class.
   * @param {object} parentClass - the parent class. 
   * @return {object}
   */
  var inherit = function(subClass, parentClass)
  {
    var F = function(){}
    F.prototype = parentClass.prototype;
    subClass.prototype = new F();
    subClass.prototype.constructor = subClass;
    subClass.superclass = parentClass.prototype;
    return subClass;
  };

  /**
   * @constructor
   * @this {POM}
   */
  var POM = function(){};
  
  /**
   * Registers the control class.
   *
   * @this {POM}
   * @param {string} tag - the control type.
   * @param {object} newControl - the control class.
   * @param {object} parentControl - the parent control.
   * @return {Control}
   */
  POM.prototype.registerControl = function(tag, newControl, parentControl)
  {
    return tags[tag] = inherit(newControl, parentControl || Control);
  };
  
  /**
   * Registers the validator class.
   *
   * @this {POM}
   * @param {string} tag - the validator type.
   * @param {object} newValidator - the validator class.
   * @param {object} parentValidator - the parent validator.
   * @return {Validator}
   */
  POM.prototype.registerValidator = function(tag, newValidator, parentValidator)
  {
    return tags[tag] = inherit(newValidator, parentValidator || Validator);
  };

  /**
   * Returns the control object.
   * If the control is not found, it returns FALSE.
   *
   * @this {POM}
   * @param {string} id - the unique or logic identifier of the control.
   * @param {jQuery} context - the search context.
   * @return {boolean|Control}
   */
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
  
  /**
   * Gathers the control view states.
   *
   * @this {POM}
   * @return {self}
   */
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
  
  /**
   * Returns changes of the controls' view states.
   *
   * @this {POM}
   * @return {object}
   */
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
  
  /**
   * Updates or deletes controls or their attributes.
   *
   * @this {POM}
   * @param {object} data - information about control changes.
   * @return {self}
   * @protected
   */
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
  
  /**
   * Returns validators that associated with one or more groups.
   *
   * @this {POM}
   * @param {string} groups - the validation group(s).
   * @return {array}
   */
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
  
  /**
   * Validates controls.
   *
   * @this {POM}
   * @param {groups} - the validation group(s).
   * @param {string} classInvalid - the CSS class for highlighting of invalid controls.
   * @param {string} classValid - the CSS class for highlighting of valid controls.
   * @return {boolean}
   */
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
  
  /**
   * Resets highlighting of the validated controls and validators.
   *
   * @this {POM}
   * @param {groups} - the validation group(s).
   * @param {string} classInvalid - the CSS class for highlighting of invalid controls.
   * @param {string} classValid - the CSS class for highlighting of valid controls.
   * @return {self}
   */
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

/**
 * Class for performing Ajax requests using jQuery Ajax API.
 */
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

  /**
   * Initializes the Ajax instance.
   *
   * @constructor
   * @this {Ajax}
   * @param {POM} pom - the instance of the POM object.
   */
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
  
  /**
   * Sets the Ajax settings.
   *
   * @this {Ajax}
   * @param {object} settings - the Ajax settings. See more info here http://api.jquery.com/jQuery.ajax/
   * @param {boolean} isOneOff - determines whether the given settings will be used only one time.
   * @return {self}
   */
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
  
  /**
   * Performs an Ajax request.
   *
   * @this {Ajax}
   * @param {string} delegate - the PHP delegate to call via Ajax request.
   * @param {mixed} param1, ..., paramN - parameters of the delegate.
   * @return {jqXHR}
   */
  Ajax.prototype.doit = function(delegate/*, param1, param2, ..., paramN */)
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
  
  /**
   * Performs an Ajax request with the preliminary validation of controls.
   * It returns FALSE if the request has been already sent and jqXHR otherwise.
   *
   * @this {Ajax}
   * @param {string} delegate - the PHP delegate to call via Ajax request.
   * @param {string} groups - the validation group(s).
   * @param {string} classInvalid - the CSS class for invalid controls.
   * @param {string} classValid - the CSS class for valid controls.
   * @param {mixed} param1, ..., paramN - parameters of the delegate.
   * @return {boolean|jqXHR}
   */
  Ajax.prototype.submit = function(delegate, groups, classInvalid, classValid/*, param1, param2, ..., paramN */)
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
  
  /**
   * Performs some action.
   *
   * @this {Ajax}
   * @param {mixed} param1, ..., paramN - parameters of the action.
   */
  Ajax.prototype.action = function(act/*, param1, param2, ..., paramN */)
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

/**
 * Represents any HTML element.
 *
 * @constructor
 * @this {Any}
 * @param {jQuery} el - jQuery instance of the HTML element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Any = function(el, pom)
{
  Any.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('any', Any);

/**
 * Represents the <input> HTML element.
 *
 * @constructor
 * @this {Input}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Input = function(el, pom)
{
  Input.superclass.constructor.call(this, el, pom);
  
  /**
   * Validates the control value.
   *
   * @this {Input}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$pom.registerControl('input', Input);

/**
 * Represents the <input type="hidden"> HTML element.
 *
 * @constructor
 * @this {Hidden}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Hidden = function(el, pom)
{
  Hidden.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('hidden', Input);

/**
 * Represents the <input type="text"> HTML element.
 *
 * @constructor
 * @this {TextBox}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var TextBox = function(el, pom)
{
  TextBox.superclass.constructor.call(this, el, pom);
 
  var id = el.attr('id');
  
  /**
   * Inserts default value in the textbox when it gets focus.
   *
   * @this {Element}
   * @private
   */
  var focus = function()
  {
    var el = $(this);
    if (el.val() == el.attr('data-default')) el.val('');
  };
  
  /**
   * Removes default value from the textbox when it loses focus.
   *
   * @this {Element}
   * @private
   */
  var blur = function()
  {
    var el = $(this);
    if (!el.val()) el.val(el.attr('data-default'));
  };
  
  /**
   * Returns TRUE if the textbox has default value and FALSE otherwise.
   *
   * @this {Element}
   * @return {boolean}
   * @private
   */
  var check = function()
  {
    return !!$('#' + id).attr('data-default');
  };
    
  this.addEvent(id + '_txt_e1', 'focus', focus, check);
  this.addEvent(id + '_txt_e2', 'blur', blur, check);
    
  /**
   * Initializes the control.
   *
   * @this {TextBox}
   * @return {self}
   */
  this.init = function()
  {
    TextBox.superclass.init.call(this);
    var dv = this.el.attr('data-default');
    if (dv) if (!this.el.val()) this.el.val(dv);
    return this;
  };
  
  /**
   * Redraws the control.
   *
   * @this {TextBox}
   * @param {object|string} obj - determines HTML of the control or its attributes which need to be updated.
   * @param {object} removedAttributes - determines the control attributes which need to be removed.
   * @return {self}
   */
  this.refresh = function(obj, removedAttributes)
  {
    focus.call(this.el);
    return TextBox.superclass.refresh.call(this, obj, removedAttributes);
  };
    
  /**
   * Validates the control.
   * 
   * @this {TextBox}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
    
  /**
   * Cleans the control value.
   *
   * @this {TextBox}
   * @return {self}
   */
  this.clean = function()
  {
    this.el.val(this.el.attr('data-default') || '');
    return this;
  };
};

$pom.registerControl('textbox', TextBox);

/**
 * The wrapper around the jQuery Upload plugin.
 *
 * @constructor
 * @this {Upload}
 * @param {jQuery} el - jQuery instance of the <input type="file"> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Upload = function(el, pom)
{
  Upload.superclass.constructor.call(this, el, pom);
  
  /**
   * Initializes the control.
   *
   * @this {Upload}
   * @return {self}
   */
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
  
  /**
   * Removes the control.
   *
   * @this {Upload}
   * @return {self}
   */
  this.remove = function()
  {
    this.el.fileupload('destroy');
    Upload.superclass.remove.call(this);
    return this;
  };
  
  /**
   * Returns the control value.
   * For the Upload control this method always returns empty string.
   *
   * @this {Upload}
   * @return {string}
   */
  this.value = function()
  {
    return '';
  }
};

$pom.registerControl('upload', Upload);

/**
 * Represents the <input type="checkbox"> HTML element.
 *
 * @constructor
 * @this {CheckBox}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var CheckBox = function(el, pom)
{
  CheckBox.superclass.constructor.call(this, el, pom);
  
  /**
   * Returns the control view state.
   *
   * @this {CheckBox}
   * @return {object}
   */
  this.vs = function()
  {
    var res = CheckBox.superclass.vs.call(this);
    res['attrs']['checked'] = this.el.prop('checked') ? 'checked' : '';
    return res;
  }
    
  /**
   * Validates the control. It returns TRUE if the checkbox is checked and FALSE otherwise.
   * 
   * @this {CheckBox}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    if (validator.type() == 'vrequired') return validator.check(this.el.prop('checked'));
    return true;
  };
  
  /**
   * Makes the checkbox unchecked.
   *
   * @this {CheckBox}
   * @return {self}
   */
  this.clean = function()
  {
    this.el.prop('checked', false);
    return this;
  };
};

$pom.registerControl('checkbox', CheckBox);

/**
 * Represents the <input type="radio"> HTML element.
 *
 * @constructor
 * @this {Radio}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Radio = function(el, pom)
{
  Radio.superclass.constructor.call(this, el, pom);
  
  /**
   * Returns the control view state.
   *
   * @this {Radio}
   * @return {object}
   */
  this.vs = function()
  {
    var res = Radio.superclass.vs.call(this);
    res['attrs']['checked'] = this.el.prop('checked') ? 'checked' : '';
    return res;
  }
    
  /**
   * Validates the control. It returns TRUE if the radio button is checked and FALSE otherwise.
   * 
   * @this {Radio}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    if (validator.type() == 'vrequired') return validator.check(this.el.prop('checked'));
    return true;
  };
  
  /**
   * Makes the radio button unchecked.
   *
   * @this {Radio}
   * @return {self}
   */
  this.clean = function()
  {
    this.el.prop('checked', false);
    return this;
  };
};

$pom.registerControl('radio', Radio);

/**
 * Represents the <button> HTML element.
 *
 * @constructor
 * @this {Button}
 * @param {jQuery} el - jQuery instance of the <button> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Button = function(el, pom)
{
  Button.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('button', Button);

/**
 * Represents the <img> HTML element.
 *
 * @constructor
 * @this {Image}
 * @param {jQuery} el - jQuery instance of the <img> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Image = function(el, pom)
{
  Image.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('image', Image);

/**
 * Represents the autocomplete combobox that enables users to quickly find 
 * and select from a pre-populated list of values as they type.
 *
 * @constructor
 * @this {Autofill}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
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

/**
 * This control is wrapper around the CKEditor.
 *
 * @constructor
 * @this {CKEditor}
 * @param {jQuery} el - jQuery instance of the <textarea> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var CKEditor = function(el, pom)
{
  CKEditor.superclass.constructor.call(this, el, pom);
  
  /**
   * Returns the control view state.
   *
   * @this {CKEditor}
   * @return {object}
   */
  this.vs = function()
  {
    return CKEditor.superclass.vs.call(this, ['style', 'class']);
  }
  
  /**
   * Initializes the control.
   *
   * @this {CKEditor}
   * @return {self}
   */
  this.init = function()
  {
    CKEditor.superclass.init.call(this);
    if (this.editor) this.editor.destroy();
    this.editor = CKEDITOR.replace(el.attr('id'), eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  /**
   * Removes the control.
   *
   * @this {CKEditor}
   * @return {self}
   */
  this.remove = function()
  {
    if (this.editor) this.editor.destroy();
    CKEditor.superclass.remove.call(this);
    return this;
  };
  
  /**
   * Moves the selection focus to the editing area space in the CKEditor.
   *
   * @this CKEditor
   * @return {self}
   */
  this.focus = function()
  {
    this.editor.focus();
    return this;
  };
  
  /**
   * Returns the control value (CKEditor's content).
   *
   * @this {CKEditor}
   * @return {string}
   */
  this.value = function()
  {
    if (this.editor) return this.editor.getData();
    return CKEditor.superclass.value.call(this);
  }
  
  /**
   * Validates the control value.
   *
   * @this {CKEditor}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
  
  /**
   * Removes the CKEditor's content.
   *
   * @this {CKEditor}
   * @return {self}   
   */
  this.clean = function()
  {
    CKEDITOR.instances[this.el.attr('id')].setData('');
    return this;
  };
};

$pom.registerControl('ckeditor', CKEditor);

/**
 * Represents the <select> HTML element.
 *
 * @constructor
 * @this {DropDownBox}
 * @param {jQuery} el - jQuery instance of the <select> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var DropDownBox = function(el, pom)
{
  DropDownBox.superclass.constructor.call(this, el, pom);
  
  /**
   * Validates the control value.
   *
   * @this {DropDownBox}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    var value = this.value();
    if ($.isArray(value)) value = value.join('');
    return validator.check(value);
  };
};

$pom.registerControl('dropdownbox', DropDownBox);

/**
 * This control is used to output pagination controls such as page numbers and next/previous links.
 *
 * @constructor
 * @this {Paginator}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Paginator = function(el, pom)
{
  Paginator.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('paginator', Paginator);

/**
 * This control is wrapper around the jQuery Spectrum Color Picker plugin.
 *
 * @constructor
 * @this {ColorPicker}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var ColorPicker = function(el, pom)
{
  ColorPicker.superclass.constructor.call(this, el, pom);
  
  /**
   * Initializes the control.
   *
   * @this {ColorPicker}
   * @return {self}
   */
  this.init = function()
  {
    ColorPicker.superclass.init.call(this);
    this.el.spectrum(eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  /**
   * Redraws the control.
   *
   * @this {ColorPicker}
   * @param {object|string} obj - determines HTML of the control or its attributes which need to be updated.
   * @param {object} removedAttributes - determines the control attributes which need to be removed.
   * @return {self}
   */
  this.refresh = function(obj, removedAttributes)
  {
    this.el.spectrum('destroy');
    ColorPicker.superclass.refresh.call(this, obj, removedAttributes);
    return this;
  }
  
  /**
   * Removes the control.
   *
   * @this {ColorPicker}
   * @return {self}
   */
  this.remove = function()
  {
    this.el.spectrum('destroy');
    ColorPicker.superclass.remove.call(this);
    return this;
  };
 
  /**
   * Validates the control value.
   *
   * @this {ColorPicker}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$pom.registerControl('colorpicker', ColorPicker);

/**
 * This control is wrapper for the jQuery DateTime Picker plugin.
 *
 * @constructor
 * @this {DateTimePicker}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var DateTimePicker = function(el, pom)
{
  DateTimePicker.superclass.constructor.call(this, el, pom);
  
  /**
   * Initializes the control.
   *
   * @this {DateTimePicker}
   * @return {self}
   */
  this.init = function()
  {
    DateTimePicker.superclass.init.call(this);
    this.el.datetimepicker('destroy').datetimepicker(eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  /**
   * Removes the control.
   *
   * @this {DateTimePicker}
   * @return {self}
   */
  this.remove = function()
  {
    this.el.datetimepicker('destroy');
    DateTimePicker.superclass.remove.call(this);
    return this;
  };
 
  /**
   * Validates the control value.
   *
   * @this {DateTimePicker}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$pom.registerControl('datetimepicker', DateTimePicker);

/**
 * This control is wrapper for jQuery noUiSlider plugin.
 *
 * @constructor
 * @this {Slider}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Slider = function(el, pom)
{
  Slider.superclass.constructor.call(this, el, pom);
  
  /**
   * Initializes the control.
   *
   * @this {Slider}
   * @return {self}
   */
  this.init = function()
  {
    Slider.superclass.init.call(this);
    this.el.noUiSlider(eval('(' + (this.el.attr('data-settings') || '{}') + ')'), this.el.html() ? true : false);
    return this;
  };
};

$pom.registerControl('slider', Slider);

/**
 * Represents the <a> HTML element.
 *
 * @constructor
 * @this {HyperLink}
 * @param {jQuery} el - jQuery instance of the <a> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var HyperLink = function(el, pom)
{
  HyperLink.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('hyperlink', HyperLink);

/**
 * Represents any container HTML element.
 *
 * @constructor
 * @this {Panel}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Panel = function(el, pom)
{
  Panel.superclass.constructor.call(this, el, pom);

  /**
   * Returns the control that contained in the given panel.
   *
   * @this {Panel}
   * @param {string} id - the unique or logic identifier of the control.
   * @return {Control}
   */
  this.get = function(id)
  {
    return this.pom.get(id, this.el);
  };
  
  /**
   * Returns TRUE if the panel has a control, otherwise it returns FALSE.
   *
   * @this {Panel}
   * @param {string} id - the unique or logic identifier of the control.
   * @return {boolean}
   */
  this.has = function(id)
  {
    if (id instanceof Control) id = id.el.attr('id');
    var ctrl = this.get(id);
    return ctrl !== false && ctrl !== this; 
  };
  
  /**
   * Returns controls of the panel.
   *
   * @this {Panel}
   * @param {boolean} searchRecursively - determines whether controls of the nested panels also should be returned.
   * @return {array}
   */
  this.getControls = function(searchRecursively)
  {
    var ctrl, ctrls = [], bind = this;
    if (searchRecursively)
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
  
  /**
   * Cleans values of the panel's controls.
   *
   * @this {Panel}
   * @param {boolean} searchRecursively - determines whether controls of the nested panels also should be cleaned.
   * @return {self}
   */
  this.clean = function(searchRecursively)
  {
    $.each(this.getControls(searchRecursively), function(index, ctrl)
    {
      ctrl.clean();
    });
    return this;
  };
  
  /**
   * Gets checkboxes of the panel checked or unchecked.
   *
   * @this {Panel}
   * @param {boolean} flag - if it is TRUE, a checkbox will be checked. Otherwise it will be unchecked.
   * @param {boolean} searchRecursively - determines whether checkboxes of the nested panels also should be processed.
   */
  this.check = function(flag, searchRecursively)
  {
    $.each(this.getControls(searchRecursively), function(index, ctrl)
    {
      if (ctrl instanceof CheckBox) ctrl.el.prop('checked', flag);
    });
    return this;
  };
  
  /**
   * Redraws the control.
   *
   * @this {Panel}
   * @param {object|string} obj - determines HTML of the control or its attributes which need to be updated.
   * @param {object} removedAttributes - determines the control attributes which need to be removed.
   * @return {self}
   */
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

/**
 * Represents any data list that organized as a table with pagination.
 *
 * @constructor
 * @this {Grid}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Grid = function(el, pom)
{
  Grid.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('grid', Grid, Panel);

var visiblePopupIdentifiers = [];

/**
 * Represents a simple popup panel.
 *
 * @constructor
 * @this {Panel}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Popup = function(el, pom)
{
  Popup.superclass.constructor.call(this, el, pom);
  
  var bind = this, id = el.attr('id');
  
  /**
   * Stops propagation of the hide event when you click anywhere on the popup area.
   *
   * @this {Element}
   * @param {Event} e - an event object.
   * @private
   */
  var stop = function(e)
  {
    e.stopPropagation();
  };
  
  /**
   * Hides the popup when you click the document area.
   *
   * @this {Element}
   * @param {Event} e - an event object.
   * @private
   */
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
  
  /**
   * Hides the popup when you press the ESCAPE button.
   *
   * @this {Element}
   * @param {Event} e - an event object.
   * @private
   */
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
  
  /**
   * Shows the popup.
   *
   * @this {Popup}
   * @param {boolean} center - determines whether the popup will be shown in the center of the screen.
   * @return {self}
   */
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
  
  /**
   * Hides the popup.
   *
   * @this {Popup}
   * @remove {self}
   */
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
  
  /**
   * Removes the popup.
   *
   * @this {Popup}
   * @return {self}
   */
  this.remove = function()
  {
    this.hide();
    Popup.superclass.remove.call(this);
    return this;
  };
};

$pom.registerControl('popup', Popup, Panel);

/**
 * This control is a simple image editor that provides base functionality for uploading and editing images.
 *
 * @constructor
 * @this {ImgEditor}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var ImgEditor = function(el, pom)
{
  ImgEditor.superclass.constructor.call(this, el, pom);
  
  var bind = this, id = el.attr('id'), ops, paper, image;
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
  
  /**
   * Changes dimensions of the image area of the editor.
   *
   * @this {Window}
   * @private
   */
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
  
  /**
   * Rotates the image when the angle of rotation is changed.
   *
   * @this {Element}
   * @private
   */
  var changeAngle = function()
  {
    transform(image.attrs.x, image.attrs.y, $(this).val(), ops.scale);
  };
  
  /**
   * Scales the image when the scale is changed.
   *
   * @this {Element}
   * @private
   */
  var changeScale = function()
  {
    transform(image.attrs.x, image.attrs.y, ops.angle, $(this).val());
  };
  
  /**
   * Applies transformation to the image.
   *
   * @this {Window}
   * @param {float} x - the X coordinate of the image.
   * @param {float} y - the Y coordinate of the image.
   * @param {float} angle - the image rotation angle.
   * @param {float} scale - the image scale.
   * @private
   */
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
  
  /**
   * Draws the crop frame.
   *
   * @this {Window}
   * @param {integer} cropLeft - X coordinate of the left top corner of the crop.
   * @param {integer} cropTop - Y coordinate of the left top corner of the crop.
   * @private
   */
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
  
  /**
   * Creates or removes the crop.
   *
   * @this {Window}
   * @param 
   * @private
   */
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
  
  /**
   * Calls the callback to transform the image on the server side.
   *
   * @this {Element}
   * @param {Event} e - the event object.
   * @private
   */
  var apply = function(e)
  {
    if (e.data.useOriginal) 
    {
      bind.hide();
      $ajax.doit(ops.callback, ops.UID);
    }
    else 
    {
      var data = bind.getTransformData();
      bind.hide();
      $ajax.doit(ops.callback, ops.UID, data);
    }
  };
  
  $(window).resize(resize);
  
  /**
   * Initializes the image editor.
   *
   * @this {ImgEditor}
   * @return {self}
   */
  this.init = function()
  {
    Popup.superclass.init.call(this);
    if (paper) paper.remove();
    paper = new Raphael('canvas_' + id);
    return this;
  };
  
  /**
   * Loads the image to editor.
   *
   * @this {ImgEditor}
   * @param {string} url - the image URL.
   * @param {integer} width - the image width.
   * @param {integer} height - the image height.
   * @param {object} options - the image editor settings. 
   * @return {self}
   */
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
    this.get('btnApply').el.off('click', apply).on('click', {'useOriginal': false}, apply);
    this.get('btnUseOriginal').el.off('click', apply).on('click', {'useOriginal': true}, apply);
    crop();
    return this;
  };
  
  /**
   * Returns data of the image transformation.
   *
   * @this {ImgEditor}
   * @return {object}
   */
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
  
  /**
   * Removes the image editor.
   *
   * @this {ImgEditor}
   * @return {self}
   */
  this.remove = function()
  {
    $(window).off('resize', resize);
    this.hide();
    Popup.superclass.remove.call(this);
    return this;
  };
};

$pom.registerControl('imgeditor', ImgEditor, Popup);

/**
 * The base class of validators.
 *
 * @constructor
 * @this {Validator}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var Validator = function(el, pom)
{
  Validator.superclass.constructor.call(this, el, pom);
     
  /**
   * Validation statuses of the validator's controls.
   */
  this.result = {};
      
  /**
   * Sets the validator state.
   *
   * @this {Validator}
   * @param {boolean} flag - the validator state.
   * @return {self}
   */
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
    
  /**
   * Returns the current state of a validator.
   *
   * @this {Validator}
   * @return {boolean}
   */
  this.getState = function()
  {
    return this.el.attr('data-state') == '1';
  };
    
  /**
   * Returns unique identifiers of the validating controls.
   *
   * @this {Validator}
   * @return {array}
   */
  this.getControls = function()
  {
    var ctrls = this.el.attr('data-controls');
    if (!ctrls) return [];
    return ctrls.split(/\s*,\s*/);
  };
    
  /**
   * Returns groups of validation which the validator belongs to.
   *
   * @this {Validator}
   * @return {array}
   */
  this.getGroups = function()
  {
    return (this.el.attr('data-groups') || 'default').split(/\s*,\s*/)
  };
  
  /**
   * Returns the validation mode.
   *
   * @this {Validator}
   * @return {string}
   */
  this.getMode = function()
  {
    return (this.el.attr('data-mode') || 'AND').toUpperCase();
  };
   
  /**
   * Returns the validator's index.
   *
   * @this {Validator}
   * @return {integer}
   */
  this.getIndex = function()
  {
    return parseInt(this.el.attr('data-index')) || 0;
  };

  /**
   * Returns TRUE if the validators has the given group(s), otherwise it returns FALSE.
   *
   * @this {Validator}
   * @return {boolean}   
   */
  this.hasGroup = function(group)
  {
    var groups = this.getGroups();
    if (!$.isArray(group)) return $.inArray(group, groups);
    for (var i = 0; i < group.length; i++) if ($.inArray(group[i], groups) != -1) return true;
    return false;
  };
  
  /**
   * Locks or unlocks the validator.
   *
   * @this {Validator}
   * @param {boolean} flag - determines whether the validator will be locked or unlocked.
   * @return {self}
   */
  this.lock = function(flag)
  {
    if (flag) this.el.attr('data-locked', 1);
    else this.el.removeAttr('data-locked');
    return this;
  };
  
  /**
   * Returns TRUE if the validator is locked and FALSE if it isn't.
   *
   * @this {Validator}
   * @return {boolean}
   */
  this.isLocked = function()
  {
    return this.el.attr('data-locked');
  };
    
  /**
   * Sets the validator's state to TRUE.
   *
   * @this {Validator}
   * @return {self}
   */
  this.clean = function()
  {
    return this.setState(true);
  };

  /**
   * Validates the validator's controls.
   *
   * @this {Validator}
   * @return {boolean}
   */
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
  
  /**
   * Redraws the validator.
   *
   * @this {Validator}
   * @param {object|string} obj - determines HTML of the validator or its attributes which need to be updated.
   * @param {object} removedAttributes - determines the validator attributes which need to be removed.
   * @return {self}
   */
  this.refresh = function(obj, removedAttributes)
  {
    Validator.superclass.refresh.call(this, obj, removedAttributes);
    return this.setState(this.el.attr('data-state') == '1');
  };
};

$pom.registerControl('validator', Validator);

/**
 * This validator checks whether the value of the validating control is not empty.
 *
 * @constructor
 * @this {VRequired}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VRequired = function(el, pom)
{
  VRequired.superclass.constructor.call(this, el, pom);
    
  /**
   * Returns TRUE if the given value is not empty string and FALSE otherwise.
   *
   * @this {VRequired}
   * @param {mixed} the validating value.
   * @return {boolean}
   */
  this.check = function(value)
  {
    if (value === false || value === true) return value;
    if (this.el.attr('data-trim')) return $.trim(value) != '';
    return value != '';
  };
};

$pom.registerValidator('vrequired', VRequired);

/**
 * This validator checks whether the value of the validating control matches the given regular expression.
 *
 * @constructor
 * @this {VRegExp}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VRegExp = function(el, pom)
{
  VRegExp.superclass.constructor.call(this, el, pom);
    
  /**
   * Returns TRUE if the given value matches the validator's regular expression. Otherwise, it returns FALSE.
   *
   * @this {VRegExp}
   * @param {mixed} value - the validating value.
   * @return {boolean}
   */
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

/**
 * This validator checks whether the value of the validating control matches valid email format.
 *
 * @constructor
 * @this {VEmail}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VEmail = function(el, pom)
{
  VEmail.superclass.constructor.call(this, el, pom);
};

$pom.registerValidator('vemail', VRegExp);

/**
 * This validator checks whether the values of the validating controls are equal to each other.
 *
 * @constructor
 * @this {VCompare}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VCompare = function(el, pom)
{
  VCompare.superclass.constructor.call(this, el, pom);
    
  /**
   * Validates value of a control.
   * The returning result depends on the validator's mode:
   * AND - it returns TRUE if all controls values are equal to each other and FALSE otherwise.
   * OR - it returns TRUE if value of one control is equal to the value of at least one other control and FALSE otherwise.
   * XOR - it returns TRUE if exactly two controls have equal values and FALSE otherwise. 
   *
   * @this {VCompare}
   * @return {boolean}
   */
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
  
  /**
   * Returns the given value in lower case if attributes caseInsensitive is defined. Otherwise it returns the given value without any changes.
   *
   * @this {VCompare}
   * @param {mixed} value - the validating value.
   * @return {mixed}
   */
  this.check = function(value)
  {
    return this.el.attr('data-caseinsensitive') == '1' ? (value + '').toLowerCase() : value;
  };
};

$pom.registerValidator('vcompare', VCompare);

/**
 * This validator checks controls based on a custom validation function.
 *
 * @constructor
 * @this {VCustom}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VCustom = function(el, pom)
{
  VCustom.superclass.constructor.call(this, el, pom);
    
  /**
   * Calls the custom validation function (from the client or/and server side).
   *
   * @this {VCustom}
   * @return {boolean}
   */
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