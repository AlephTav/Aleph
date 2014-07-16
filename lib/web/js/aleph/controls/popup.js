var visiblePopupIdentifiers = [];

var Popup = function(el, pom)
{
  Popup.superclass.constructor.call(this, el, pom);
  
  var bind = this, id = el.attr('id');
  
  var stop = function(e)
  {
    e.stopPropagation();
  };
  
  var hide = function(e)
  {
    var cid = visiblePopupIdentifiers.pop();
    if (id != cid) visiblePopupIdentifiers.push(cid);
    else
    {
      bind.hide();
      e.stopPropagation();
    } 
  };
  
  var escape = function(e)
  {
    if (e.which == 27) 
    {
      var cid = visiblePopupIdentifiers.pop();
      if (id != cid) visiblePopupIdentifiers.push(cid);
      else
      {
        bind.hide();
        e.stopPropagation();
      } 
    }
  };
  
  this.show = function(center)
  {
    if (this.el.attr('data-overlay'))
    {
      var overlay, selector = this.el.attr('data-overlayselector');
      if (selector) overlay = $(selector);
      else
      {
        overlay = $('#overlay_' + id);
        if (overlay.length == 0)
        {
          var overlayClass = this.el.attr('data-overlayclass');
          overlay = $(document.createElement('div'));
          overlay.attr('id', 'overlay_' + id);
          if (overlayClass) overlay.addClass(overlayClass);
          else overlay.css({position: 'fixed', left: 0, top: 0, width: '100%', height: '100%', backgroundColor: '#000000', opacity: 0.5});
          $(document.body).append(overlay);
        }        
      }  
      if (overlay) 
      {
        overlay.css('z-index', this.el.css('z-index') - 1);
        overlay.show();
      }
    }
    if (this.el.attr('data-closebydocument'))
    {
      $(this.el).unbind('click', stop).click(stop);
      $(document.body).unbind('click', hide).click(hide);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).unbind('keyup', escape).keyup(escape);
    }
    var btnClose = this.el.attr('data-closebuttons');
    if (btnClose) $(btnClose).unbind('click', hide).click(hide);
    var position = this.el.css('position');
    if (position != 'fixed' && position != 'absolute') 
    {
      this.el.css('position', 'fixed');
      position = 'fixed';
    }
    if (center)
    {
      this.el.css({top: (($(window).height() - this.el.outerHeight()) / 2) + (position != 'fixed' ? $(window).scrollTop() : 0) + 'px',
                   left: (($(window).width() - this.el.outerWidth()) / 2) + (position != 'fixed' ? $(window).scrollLeft() : 0) + 'px'});
    }
    this.el.show();
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
    visiblePopupIdentifiers.push(id);
    return this;
  };
  
  this.hide = function()
  {
    this.el.hide();
    if (this.el.attr('data-overlay'))
    {
      var overlay = $(this.el.attr('data-overlayselector') || '#overlay_' + this.el.attr('id'));
      if (overlay) overlay.hide();
    }
    if (this.el.attr('data-closebydocument'))
    {
      $(document.body).unbind('click', hide);
      $(this.el).unbind('click', stop);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).unbind('keyup', escape);
    }
    if (this.el.attr('data-closebuttons')) $(this.el.attr('data-closebuttons')).unbind('click', hide);
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
    return this;
  };
  
  this.remove = function()
  {
    this.hide();
    Popup.superclass.remove.call(this);
    return this;
  };
};

$pom.registerControl('popup', Popup, Panel);