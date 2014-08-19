var Upload = function(el, pom)
{
  Upload.superclass.constructor.call(this, el, pom);
  
  this.init = function()
  {
    var bind = this, id = this.el.attr('id'), callback = this.el.attr('data-callback');
    var submit = function(e, data) 
    {
      data.formData = {'ajax-method': callback,
                       'ajax-args[]': id,
                       'ajax-key': $(document.body).attr('id'), 
                       'ajax-vs': JSON.stringify(bind.pom.getVS())};
    };
    var always = function(e, data) 
    {
      bind.el = $('#' + id);
    };
    Upload.superclass.init.call(this);
    var ops = eval('(' + (this.el.attr('data-settings') || '{}') + ')');
    ops.dataType = 'json';
    ops.paramName = id + (this.el.attr('multiple') == 'multiple' ? '[]' : '');
    ops.multipart = true;
    this.el.fileupload(ops)
           .off('fileuploadsubmit', submit)
           .off('fileuploadalways', always)
           .on('fileuploadalways', always);
    if (callback) this.el.on('fileuploadsubmit', submit);
    return this;
  };
  
  this.remove = function()
  {
    this.el.fileupload('destroy');
    Upload.superclass.remove.call(this);
    return this;
  };
  
  this.value = function()
  {
    return '';
  }
};

$pom.registerControl('upload', Upload);