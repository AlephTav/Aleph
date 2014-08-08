var Grid = function(el, pom)
{
  Grid.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('grid', Grid, Panel);