/**
 * This control is wrapper for jQuery noUiSlider plugin.
 *
 * @constructor
 * @this {Slider}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Slider = function(el, pom)
{
  Slider.superclass.constructor.call(this, el, pom);
  
  /**
   * Initializes the control.
   *
   * @this {Slider}
   * @return {self}
   */
  this.init = function()
  {
    Slider.superclass.init.call(this);
    this.el.noUiSlider(eval('(' + (this.el.attr('data-settings') || '{}') + ')'), this.el.html() ? true : false);
    return this;
  };
};

$pom.registerControl('slider', Slider);