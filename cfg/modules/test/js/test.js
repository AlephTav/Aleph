cfg.addModule('test',
{
  'init': function()
  {
    var self = this;
    // Preparing.
    //---------------------------------------------
    $('#btnRunTests').click(function(){self.updateTests(true);});
    $('#tabTests').click(function(){self.updateTests(false);});
    $('#tests').load(function()
    {
      if ($('#tests').attr('src'))
      {
        $(this).show();
        $('#testLoader').hide();
      }
    });
  },
  
  'updateTests': function(update)
  {
    if ($('#tabTests').css('display') != 'none')
    {
      if (update || !update && !$('#tests').attr('src'))
      {
        $('#testLoader').show();
        $('#tests').hide().attr('src', '?test');
      }
    }
  }
});