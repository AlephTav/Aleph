var VCustom = function(el, pom)
{
  VCustom.superclass.constructor.call(this, el, pom);
    
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
    flag = this.el.attr('data-state') == '1';
    for (var i = 0; i < ctrls.length; i++) this.result[ctrls[i]] = flag;
    this.setState(flag);
    return this.el.attr('data-serverfunction') ? true : flag;
  };
};

$pom.registerValidator('vcustom', VCustom);