var ColorPicker = function(el)
{
  ColorPicker.superclass.constructor.call(this, el);
  
  this.init = function()
  {
    ColorPicker.superclass.init.call(this);
    this.el.spectrum(eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  this.refresh = function(obj, removedAttributes)
  {
    this.el.spectrum('destroy');
    ColorPicker.superclass.refresh.call(this, obj, removedAttributes);
  }
  
  this.remove = function()
  {
    this.el.spectrum('destroy');
    ColorPicker.superclass.remove.call(this);
  };
 
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$a.pom.registerControl('colorpicker', ColorPicker);