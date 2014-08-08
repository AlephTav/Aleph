var Button = function(el, pom)
{
  Button.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('button', Button);