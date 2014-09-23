cfg.addModule('orm',
{
  'init': function()
  {
    var self = this;
    // Actions
    //---------------------------------------------
    // Refresh databases aliases and tables.
    $('#btnRefreshDBs').click(function()
    {
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'refresh', 'args': {'alias': $('#ormAliases').val()}}}).done(function(data)
      {
        if (cfg.hasError(data)) return;
        data = JSON.parse(data);
        $('#ormAliases').html(data['aliases']);
        $('#ormTables').html(data['tables']);
        cfg.showMsg('Database information has been successfully refreshed.');
      });
    });
    // Display tables of the selected database.
    $('#ormAliases').change(function()
    {
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'show', 'args': {'alias': $('#ormAliases').val()}}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        $('#ormTables').html(html);
      });
    });
    // Check all tables.
    $('#btnCheckTables').click(function()
    {
      $('#ormTables').find('input:checkbox').prop('checked', true);
    });
    // Uncheck all tables.
    $('#btnUncheckTables').click(function()
    {
      $('#ormTables').find('input:checkbox').prop('checked', false);
    });
    // Check tables according to the given RegExp.
    $('#btnCheckByRegExp').click(function()
    {
      var re = $('#ormRegExp').val();
      if (re == '') return;
      eval('re = ' + re);
      $('#ormTables').find('input:checkbox').each(function()
      {
        if (re.test($(this).val())) $(this).prop('checked', true);
      });
    });
    // Uncheck tables according to the given RegExp.
    $('#btnUncheckByRegExp').click(function()
    {
      var re = $('#ormRegExp').val();
      if (re == '') return;
      eval('re = ' + re);
      $('#ormTables').find('input:checkbox').each(function()
      {
        if (re.test($(this).val())) $(this).prop('checked', false);
      });
    });
    // Creates XML.
    $('#btnCreateXML').click(function()
    {
      var tmp, info = {'alias': $('#ormAliases').val(), 'tables': self.getExcludedTables(), 'mode': $('#ormMode').find('input:checked[name="ormMode"]').val()};
      if ((tmp = $('#ormBaseDirectory').val()) != '') info['dir'] = tmp;
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'xml', 'args': info}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        $('#shadow').hide();
        cfg.showMsg('XML has been successfully created.');
      });
    });
    // Creates models.
    $('#btnCreateORM').click(function()
    {
      var tmp, info = {'alias': $('#ormAliases').val(), 'tables': self.getExcludedTables(), 'mode': $('#ormMode').find('input:checked[name="ormMode"]').val()};
      if ((tmp = $('#ormModelNamespace').val()) != '') info['ns'] = tmp;
      if ((tmp = $('#ormBaseDirectory').val()) != '') info['dir'] = tmp;
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'model', 'args': info}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        $('#shadow').hide();
        cfg.showMsg('Model\'s classes have been successfully created.');
      });
    });
    // Creates AR.
    $('#btnCreateAR').click(function()
    {
      var tmp, info = {'alias': $('#ormAliases').val(), 'tables': self.getExcludedTables(), 'mode': $('#ormMode').find('input:checked[name="ormMode"]').val()};
      if ((tmp = $('#ormARNamespace').val()) != '') info['ns'] = tmp;
      if ((tmp = $('#ormBaseDirectory').val()) != '') info['dir'] = tmp;
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'ar', 'args': info}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        $('#shadow').hide();
        cfg.showMsg('Active Record\'s classes have been successfully created.');
      });
    });
  },
  
  'getExcludedTables': function()
  {
    var tmp = [];
    $('#ormTables').find('input:checkbox:not(:checked)').each(function()
    {
      tmp.push($(this).val());
    });
    return tmp;
  }
});