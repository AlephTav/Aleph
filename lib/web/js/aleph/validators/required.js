var VRequired = function(el)
{
  VRequired.superclass.constructor.call(this, el);
    
  this.check = function(value)
  {
    if (value === false || value === true) return value;
    if (this.el.attr('data-trim')) return $.trim(value) != '';
    return value != '';
  };
};

$a.pom.registerValidator('vrequired', VRequired);