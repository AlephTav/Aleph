var Slider = function(el, pom)
{
  Slider.superclass.constructor.call(this, el, pom);
  
  this.init = function()
  {
    Slider.superclass.init.call(this);
    this.el.noUiSlider(eval('(' + (this.el.attr('data-settings') || '{}') + ')'), this.el.html() ? true : false);
    return this;
  };
};

$pom.registerControl('slider', Slider);