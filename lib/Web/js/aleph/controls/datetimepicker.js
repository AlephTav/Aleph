var DateTimePicker = function(el, pom)
{
  DateTimePicker.superclass.constructor.call(this, el, pom);
  
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
    return this;
  };
 
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$pom.registerControl('datetimepicker', DateTimePicker);