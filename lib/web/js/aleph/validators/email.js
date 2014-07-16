var VEmail = function(el, pom)
{
  VEmail.superclass.constructor.call(this, el, pom);
};

$pom.registerValidator('vemail', VRegExp);