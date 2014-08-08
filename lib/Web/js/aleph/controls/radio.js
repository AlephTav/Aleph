var Radio = function(el, pom)
{
  Radio.superclass.constructor.call(this, el, pom);
  
  this.vs = function()
  {
    var res = Radio.superclass.vs.call(this);
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

$pom.registerControl('radio', Radio);