/**
 * Represents the <select> HTML element.
 *
 * @constructor
 * @this {DropDownBox}
 * @param {jQuery} el - jQuery instance of the <select> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var DropDownBox = function(el, pom)
{
  DropDownBox.superclass.constructor.call(this, el, pom);
  
  /**
   * Validates the control value.
   *
   * @this {DropDownBox}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    var value = this.value();
    if ($.isArray(value)) value = value.join('');
    return validator.check(value);
  };
};

$pom.registerControl('dropdownbox', DropDownBox);