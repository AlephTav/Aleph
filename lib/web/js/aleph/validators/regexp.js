var VRegExp = function(el, pom)
{
  VRegExp.superclass.constructor.call(this, el, pom);
    
  this.check = function(value)
  {
    var flag, exp = this.el.attr('data-expression');
    if (exp == '' || this.el.attr('data-empty') == '1' && value == '') return true;
    if (exp.charAt(0) == 'i') eval('flag = !' + exp.substr(1) + '.test(value);');
    else eval('flag = ' + exp + '.test(value);');
    return flag;
  };
};

$pom.registerValidator('vregexp', VRegExp);