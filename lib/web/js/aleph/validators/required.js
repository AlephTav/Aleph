var VRequired = function(el, pom)
{
  VRequired.superclass.constructor.call(this, el, pom);
    
  this.check = function(value)
  {
    if (value === false || value === true) return value;
    if (this.el.attr('data-trim')) return $.trim(value) != '';
    return value != '';
  };
};

$pom.registerValidator('vrequired', VRequired);