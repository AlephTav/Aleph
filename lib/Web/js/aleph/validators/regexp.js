/**
 * This validator checks whether the value of the validating control matches the given regular expression.
 *
 * @constructor
 * @this {VRegExp}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VRegExp = function(el, pom)
{
  VRegExp.superclass.constructor.call(this, el, pom);
    
  /**
   * Returns TRUE if the given value matches the validator's regular expression. Otherwise, it returns FALSE.
   *
   * @this {VRegExp}
   * @param {mixed} value - the validating value.
   * @return {boolean}
   */
  this.check = function(value)
  {
    var flag, exp = this.el.attr('data-expression');
    if (exp == '' || this.el.attr('data-empty') == '1' && value == '') return true;
    if (exp.charAt(0) == 'i') eval('flag = !' + exp.substr(1) + '.test(value);');
    else eval('flag = ' + exp + '.test(value);');
    return flag;
  };
};

$pom.registerValidator('vregexp', VRegExp);