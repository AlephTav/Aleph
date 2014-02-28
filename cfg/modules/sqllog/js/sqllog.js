cfg.addModule('sqllog',
{
  'init': function()
  {
    // Preparing
    //--------------------------------------------
    $('input[name="sqlSearchWhere"]').click(function()
    {
      if ($('#sqlSearchModeRegExp').attr('checked') == 'checked') return;
      var val = $(this).attr('value');
      if (val == -1 || val >=4 && val <= 7)
      {
        $('#sqlFrom').removeAttr('disabled');
        $('#sqlTo').removeAttr('disabled');
        $('#sqlSearchOptRange').removeAttr('disabled');
      }
      else
      {
        $('#sqlFrom').attr('disabled', 'disabled');
        $('#sqlTo').attr('disabled', 'disabled');
        $('#sqlSearchOptRange').attr('disabled', 'disabled');
        $('#sqlSearchOptRange').removeAttr('checked', 'checked');
      }
    });
    $('input[name="sqlSearchMode"]').click(function()
    {
      if ($(this).val() == 'regexp')
      {
        $('#sqlFrom').attr('disabled', 'disabled');
        $('#sqlTo').attr('disabled', 'disabled');
        $('#sqlSearchOptRange').removeAttr('checked', 'checked');
        $('#sqlSearchOptRange').attr('disabled', 'disabled');
        $('#sqlSearchOptWord').attr('disabled', 'disabled');
        $('#sqlSearchOptCaseSensitive').attr('disabled', 'disabled');
      }
      else
      {
        var val = $('input:checked[name="sqlSearchWhere"]').val();
        $('#sqlSearchOptWord').removeAttr('disabled');
        $('#sqlSearchOptCaseSensitive').removeAttr('disabled');
        if (val == -1 || val >=4 && val <= 7)
        {
          $('#sqlFrom').removeAttr('disabled');
          $('#sqlTo').removeAttr('disabled');
          $('#sqlSearchOptRange').removeAttr('disabled');
        } 
      }
    });
    $('#sqlFrom').change(function()
    {
      if ($(this).val().length > 0) $('#sqlSearchOptRange').attr('checked', 'checked');
    });
    $('#sqlTo').change(function()
    {
      if ($(this).val().length > 0) $('#sqlSearchOptRange').attr('checked', 'checked');
    });
    // Handlers
    //---------------------------------------------
    // Search SQL log.
    $('#btnSearchSQL').click(function()
    {
      var opts = {'where': $('input:checked[name="sqlSearchWhere"]').val(),
                  'mode': $('input:checked[name="sqlSearchMode"]').val(),
                  'onlyWholeWord': $('#sqlSearchOptWord').attr('checked') == 'checked' ? 1 : 0,
                  'caseSensitive': $('#sqlSearchOptCaseSensitive').attr('checked') == 'checked' ? 1 : 0,
                  'from': $('#sqlSearchOptRange').attr('checked') == 'checked' ? $('#sqlFrom').val() : '',
                  'to': $('#sqlSearchOptRange').attr('checked') == 'checked' ? $('#sqlTo').val() : ''};
      $('#shadow').show();
      $.ajax({'type': 'POST', 'data': {'module': 'sqllog', 'command': 'search', 'args': {'keyword': $('#sqlLogKeyword').val(), 'options': opts}}}).done(function(html)
      {
        $('#sqlSearchResults').html(html);
        $('#shadow').hide();
      }); 
    });
    // Clean SQL log.
    $('#btnCleanSQL').click(function()
    {
      cfg.showDialog('Confirmation', 'Are you sure you want to clean SQL log?', function()
      {
        $.ajax({'type': 'POST', 'data': {'module': 'sqllog', 'command': 'clean'}}).done(function(html)
        {
          if (cfg.hasError(html)) return;
          cfg.showMsg('SQL log has been successfully cleaned.');
          $('#sqlSearchResults').html('');
          cfg.hideDialog();
        });
      });
    });
  }
});