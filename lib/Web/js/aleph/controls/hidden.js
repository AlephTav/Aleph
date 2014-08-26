/**
 * Represents the <input type="hidden"> HTML element.
 *
 * @constructor
 * @this {Hidden}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Hidden = function(el, pom)
{
  Hidden.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('hidden', Input);