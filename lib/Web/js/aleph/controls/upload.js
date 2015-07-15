/**
 * The wrapper around the jQuery Upload plugin.
 *
 * @constructor
 * @this {Upload}
 * @param {jQuery} el - jQuery instance of the <input type="file"> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Upload = function(el, pom)
{
  Upload.superclass.constructor.call(this, el, pom);
  
  /**
   * Initializes the control.
   *
   * @this {Upload}
   * @return {self}
   */
  this.init = function()
  {
    var bind = this, id = this.el.attr('id'), callback = this.el.attr('data-callback');
    var submit = function(e, data) 
    {
      bind.pom.setVS()
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
  
  /**
   * Removes the control.
   *
   * @this {Upload}
   * @return {self}
   */
  this.remove = function()
  {
    this.el.fileupload('destroy');
    Upload.superclass.remove.call(this);
    return this;
  };
  
  /**
   * Returns the control value.
   * For the Upload control this method always returns empty string.
   *
   * @this {Upload}
   * @return {string}
   */
  this.value = function()
  {
    return '';
  }
};

$pom.registerControl('upload', Upload);