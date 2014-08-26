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