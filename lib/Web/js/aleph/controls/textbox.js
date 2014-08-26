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