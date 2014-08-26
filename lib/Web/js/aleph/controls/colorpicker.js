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