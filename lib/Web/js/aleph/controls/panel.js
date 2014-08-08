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