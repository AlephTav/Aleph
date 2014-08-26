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