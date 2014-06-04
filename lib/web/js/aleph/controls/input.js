var Input = function(el)
{
  Input.superclass.constructor.call(this, el);
  
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$a.pom.registerControl('input', Input);