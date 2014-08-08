var Input = function(el, pom)
{
  Input.superclass.constructor.call(this, el, pom);
  
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$pom.registerControl('input', Input);