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