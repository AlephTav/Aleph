cfg.addModule('log',
{
  'init': function()
  {
    var self = this;
    // Actions
    //---------------------------------------------
    // Refresh logs
    $('#btnLogShow').click(function()
    {
      $.ajax({'type': 'POST', 'data': {'module': 'log', 'command': 'show'}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        $('#logList').html(html);
        $('#logDetails').html('');
        cfg.showMsg('Log has been successfully refreshed.');
        $('.log-dirs').click(self.loadLogFiles);
      });
    });
    // Delete logs
    $('#btnLogClean').click(function()
    {
      cfg.showDialog('Confirmation', 'Are you sure you want to remove all logs?', function()
      {
        $.ajax({'type': 'POST', 'data': {'module': 'log', 'command': 'clean'}}).done(function(html)
        {
          if (cfg.hasError(html)) return;
          $('#logList').html(html);
          cfg.showMsg('Log has been successfully cleaned.');
          cfg.hideDialog();
        });
      });
    });
    // Preparing.
    //---------------------------------------------
    // Loads log files
    $('.log-dirs').click(this.loadLogFiles);
  },
  
  'loadLogFiles': function()
  {
    var el = $(this), dir = el.text(), list = el.parent().find('ul');
    if (list.length > 0)
    {
      list.toggle();
      return;
    }
    $.ajax({'type': 'POST', 'data': {'module': 'log', 'command': 'files', 'args': {'dir': dir}}}).done(function(html)
    {
      if (el.parent().find('ul').length > 0) return;
      el.parent().append(html).find('ul > li').click(function()
      {
        var file = $(this).text();
        $.ajax({'type': 'POST', 'data': {'module': 'log', 'command': 'details', 'args': {'dir': dir, 'file': file}}}).done(function(html)
        {
          $('#logDetails').html(html);
          cfg.normalizeJSON('.log-details textarea');
          SyntaxHighlighter.highlight();
        });
      });
    });
  }
});