var Radio = function(el)
{
  Radio.superclass.constructor.call(this, el);
  
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

$a.pom.registerControl('radio', Radio);