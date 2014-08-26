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