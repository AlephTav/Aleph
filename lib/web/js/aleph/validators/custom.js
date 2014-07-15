var VCustom = function(el)
{
  VCustom.superclass.constructor.call(this, el);
    
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

$a.pom.registerValidator('vcustom', VCustom);