/**
 * Represents any data list that organized as a table with pagination.
 *
 * @constructor
 * @this {Grid}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Grid = function(el, pom)
{
  Grid.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('grid', Grid, Panel);