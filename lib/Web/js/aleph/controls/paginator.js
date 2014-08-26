/**
 * This control is used to output pagination controls such as page numbers and next/previous links.
 *
 * @constructor
 * @this {Paginator}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Paginator = function(el, pom)
{
  Paginator.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('paginator', Paginator);