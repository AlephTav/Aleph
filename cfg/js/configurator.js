$(function()
{
  // Handlers
  //--------------------------------------------
  $('input[name="cacheGroup"]').click(function()
  {
    $('#otherGroup').attr('disabled', 'disabled');
  });
  $('#groupOther').click(function()
  {
    $('#otherGroup').removeAttr('disabled').focus();
  });
  $('body').on('change', '#cacheType', function()
  {
    selectCacheType($(this).val());
  });
  $('h2').click(function()
  {
    $(this).next().toggle();
  });
  $('#divTests').click(function()
  {
    if ($('#tests').attr('src') == undefined) $('#tests').attr('src', '?tests');
  });
  // Actions
  //---------------------------------------------
  // Garbage Collector
  $('#btnGC').click(function()
  {
    $.ajax({'type': 'POST', 'data': {'method': 'cache.gc'}}).done(function()
    {
      showMsg('Garbage Collector has been successfully run.');
    });
  });
  // Cache cleanig
  $('#btnClean').click(function()
  {
    var group = $('input:checked[name="cacheGroup"]').val();
    $.ajax({'type': 'POST', 'data': {'method': 'cache.clean', 'custom': group == 'other' ? 1 : 0, 'group': group == 'other' ? $('#otherGroup').val() : group}}).done(function()
    {
      showMsg('Cache has been successfully cleaned.');
    });
  });
  // Select config file
  $('#config').change(function()
  {
    $.ajax({'type': 'POST', 'data': {'method': 'config.file', 'file': $(this).val()}}).done(function(html)
    {
      $('#configDetails').html(html);
      normalizeJSON('.json');
    });
  });
  // Default config settings restoring
  $('body').on('click', '#btnDefault', function()
  {
    showDialog('Confirmation', 'Are you sure you want to restore the default configuration settings?', function()
    {
      hideDialog(true);
      $.ajax({'type': 'POST', 'data': {'method': 'config.restore', 'file': $('#config').val()}}).done(function(html)
      {
        $('#configDetails').html(html);
        showMsg('Default settings have been successfully restored.');
        $('#shadow').hide();
        normalizeJSON('.json');
      });
    });
  });
  // Config saving
  $('body').on('click', '#btnSave', function()
  {
    var tmp, cfg = {'debugging': $('#debugOn').attr('checked') == 'checked' ? 1 : 0, 
                    'logging': $('#logOn').attr('checked') == 'checked' ? 1 : 0};
    if ((tmp = $('#tplDebug').val()) != '') cfg['templateDebug'] = tmp;
    if ((tmp = $('#tplBug').val()) != '') cfg['templateBug'] = tmp;
    if ((tmp = $('#customDebugMethod').val()) != '') cfg['customDebugMethod'] = tmp;
    if ((tmp = $('#customLogMethod').val()) != '') cfg['customLogMethod'] = tmp;
    cfg['cache'] = {'type': $('#cacheType').val()};
    if (cfg.cache.type == 'file')
    {
      cfg.cache.directory = $('#cacheDirectory').val();
      cfg.cache.gcProbability = $('#gcProbability').val();
    }
    else if (cfg.cache.type == 'memory')
    {
      cfg.cache.servers = $('#memServers').val();
      cfg.cache.compress = $('#compressNo').attr('checked') == 'checked' ? false : true;
    }
    var flag = true, first;
    $('.alias').each(function()
    {
      var index = $(this).attr('id').substr(8), alias = $('#alias' + index), dir = $('#dir' + index);
      if (alias.val() == '' || dir.val() == '')
      {
        flag = false;
        $(this).addClass('ym-error');
        if (!first) first = alias.val() == '' ? alias : dir;
        return;
      }
      $(this).removeClass('ym-error');
      if (typeof cfg['dirs'] == 'undefined') cfg['dirs'] = {};
      cfg.dirs[alias.val()] = dir.val();
    });
    $('.prop').each(function()
    {
      var index = $(this).attr('id').substr(7), prop = $('#prop' + index), value = $('#value' + index);
      if (prop.val() == '')
      {
        flag = false;
        $(this).addClass('ym-error');
        if (!first) first = prop;
        return;
      }
      $(this).removeClass('ym-error');
      if (typeof cfg['custom'] == 'undefined') cfg['custom'] = {};
      cfg.custom[prop.val()] = value.val();
    });
    if (!flag)
    {
      first.focus();
      return;
    }
    $('#shadow').show();
    $.ajax({'type': 'POST', 'data': {'method': 'config.save', 'file': $('#config').val(), 'config': cfg}}).done(function()
    {
      showMsg('Settings have been successfully saved.');
      $('#shadow').hide();
    });
  });
  // New directory alias adding 
  var nAliases = $('.alias').length;
  $('body').on('click', '#btnAddNewAlias', function()
  {
    var alias = $('#newAlias').val(), dir = $('#newDir').val();
    if (alias == '' || dir == '') 
    {
      $('#divNewAlias').addClass('ym-error');
      $('#' + (alias == '' ? 'newAlias' : 'newDir')).focus();
      return;
    }
    $('#divNewAlias').removeClass('ym-error');
    var html = '';
    html += '<div id="divAlias' + nAliases + '" class="ym-fbox-text alias">';
    html += '<label class="ym-label" for="alias' + nAliases + '">Alias<sup class="ym-required">*</sup></label>';
    html += '<input type="text" id="alias' + nAliases + '" name="alias' + nAliases + '" />';
    html += '<label class="ym-label" for="dir' + nAliases + '">Directory<sup class="ym-required">*</sup></label>';
    html += '<input type="text" id="dir' + nAliases + '" name="dir' + nAliases + '" />';
    html += '<a id="btnDeleteAlias' + nAliases + '" class="ym-button ym-delete" style="float:right;" title="Delete directory alias">Delete</a>';
    html += '</div>';
    $('#aliases').prepend(html);
    $('#alias' + nAliases).val(alias);
    $('#dir' + nAliases).val(dir);
    $('#btnDeleteAlias' + nAliases).click(deleteAlias);
    $('#newDir').val('');
    $('#newAlias').val('')
    nAliases++;
  });
  $('body').on('click', '.alias > .ym-delete', deleteAlias);
  // New config custom property adding
  var nProps = $('.prop').length;
  $('body').on('click', '#btnAddNewProp', function()
  {
    var prop = $('#newProp').val(), value = $('#newValue').val();
    if (prop == '' || prop == 'debugging' || prop == 'logging' || prop == 'templateDebug' || prop == 'templateBug' || prop == 'cache' || prop == 'dirs') 
    {
      if (prop != '')
      {
        showMsg('You cannot add property "' + prop + '" because this property is reserved.', true);
      }
      $('#divNewProp').addClass('ym-error');
      $('#newProp').focus();
      return;
    }
    $('#divNewProp').removeClass('ym-error');
    var html = '';
    html += '<div id="divProp' + nProps + '" class="ym-fbox-text prop">';
    html += '<label class="ym-label" for="prop' + nProps + '">Name<sup class="ym-required">*</sup></label>';
    html += '<input type="text" id="prop' + nProps + '" name="prop' + nProps + '" />';
    html += '<label class="ym-label" for="value' + nProps + '">Value<sup class="ym-required">*</sup></label>';
    html += '<textarea id="value' + nProps + '" name="value' + nProps + '" rows="7"></textarea>';
    html += '<a id="btnDeleteProp' + nProps + '" class="ym-button ym-delete" style="float:right;" title="Delete custom property">Delete</a>';
    html += '</div>';
    $('#props').prepend(html);
    $('#prop' + nProps).val(prop);
    $('#value' + nProps).val(value);
    $('#btnDeleteProp' + nProps).click(deleteProperty);
    $('#newProp').val('');
    $('#newValue').val('')
    nProps++;
  });
  $('body').on('click', '.prop > .ym-delete', deleteProperty);
  // Refresh logs
  $('#btnLogRefresh').click(function()
  {
    $.ajax({'type': 'POST', 'data': {'method': 'log.refresh'}}).done(function(html)
    {
      $('#logList').html(html);
      $('#logDetails').html('');
      showMsg('Log has been successfully refreshed.');
      $('.log-dirs').click(loadLogFiles);
    });
  });
  // Delete logs
  $('#btnLogClean').click(function()
  {
    showDialog('Confirmation', 'Are you sure you want to remove all logs?', function()
    {
      $.ajax({'type': 'POST', 'data': {'method': 'log.clean'}}).done(function(html)
      {
        $('#logList').html(html);
        showMsg('Log has been successfully cleaned.');
        hideDialog();
      });
    });
  });
  // Loads log files
  $('.log-dirs').click(loadLogFiles);
  // Preparing
  //---------------------------------------------
  selectCacheType($('#cacheType').val());
  // Normalizing of json data in textareas.
  normalizeJSON('.json');
});

var thMsg;
function showMsg(msg, isError)
{
  if (thMsg) clearTimeout(thMsg);
  $('#msg').html(msg);
  if (isError) $('#msg').addClass('error').removeClass('success');
  else $('#msg').removeClass('error').addClass('success');
  $('#ppMsg').css({'left': 0, 'top': 0});
  $('#ppMsg').css({'left': $(window).width() - $('#ppMsg').width() - 20, 'top': 0});
  $('#ppMsg').fadeIn();
  thMsg = setTimeout(function(){$('#ppMsg').fadeOut();}, 3000);
}

function showDialog(subject, question, action)
{
  $('#shadow').show();
  $('#subject').html(subject);
  $('#question').html(question);
  $('#ppDialog').css({'zIndex': 100, 'left': ($(window).width() - $('#ppDialog').width()) / 2, 'top': ($(window).height() - $('#ppDialog').height()) / 2}).fadeIn();
  $('#btnYes').off('click').click(action);
}

function hideDialog(leftShadow)
{
  if (!leftShadow) $('#shadow').hide();
  $('#ppDialog').hide();
}

function normalizeJSON(selector)
{
  if (typeof(JSON) != 'undefined')
  {
    $(selector).val(function(index, val)
    {
      if (val == '') return val;
      return JSON.stringify(JSON.parse(val), null, 4);
    });
  }
}

function selectCacheType(type)
{
  switch (type)
  {
    case 'file':
      $('.cache-file').show();
      $('.cache-memory').hide();
      break;
    case 'memory':
      $('.cache-file').hide();
      $('.cache-memory').show();
      break;
    default:
      $('.cache-file').hide();
      $('.cache-memory').hide();
      break;
  }
}

function deleteAlias()
{
  var i = $(this).attr('id').substr(14), alias = $('#alias' + i).val();
  showDialog('Confirmation', 'Are you sure you want to delete directory alias' + (alias ? ' "' + alias + '"?' : '?'), function()
  {
    $('#divAlias' + i).remove();
    hideDialog();
  });
}

function deleteProperty()
{
  var i = $(this).attr('id').substr(13), prop = $('#prop' + i).val();
  showDialog('Confirmation', 'Are you sure you want to delete custom property' + (prop ? ' "' + prop + '"?' : '?'), function()
  {
    $('#divProp' + i).remove();
    hideDialog();
  });
}

function loadLogFiles()
{
  var el = $(this), dir = el.text(), list = el.parent().find('ul');
  if (list.length > 0)
  {
    list.toggle();
    return;
  }
  $.ajax({'type': 'POST', 'data': {'method': 'log.files', 'dir': dir}}).done(function(html)
  {
    if (el.parent().find('ul').length > 0) return;
    el.parent().append(html).find('ul > li').click(function()
    {
      var file = $(this).text();
      $.ajax({'type': 'POST', 'data': {'method': 'log.details', 'dir': dir, 'file': file}}).done(function(html)
      {
        $('#logDetails').html(html);
        normalizeJSON('.log-details textarea');
        SyntaxHighlighter.highlight();
      });
    });
  });
}