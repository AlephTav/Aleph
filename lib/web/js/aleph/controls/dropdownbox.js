var DropDownBox = function(el)
{
  DropDownBox.superclass.constructor.call(this, el);
  
  this.validate = function(validator)
  {
    var value = this.value();
    if ($.isArray(value)) value = value.join('');
    return validator.check(value);
  };
};

$a.pom.registerControl('dropdownbox', DropDownBox);