/**
 * Represents the <input type="checkbox"> HTML element.
 *
 * @constructor
 * @this {CheckBox}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var CheckBox = function(el, pom)
{
  CheckBox.superclass.constructor.call(this, el, pom);
  
  /**
   * Returns the control view state.
   *
   * @this {CheckBox}
   * @return {object}
   */
  this.vs = function()
  {
    var res = CheckBox.superclass.vs.call(this);
    res['attrs']['checked'] = this.el.prop('checked') ? 'checked' : '';
    return res;
  }
    
  /**
   * Validates the control. It returns TRUE if the checkbox is checked and FALSE otherwise.
   * 
   * @this {CheckBox}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    if (validator.type() == 'vrequired') return validator.check(this.el.prop('checked'));
    return true;
  };
  
  /**
   * Makes the checkbox unchecked.
   *
   * @this {CheckBox}
   * @return {self}
   */
  this.clean = function()
  {
    this.el.prop('checked', false);
    return this;
  };
};

$pom.registerControl('checkbox', CheckBox);