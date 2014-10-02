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
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'refresh', 'args': {'alias': $('#ormAliases').val()}}}).done(function(data)
      {
        $('#shadow').hide();
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
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'show', 'args': {'alias': $('#ormAliases').val()}}}).done(function(html)
      {
        $('#shadow').hide();
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
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'xml', 'args': self.getArgs('XML')}}).done(function(html)
      {
        $('#shadow').hide();
        if (cfg.hasError(html)) return;
        cfg.showMsg('XML has been successfully created.');
      });
    });
    // Creates models.
    $('#btnCreateORM').click(function()
    {
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'model', 'args': self.getArgs('ORM')}}).done(function(html)
      {
        $('#shadow').hide();
        if (cfg.hasError(html)) return;
        cfg.showMsg('Model\'s classes have been successfully created.');
      });
    });
    // Creates AR.
    $('#btnCreateAR').click(function()
    {
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'orm', 'command': 'ar', 'args': self.getArgs('AR')}}).done(function(html)
      {
        $('#shadow').hide();
        if (cfg.hasError(html)) return;
        cfg.showMsg('Active Record\'s classes have been successfully created.');
      });
    });
  },
  
  'getArgs': function(mode)
  {
    var tmp, args = {'alias': $('#ormAliases').val(), 
                     'tables': this.getExcludedTables(),
                     'mode': $('#ormMode').find('input:checked[name="ormMode"]').val(),
                     'useTransformation': $('#ormTransformation').prop('checked') ? 1 : 0,
                     'useInheritance': $('#ormInheritance').prop('checked') ? 1 : 0};
    if ((tmp = $('#ormBaseDirectory').val()) != '') args['dir'] = tmp;
    if (mode == 'AR') 
    {
      if ((tmp = $('#ormARNamespace').val()) != '') args['ns'] = tmp;
    }
    else if (mode == 'ORM')
    {
      if ((tmp = $('#ormModelNamespace').val()) != '') args['ns'] = tmp;
    }
    return args;
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