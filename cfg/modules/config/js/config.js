cfg.addModule('config',
{
  'init': function()
  {
    var self = this;
    // Handlers.
    //---------------------------------------------
    // Select config file
    $('#config').change(function()
    {
      $.ajax({'type': 'POST', 'data': {'module': 'config', 'command': 'show', 'args': {'file': $(this).val()}}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        $('#configDetails').html(html);
        cfg.normalizeJSON('.json');
      });
    });
    // Config saving
    $('body').on('click', '#btnSave', function()
    {
      var tmp, info = {'debugging': $('#debugOn').prop('checked') ? 1 : 0, 
                       'logging': $('#logOn').prop('checked') ? 1 : 0};
      if ((tmp = $('#tplDebug').val()) != '') info['templateDebug'] = tmp;
      if ((tmp = $('#tplBug').val()) != '') info['templateBug'] = tmp;
      if ((tmp = $('#customDebugMethod').val()) != '') info['customDebugMethod'] = tmp;
      if ((tmp = $('#customLogMethod').val()) != '') info['customLogMethod'] = tmp;
      info['cache'] = {'type': $('#cacheType').val()};
      if (info.cache.type == 'file')
      {
        info.cache.directory = $('#cacheDirectory').val();
        info.cache.gcProbability = $('#gcProbability').val();
      }
      else if (info.cache.type == 'memory')
      {
        info.cache.servers = $('#memServers').val();
        info.cache.compress = $('#compressNo').prop('checked') ? false : true;
      }
      else if (info.cache.type == 'redis' || info.cache.type == 'phpredis')
      {
        info.cache.host = $('#redisHost').val();
        info.cache.port = $('#redisPort').val();
        info.cache.timeout = $('#redisTimeout').val();
        info.cache.database = $('#redisDatabase').val();
        info.cache.password = $('#redisPassword').val();
      }
      info['autoload'] = {'search': $('#alSearchYes').prop('checked') ? 1 : 0, 'unique': $('#alUniqueYes').prop('checked') ? 1 : 0};
      if ((tmp = $('#alType').val()) != '') info['autoload']['type'] = tmp;
      if ((tmp = $('#alClassMap').val()) != '') info['autoload']['classmap'] = tmp;
      if ((tmp = $('#alMask').val()) != '') info['autoload']['mask'] = tmp;
      if ((tmp = $('#alCallback').val()) != '') info['autoload']['callback'] = tmp;
      if ((tmp = $('#alTimeout').val()) != '') info['autoload']['timeout'] = tmp;
      if ((tmp = $('#alNamespaces').val()) != '') info['autoload']['namespaces'] = tmp;
      if ((tmp = $('#alDirectories').val()) != '') info['autoload']['directories'] = tmp;
      if ((tmp = $('#alExclusions').val()) != '') info['autoload']['exclusions'] = tmp;
      info['mvc'] = {'locked': $('#appLocked').prop('checked') ? 1 : 0};
      if ((tmp = $('#unlockKey').val()) != '') info['mvc']['unlockKey'] = tmp;
      if ((tmp = $('#unlockKeyExpire').val()) != '') info['mvc']['unlockKeyExpire'] = tmp;
      if ((tmp = $('#templateLock').val()) != '') info['mvc']['templateLock'] = tmp;
      info['pom'] = {'cacheEnabled': $('#pomCacheEnabled').prop('checked') ? 1 : 0};
      if ((tmp = $('#pomCacheGroup').val()) != '') info['pom']['cacheGroup'] = tmp;
      if ((tmp = $('#pomCharset').val()) != '') info['pom']['charset'] = tmp;
      if ((tmp = $('#pomNamespaces').val()) != '') info['pom']['namespaces'] = tmp;
      if ((tmp = $('#pomPPOpenTag').val()) != '') info['pom']['ppOpenTag'] = tmp;
      if ((tmp = $('#pomPPCloseTag').val()) != '') info['pom']['ppCloseTag'] = tmp;
      info['db'] = {'logging': $('#dbLogOn').prop('checked') ? 1 : 0};
      if ((tmp = $('#dbLogFile').val()) != '') info['db']['log'] = tmp;
      if ((tmp = $('#dbCacheExpire').val()) != '') info['db']['cacheExpire'] = tmp;
      if ((tmp = $('#dbCacheGroup').val()) != '') info['db']['cacheGroup'] = tmp;
      info['ar'] = {};
      if ((tmp = $('#arCacheExpire').val()) != '') info['ar']['cacheExpire'] = tmp;
      if ((tmp = $('#arCacheGroup').val()) != '') info['ar']['cacheGroup'] = tmp;
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
        if (typeof info['dirs'] == 'undefined') info['dirs'] = {};
        info.dirs[alias.val()] = dir.val();
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
        if (typeof info['custom'] == 'undefined') info['custom'] = {};
        info.custom[prop.val()] = value.val();
      });
      if (!flag)
      {
        first.focus();
        return;
      }
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'config', 'command': 'save', 'args': {'file': $('#config').val(), 'config': info}}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        cfg.showMsg('Settings have been successfully saved.');
        $('#shadow').hide();
      });
    });
    // Default config settings restoring
    $('body').on('click', '#btnDefault', function()
    {
      cfg.showDialog('Confirmation', 'Are you sure you want to restore the default configuration settings?', function()
      {
        cfg.hideDialog(true);
        $.ajax({'type': 'POST', 'data': {'module': 'config', 'command': 'restore', 'args': {'file': $('#config').val()}}}).done(function(html)
        {
          if (cfg.hasError(html)) return;
          $('#configDetails').html(html);
          cfg.showMsg('Default settings have been successfully restored.');
          $('#shadow').hide();
          cfg.normalizeJSON('.json');
        });
      });
    });
    // Preparing.
    //---------------------------------------------
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
      $('#btnDeleteAlias' + nAliases).click(self.deleteAlias);
      $('#newDir').val('');
      $('#newAlias').val('');
      nAliases++;
    });
    $('body').on('click', '.alias > .ym-delete', this.deleteAlias);
    // New config custom property adding
    var nProps = $('.prop').length;
    $('body').on('click', '#btnAddNewProp', function()
    {
      var prop = $('#newProp').val(), value = $('#newValue').val();
      if (prop == '' || prop == 'debugging' || prop == 'logging' || prop == 'templateDebug' || prop == 'templateBug' || prop == 'customDebugMethod' || prop == 'customLogMethod' || prop == 'cache' || prop == 'dirs' || prop == 'autoload' || prop == 'ar')
      {
        if (prop != '')
        {
          cfg.showMsg('You cannot add property "' + prop + '" because this property is reserved.', true);
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
      $('#btnDeleteProp' + nProps).click(self.deleteProperty);
      $('#newProp').val('');
      $('#newValue').val('');
      cfg.normalizeJSON('.json');
      nProps++;
    });
    $('body').on('click', '.prop > .ym-delete', this.deleteProperty);
    // Selecting cache type.
    $('body').on('change', '#cacheType', function(){self.selectCacheType($(this).val());});
    this.selectCacheType($('#cacheType').val());
    // Selecting autoload type.
    $('body').on('change', '#alType', function()
    {
      if ($(this).val() == 'classmap')
      {
        $('.autoload-psr').hide();
        $('.autoload-classmap').show();
      }
      else
      {
        $('.autoload-classmap').hide();
        $('.autoload-psr').show();
      }
    });
    // Normalization of the JSON data.
    cfg.normalizeJSON('.json');
  },

  'selectCacheType': function(type)
  {
    switch (type)
    {
      case 'file':
        $('.cache-memory').hide();
        $('.cache-redis').hide();
        $('.cache-file').show();
        break;
      case 'memory':
        $('.cache-file').hide();
        $('.cache-redis').hide();
        $('.cache-memory').show();
        break;
      case 'redis':
      case 'phpredis':
        $('.cache-file').hide();
        $('.cache-memory').hide();
        $('.cache-redis').show();
        break;
      default:
        $('.cache-file').hide();
        $('.cache-memory').hide();
        $('.cache-redis').hide();
        break;
    }
  },
  
  'deleteAlias': function()
  {
    var i = $(this).attr('id').substr(14), alias = $('#alias' + i).val();
    cfg.showDialog('Confirmation', 'Are you sure you want to delete directory alias' + (alias ? ' "' + alias + '"?' : '?'), function()
    {
      $('#divAlias' + i).remove();
      cfg.hideDialog();
    });
  },
  
  'deleteProperty': function()
  {
    var i = $(this).attr('id').substr(13), prop = $('#prop' + i).val();
    cfg.showDialog('Confirmation', 'Are you sure you want to delete custom property' + (prop ? ' "' + prop + '"?' : '?'), function()
    {
      $('#divProp' + i).remove();
      cfg.hideDialog();
    });
  }
});