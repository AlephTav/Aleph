var VEmail = function(el)
{
  VEmail.superclass.constructor.call(this, el);
};

$a.pom.registerValidator('vemail', VRegExp);