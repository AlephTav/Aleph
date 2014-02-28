var Configurator = (function(undefined)
{
  var thMsg, modules = {};
  
  var Configurator = function(){};
  
  Configurator.prototype.addModule = function(name, module)
  {
    modules[name] = module;
  };
  
  Configurator.prototype.normalizeJSON = function(selector)
  {
    if (typeof(JSON) != 'undefined')
    {
      $(selector).val(function(index, val)
      {
        if (val == '') return val;
        return JSON.stringify(JSON.parse(val), null, 4);
      });
    }
  };
  
  Configurator.prototype.showMsg = function(msg, isError)
  {
    if (thMsg) clearTimeout(thMsg);
    $('#msg').html(msg);
    if (isError) $('#msg').addClass('error').removeClass('success');
    else $('#msg').removeClass('error').addClass('success');
    $('#ppMsg').css({'left': 0, 'top': 0});
    $('#ppMsg').css({'left': $(window).width() - $('#ppMsg').width() - 20, 'top': 0});
    $('#ppMsg').fadeIn();
    thMsg = setTimeout(function(){$('#ppMsg').fadeOut();}, isError ? 10000 : 3000);
  };

  Configurator.prototype.showDialog = function(subject, question, action)
  {
    $('#shadow').show();
    $('#subject').html(subject);
    $('#question').html(question);
    $('#ppDialog').css({'zIndex': 100, 'left': ($(window).width() - $('#ppDialog').width()) / 2, 'top': ($(window).height() - $('#ppDialog').height()) / 2}).fadeIn();
    $('#btnYes').off('click').click(action);
  };

  Configurator.prototype.hideDialog = function(leftShadow)
  {
    if (!leftShadow) $('#shadow').hide();
    $('#ppDialog').hide();
  };

  Configurator.prototype.hasError = function(html)
  { 
    if (html.match(/(Fatal|Parse) error:.* in .*/))
    {
      this.hideDialog();
      this.showMsg(html.replace(/\n/g, '<br />'), true);
      return true;
    }
    return false;
  };
  
  Configurator.prototype.init = function()
  {
    for (var i in modules) modules[i].init();
    $('h2').click(function(){$(this).next().toggle();});
  };

  return Configurator;
})();

var cfg = new Configurator();