/**
 * Represents the <img> HTML element.
 *
 * @constructor
 * @this {Image}
 * @param {jQuery} el - jQuery instance of the <img> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Image = function(el, pom)
{
  Image.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('image', Image);