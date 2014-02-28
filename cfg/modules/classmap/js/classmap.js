cfg.addModule('classmap',
{
  'init': function()
  {
    var self = this;
    // Handlers.
    //---------------------------------------------
    // Create the classmap.
    $('#btnClassmapCreate').click(function()
    {
      cfg.showDialog('Confirmation', 'Are you sure you want to create according to the settings the new classmap?', function()
      {
        $('#ppDialog').hide();
        $.ajax({'type': 'POST', 'data': {'module': 'classmap', 'command': 'create'}}).done(function(html)
        {
          if (cfg.hasError(html)) return;
          $('#classmap').html(html);
          cfg.showMsg('Classmap has been successfully created.');
          $('#shadow').hide();
        });
      });
    });
    // Create the classmap.
    $('#btnClassmapClean').click(function()
    {
      cfg.showDialog('Confirmation', 'Are you sure you want to clean the classmap?', function()
      {
        $.ajax({'type': 'POST', 'data': {'module': 'classmap', 'command': 'clean'}}).done(function(html)
        {
          if (cfg.hasError(html)) return;
          $('#classmap').html(html);
          cfg.showMsg('Classmap has been successfully cleaned.');
          cfg.hideDialog();
        });
      });
    });
  }
});