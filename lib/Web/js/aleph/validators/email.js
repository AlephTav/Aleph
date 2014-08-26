/**
 * This validator checks whether the value of the validating control matches valid email format.
 *
 * @constructor
 * @this {VEmail}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VEmail = function(el, pom)
{
  VEmail.superclass.constructor.call(this, el, pom);
};

$pom.registerValidator('vemail', VRegExp);