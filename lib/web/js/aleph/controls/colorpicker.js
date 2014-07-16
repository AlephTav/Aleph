var ColorPicker = function(el, pom)
{
  ColorPicker.superclass.constructor.call(this, el, pom);
  
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
    return this;
  }
  
  this.remove = function()
  {
    this.el.spectrum('destroy');
    ColorPicker.superclass.remove.call(this);
    return this;
  };
 
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
};

$pom.registerControl('colorpicker', ColorPicker);