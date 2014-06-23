var DateTimePicker = function(el)
{
  DateTimePicker.superclass.constructor.call(this, el);
  
  this.init = function()
  {
    DateTimePicker.superclass.init.call(this);
    this.el.datetimepicker('destroy').datetimepicker(eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  this.remove = function()
  {
    this.el.datetimepicker('destroy');
    DateTimePicker.superclass.remove.call(this);
  };
 
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$a.pom.registerControl('datetimepicker', DateTimePicker);