/**
 * This validator checks controls based on a custom validation function.
 *
 * @constructor
 * @this {VCustom}
 * @param {jQuery} el - jQuery instance of the external container element of the validator.
 * @param {POM} pom - the instance of the POM object.
 */
var VCustom = function(el, pom)
{
  VCustom.superclass.constructor.call(this, el, pom);
    
  /**
   * Calls the custom validation function (from the client or/and server side).
   *
   * @this {VCustom}
   * @return {boolean}
   */
  this.validate = function()
  {
    var flag, validate = this.el.attr('data-clientfunction');
    if (validate)
    {    
      flag = window[validate](this);
      this.setState(flag);
      return flag;
    }
    var ctrls = this.getControls();
    flag = this.el.attr('data-serverfunction') ? this.el.attr('data-state') == '1' : true;
    for (var i = 0; i < ctrls.length; i++) this.result[ctrls[i]] = flag;
    this.setState(flag);
    return true;
  };
};

$pom.registerValidator('vcustom', VCustom);