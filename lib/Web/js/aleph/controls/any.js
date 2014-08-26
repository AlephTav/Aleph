/**
 * Represents any HTML element.
 *
 * @constructor
 * @this {Any}
 * @param {jQuery} el - jQuery instance of the HTML element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Any = function(el, pom)
{
  Any.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('any', Any);