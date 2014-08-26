/**
 * Represents the <input> HTML element.
 *
 * @constructor
 * @this {Input}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Input = function(el, pom)
{
  Input.superclass.constructor.call(this, el, pom);
  
  /**
   * Validates the control value.
   *
   * @this {Input}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$pom.registerControl('input', Input);