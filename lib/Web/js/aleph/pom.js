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