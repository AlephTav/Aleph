var DropDownBox = function(el, pom)
{
  DropDownBox.superclass.constructor.call(this, el, pom);
  
  this.validate = function(validator)
  {
    var value = this.value();
    if ($.isArray(value)) value = value.join('');
    return validator.check(value);
  };
};

$pom.registerControl('dropdownbox', DropDownBox);