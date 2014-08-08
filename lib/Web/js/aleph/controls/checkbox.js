var CheckBox = function(el, pom)
{
  CheckBox.superclass.constructor.call(this, el, pom);
  
  this.vs = function()
  {
    var res = CheckBox.superclass.vs.call(this);
    res['attrs']['checked'] = this.el.prop('checked') ? 'checked' : '';
    return res;
  }
    
  this.validate = function(validator)
  {
    if (validator.type() == 'vrequired') return validator.check(this.el.prop('checked'));
    return true;
  };
  
  this.clean = function()
  {
    this.el.prop('checked', false);
    return this;
  };
};

$pom.registerControl('checkbox', CheckBox);