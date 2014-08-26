var visiblePopupIdentifiers = [];

/**
 * Represents a simple popup panel.
 *
 * @constructor
 * @this {Panel}
 * @param {jQuery} el - jQuery instance of the external container element of the control.
 * @param {POM} pom - the instance of the POM object.
 */
var Popup = function(el, pom)
{
  Popup.superclass.constructor.call(this, el, pom);
  
  var bind = this, id = el.attr('id');
  
  /**
   * Stops propagation of the hide event when you click anywhere on the popup area.
   *
   * @this {Element}
   * @param {Event} e - an event object.
   * @private
   */
  var stop = function(e)
  {
    e.stopPropagation();
  };
  
  /**
   * Hides the popup when you click the document area.
   *
   * @this {Element}
   * @param {Event} e - an event object.
   * @private
   */
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
  
  /**
   * Hides the popup when you press the ESCAPE button.
   *
   * @this {Element}
   * @param {Event} e - an event object.
   * @private
   */
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
  
  /**
   * Shows the popup.
   *
   * @this {Popup}
   * @param {boolean} center - determines whether the popup will be shown in the center of the screen.
   * @return {self}
   */
  this.show = function(center)
  {
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
    var parentID = visiblePopupIdentifiers.pop();
    if (parentID) 
    {
      this.el.css('z-index', $('#' + parentID).css('z-index') + 1000);
      visiblePopupIdentifiers.push(parentID);
    }
    visiblePopupIdentifiers.push(id);
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
      $(this.el).off('click', stop).click(stop);
      $(document.body).off('click', hide).click(hide);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).off('keyup', escape).keyup(escape);
    }
    var btnClose = this.el.attr('data-closebuttons');
    if (btnClose) $(btnClose).off('click', hide).click(hide);
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
    return this;
  };
  
  /**
   * Hides the popup.
   *
   * @this {Popup}
   * @remove {self}
   */
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
      $(document.body).off('click', hide);
      $(this.el).off('click', stop);
    }
    if (this.el.attr('data-closebyescape'))
    {
      $(document.body).off('keyup', escape);
    }
    if (this.el.attr('data-closebuttons')) $(this.el.attr('data-closebuttons')).off('click', hide);
    var idx = $.inArray(id, visiblePopupIdentifiers);
    if (idx > -1) delete visiblePopupIdentifiers[idx];
    return this;
  };
  
  /**
   * Removes the popup.
   *
   * @this {Popup}
   * @return {self}
   */
  this.remove = function()
  {
    this.hide();
    Popup.superclass.remove.call(this);
    return this;
  };
};

$pom.registerControl('popup', Popup, Panel);