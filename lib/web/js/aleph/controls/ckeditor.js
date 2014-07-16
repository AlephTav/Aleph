var CKEditor = function(el, pom)
{
  CKEditor.superclass.constructor.call(this, el, pom);
  
  this.vs = function()
  {
    return CKEditor.superclass.vs.call(this, ['style', 'class']);
  }
  
  this.init = function()
  {
    CKEditor.superclass.init.call(this);
    if (this.editor) this.editor.destroy();
    this.editor = CKEDITOR.replace(el.attr('id'), eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  this.remove = function()
  {
    if (this.editor) this.editor.destroy();
    CKEditor.superclass.remove.call(this);
    return this;
  };
  
  this.focus = function()
  {
    this.editor.focus();
    return this;
  };
  
  this.value = function()
  {
    return CKEDITOR.instances[this.el.attr('id')].getData();
  }
  
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
  
  this.clean = function()
  {
    CKEDITOR.instances[this.el.attr('id')].setData('');
    return this;
  };
};

$pom.registerControl('ckeditor', CKEditor);