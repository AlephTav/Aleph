/**
 * Represents the <a> HTML element.
 *
 * @constructor
 * @this {HyperLink}
 * @param {jQuery} el - jQuery instance of the <a> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var HyperLink = function(el, pom)
{
  HyperLink.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('hyperlink', HyperLink);