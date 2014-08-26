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