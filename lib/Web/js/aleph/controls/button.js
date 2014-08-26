/**
 * Represents the <button> HTML element.
 *
 * @constructor
 * @this {Button}
 * @param {jQuery} el - jQuery instance of the <button> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Button = function(el, pom)
{
  Button.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('button', Button);