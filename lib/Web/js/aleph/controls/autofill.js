/**
 * Represents the autocomplete combobox that enables users to quickly find 
 * and select from a pre-populated list of values as they type.
 *
 * @constructor
 * @this {Autofill}
 * @param {jQuery} el - jQuery instance of the <input> element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Autofill = function(el, pom)
{
  Autofill.superclass.constructor.call(this, el, pom);
   
  var i, t, xhr, id = el.attr('id');
  
  var hideList = function()
  {
    $('#list-' + id).hide();
    $(document.body).off('click', hideList);
  };

  this.addEvent(id + '_af_e1', 'keyup', function(e)
  {
    if (e.keyCode == 13 || e.keyCode == 9 || e.keyCode >= 37 && e.keyCode <= 40) return false;
    if (t) clearTimeout(t);
    t = setTimeout(function()
    {
      var el = $('#' + id), list = $('#list-' + id), callback = el.attr('data-callback') || '@' + id + '->search';
      list.hide();
      if (xhr) xhr.abort();
      xhr = $ajax.setup({'global': false}, true).doit(el.attr('data-callback'), e.currentTarget.value).done(function(html)
      {
        if (html != '')
        {
          var offset = el.offset(), width = parseInt(list.css('width'));
          list.html(html);
          list.css({'position': 'absolute', 'display': 'block', 'width': width ? width : el.width()});
          list.offset({'left': offset.left, 'top': offset.top + el.outerHeight()});
          list.scrollTop(0);
          $(document.body).click(hideList);
        }
        else list.hide();
        xhr = null; i = -1;
      });
    }, el.attr('data-timeout') || 0);
  });
    
  this.addEvent(id + '_af_e2', 'keydown', function(e)
  {
    if (xhr || e.keyCode != 13 && e.keyCode != 38 && e.keyCode != 40) return;
    var el = $('#' + id), list = $('#list-' + id);
    if (e.keyCode == 13) 
    {
      if (list.css('display') != 'none') $('#list-' + id + ' li:eq(' + i + ')').click();
    }
    else
    {
      var css = el.attr('data-activeitemclass'), items = $('#list-' + id + ' li').removeClass(css).length;
      if (items == 0) return;
      if (list.css('display') == 'none') list.show();
      i += e.keyCode - 39;
      if (i < 0) i = items - 1;
      else if (i >= items) i = 0;
      var item = $('#list-' + id + ' li:eq(' + i + ')').addClass(css);
      list.scrollTop(item[0].offsetTop - (list.height() - item.height()) / 2);
    }
  });
  
  this.addEvent(id + '_af_e3', 'blur', function(e)
  {
    if (xhr) xhr.abort();
  });
};

$pom.registerControl('autofill', Autofill, TextBox);