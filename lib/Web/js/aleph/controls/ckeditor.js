/**
 * This control is wrapper around the CKEditor.
 *
 * @constructor
 * @this {CKEditor}
 * @param {jQuery} el - jQuery instance of the <textarea> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var CKEditor = function(el, pom)
{
  CKEditor.superclass.constructor.call(this, el, pom);
  
  /**
   * Returns the control view state.
   *
   * @this {CKEditor}
   * @return {object}
   */
  this.vs = function()
  {
    return CKEditor.superclass.vs.call(this, ['style', 'class']);
  }
  
  /**
   * Initializes the control.
   *
   * @this {CKEditor}
   * @return {self}
   */
  this.init = function()
  {
    CKEditor.superclass.init.call(this);
    if (this.editor) this.editor.destroy();
    this.editor = CKEDITOR.replace(el.attr('id'), eval('(' + (this.el.attr('data-settings') || '{}') + ')'));
    return this;
  };
  
  /**
   * Removes the control.
   *
   * @this {CKEditor}
   * @return {self}
   */
  this.remove = function()
  {
    if (this.editor) this.editor.destroy();
    CKEditor.superclass.remove.call(this);
    return this;
  };
  
  /**
   * Moves the selection focus to the editing area space in the CKEditor.
   *
   * @this CKEditor
   * @return {self}
   */
  this.focus = function()
  {
    this.editor.focus();
    return this;
  };
  
  /**
   * Returns the control value (CKEditor's content).
   *
   * @this {CKEditor}
   * @return {string}
   */
  this.value = function()
  {
    if (this.editor) return this.editor.getData();
    return CKEditor.superclass.value.call(this);
  }
  
  /**
   * Validates the control value.
   *
   * @this {CKEditor}
   * @param {Validator} validator - the validator instance.
   * @return {boolean}
   */
  this.validate = function(validator)
  {
    return validator.check(this.value());
  };
  
  /**
   * Removes the CKEditor's content.
   *
   * @this {CKEditor}
   * @return {self}   
   */
  this.clean = function()
  {
    CKEDITOR.instances[this.el.attr('id')].setData('');
    return this;
  };
};

$pom.registerControl('ckeditor', CKEditor);