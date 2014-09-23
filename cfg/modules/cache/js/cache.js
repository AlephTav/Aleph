cfg.addModule('cache',
{
  'init': function()
  {
    var self = this;
    // Actions
    //---------------------------------------------
    // Garbage Collector
    $('#btnGC').click(function()
    {
      $.ajax({'type': 'POST', 'data': {'module': 'cache', 'command': 'gc'}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        cfg.showMsg('Garbage Collector has been successfully run.');
      });
    });
    // Cache cleanig
    $('#btnClean').click(function()
    {
      var section = $('input:checked[name="cacheGroup"]').val();
      var tmp = {'section': section};
      if (section == 'other') tmp['group'] = $('#otherGroup').val();
      $.ajax({'type': 'POST', 'data': {'module': 'cache', 'command': 'clean', 'args': tmp}}).done(function(html)
      {
        if (cfg.hasError(html)) return;
        cfg.showMsg('Cache has been successfully cleaned.');
      });
    });
    // Preparing.
    //---------------------------------------------
    $('input[name="cacheGroup"]').click(function()
    {
      $('#otherGroup').attr('disabled', 'disabled');
    });
    $('#groupOther').click(function()
    {
      $('#otherGroup').removeAttr('disabled').focus();
    });
  }
});