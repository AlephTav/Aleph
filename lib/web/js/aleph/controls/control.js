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
    if (!check || check()) (toContainer ? $('#' + CONTAINER_PREFIX + this.el.attr('id')) : this.el).bind(type, callback);
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
    if (e != undefined) (e.toContainer ? $('#' + CONTAINER_PREFIX + this.el.attr('id')) : this.el).unbind(e.type, e.callback);
    return this;
  };
    
  Control.prototype.removeEvents = function(completely)
  {
    var container = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
    if (container) container.unbind();
    this.el.unbind();
    if (completely) this.events = {};
    return this;
  };
    
  Control.prototype.restoreEvents = function()
  {
    var e, container = $('#' + CONTAINER_PREFIX + this.el.attr('id'));
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