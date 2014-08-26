/**
 * This validator checks whether the value of the validating control is not empty.
 *
 * @constructor
 * @this {VRequired}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VRequired = function(el, pom)
{
  VRequired.superclass.constructor.call(this, el, pom);
    
  /**
   * Returns TRUE if the given value is not empty string and FALSE otherwise.
   *
   * @this {VRequired}
   * @param {mixed} the validating value.
   * @return {boolean}
   */
  this.check = function(value)
  {
    if (value === false || value === true) return value;
    if (this.el.attr('data-trim')) return $.trim(value) != '';
    return value != '';
  };
};

$pom.registerValidator('vrequired', VRequired);