var TextBox = function(el, pom)
{
  TextBox.superclass.constructor.call(this, el, pom);
 
  var id = el.attr('id');
  
  var focus = function()
  {
    var el = $(this);
    if (el.val() == el.attr('data-default')) el.val('');
  };
  
  var blur = function()
  {
    var el = $(this);
    if (!el.val()) el.val(el.attr('data-default'));
  };
  
  var check = function()
  {
    return !!$('#' + id).attr('data-default');
  };
    
  this.addEvent(id + '_txt_e1', 'focus', focus, check);
  this.addEvent(id + '_txt_e2', 'blur', blur, check);
    
  this.init = function()
  {
    TextBox.superclass.init.call(this);
    var dv = this.el.attr('data-default');
    if (dv) if (!this.el.val()) this.el.val(dv);
    return this;
  };
  
  this.refresh = function(obj, removedAttributes)
  {
    focus.call(this.el);
    return TextBox.superclass.refresh.call(this, obj, removedAttributes);
  };
    
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
    
  this.clean = function()
  {
    this.el.val(this.el.attr('data-default') || '');
    return this;
  };
};

$pom.registerControl('textbox', TextBox);