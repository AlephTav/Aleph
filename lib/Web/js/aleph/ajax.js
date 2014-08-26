/**
 * Class for performing Ajax requests using jQuery Ajax API.
 */
var Ajax = (function(undefined)
{
  var options, cursor, lock = {};
  
  var showLoader = function()
  {
    cursor = document.body.style.cursor;
    document.body.style.cursor = 'wait';
  };
  
  var hideLoader = function()
  {
    document.body.style.cursor = cursor;
  };

  /**
   * Initializes the Ajax instance.
   *
   * @constructor
   * @this {Ajax}
   * @param {POM} pom - the instance of the POM object.
   */
  var Ajax = function(pom)
  {
    this.pom = pom;
    this.setup({
      'global': true,
      'type': 'POST',
      'url': window.location.href,
      'ajaxStart': showLoader,
      'ajaxStop': hideLoader,
      'dataFilter': function(data, type)
      {
        if ($.type(data) == 'object') data = $(data[0].body).text();
        if (data.length == 0)
        {
          pom.setVS();
          return 'null';
        }
        var sep = data.substr(0, 13);
        var script, p = data.lastIndexOf(sep);
        if (p < 1) script = data;
        else script = data.substr(13, p - 13);
        if (script.length) $.globalEval(script);
        pom.setVS();
        return p > 0 ? data.substr(p + 13) : 'null';
      }
    });
  };
  
  /**
   * Sets the Ajax settings.
   *
   * @this {Ajax}
   * @param {object} settings - the Ajax settings. See more info here http://api.jquery.com/jQuery.ajax/
   * @param {boolean} isOneOff - determines whether the given settings will be used only one time.
   * @return {self}
   */
  Ajax.prototype.setup = function(settings, isOneOff)
  {
    if (isOneOff) 
    {
      options = settings;
      return this;
    }
    if (settings.global)
    {
      if (settings.ajaxStart) $(document).ajaxStart(settings.ajaxStart);
      if (settings.ajaxStop) $(document).ajaxStop(settings.ajaxStop);
      if (settings.ajaxComplete) $(document).ajaxComplete(settings.ajaxComplete);
      if (settings.ajaxError) $(document).ajaxError(settings.ajaxError);
      if (settings.ajaxSuccess) $(document).ajaxSuccess(settings.ajaxSuccess);
      if (settings.ajaxSend) $(document).ajaxSend(settings.ajaxSend);
    }
    $.ajaxSetup(settings);
    return this;
  };
  
  /**
   * Performs an Ajax request.
   *
   * @this {Ajax}
   * @param {string} delegate - the PHP delegate to call via Ajax request.
   * @param {mixed} param1, ..., paramN - parameters of the delegate.
   * @return {jqXHR}
   */
  Ajax.prototype.doit = function(delegate/*, param1, param2, ..., paramN */)
  {
    var data = {'ajax-method': delegate,
                'ajax-args': Array.prototype.slice.call(arguments, 1),
                'ajax-key': $(document.body).attr('id'), 
                'ajax-vs': this.pom.getVS()};
    var settings = {'data': data};
    if (options) 
    {
      settings = options;
      settings.data = data;
      options = null;
    }
    return $.ajax(settings);
  };
  
  /**
   * Performs an Ajax request with the preliminary validation of controls.
   * It returns FALSE if the request has been already sent and jqXHR otherwise.
   *
   * @this {Ajax}
   * @param {string} delegate - the PHP delegate to call via Ajax request.
   * @param {string} groups - the validation group(s).
   * @param {string} classInvalid - the CSS class for invalid controls.
   * @param {string} classValid - the CSS class for valid controls.
   * @param {mixed} param1, ..., paramN - parameters of the delegate.
   * @return {boolean|jqXHR}
   */
  Ajax.prototype.submit = function(delegate, groups, classInvalid, classValid/*, param1, param2, ..., paramN */)
  {
    if (!lock[delegate] && this.pom.validate(groups, classInvalid, classValid)) 
    {
      lock[delegate] = true;
      return this.doit.apply(this, $.merge([delegate], Array.prototype.slice.call(arguments, 4))).always(function()
      {
        delete lock[delegate];
      });
    }
    return false;
  };
  
  /**
   * Performs some action.
   *
   * @this {Ajax}
   * @param {mixed} param1, ..., paramN - parameters of the action.
   */
  Ajax.prototype.action = function(act/*, param1, param2, ..., paramN */)
  {
    var time, args = Array.prototype.slice.call(arguments, 1);
    act = act.toLowerCase();
    switch (act)
    {
      case 'reload':
        time = 0;
        break;
      case 'alert':
      case 'redirect':
      case 'focus':
      case 'remove':
      case 'script':
        time = 1;
        break;
      case 'display':
      case 'message':
      case 'inject':
        time = 3;
        break;
      default:
        time = 2;
        break;
    }
    time = typeof args[time] == 'undefined' ? 0 : args[time];
    var process = function()
    {
      switch (act)
      {
        case 'alert':
          alert(args[0]);
          break;
        case 'redirect':
          window.location.assign(args[0]);
          break;
        case 'reload':
          window.location.reload(true);
          break;
        case 'display':
          if (typeof args[1] == 'undefined') args[1] = $(args[0]).css('display') == 'none' ? '' : 'none';
          if (typeof args[2] == 'undefined' || args[2] == 0) $(args[0]).css('display', args[1]);
          else
          {
            var display = $(args[0]).css('display');
            $(args[0]).css('display', args[1]);
            setTimeout(function(){$(args[0]).css('display', display);}, args[2]);
          }
          break;
        case 'message':
          if (typeof args[2] == 'undefined' || args[2] == 0) $(args[0]).html(args[1]);
          else 
          {
            var html = $(args[0]).html();
            $(args[0]).html(args[1]);
            setTimeout(function(){$(args[0]).html(html);}, args[2]);
          }
          break;
        case 'focus':
          $(args[0]).focus();
          break;
        case 'addclass':
          $(args[0]).addClass(args[1]);
          break;
        case 'removeclass':
          $(args[0]).removeClass(args[1]);
          break;
        case 'toggleclass':
          $(args[0]).toggleClass(args[1]);
          break;
        case 'remove':
          $(args[0]).remove();
          break;
        case 'insert':
          $(args[0]).html(args[1]);
          break;
        case 'replace':
          $(args[0]).replaceWith(args[1]);
          break;
        case 'inject':
          switch (args[2].toLowerCase())
          {
            case 'top':
              $(args[0]).prepend(args[1]);
              break;
            case 'botom':
              $(args[0]).append(args[1]);
              break;
            case 'before':
              $(args[0]).before(args[1]);
              break;
            case 'after':
              $(args[0]).after(args[1]);
              break;
          }
          break;
        case 'script':
          $.globalEval(args[0]);
          break;
      }
    };
    time > 0 ? setTimeout(process, time) : process();
  };
  
  return Ajax;
})();

var $ajax = new Ajax($pom);