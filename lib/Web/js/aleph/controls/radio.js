/**
 * Represents the <input type="radio"> HTML element.
 *
 * @constructor
 * @this {Radio}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Radio = function(el, pom)
{
  Radio.superclass.constructor.call(this, el, pom);
  
  /**
   * Returns the control view state.
   *
   * @this {Radio}
   * @return {object}
   */
  this.vs = function()
  {
    var res = Radio.superclass.vs.call(this);
    res['attrs']['checked'] = this.el.prop('checked') ? 'checked' : '';
    return res;
  }
    
  /**
   * Validates the control. It returns TRUE if the radio button is checked and FALSE otherwise.
   * 
   * @this {Radio}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    if (validator.type() == 'vrequired') return validator.check(this.el.prop('checked'));
    return true;
  };
  
  /**
   * Makes the radio button unchecked.
   *
   * @this {Radio}
   * @return {self}
   */
  this.clean = function()
  {
    this.el.prop('checked', false);
    return this;
  };
};

$pom.registerControl('radio', Radio);