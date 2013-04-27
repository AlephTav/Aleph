$(function()
{
  // Handlers
  $('input[name="cacheGroup"]').click(function()
  {
    $('#otherGroup').attr('disabled', 'disabled');
  });
  $('#groupOther').click(function()
  {
    $('#otherGroup').removeAttr('disabled').focus();
  });
  $('#cacheType').change(function()
  {
    selectCacheType($(this).val());
  });
  // Actions
  $('#btnGC').click(function()
  {
    $.ajax({'type': 'POST', 'data': {'method': 'cache.gc'}}).success(function()
    {
      showMsg('Garbage Collector has been successfully run.');
    });
  });
  $('#btnClean').click(function()
  {
    var group = $('input:checked[name="cacheGroup"]').val();
    $.ajax({'type': 'POST', 'data': {'method': 'cache.clean', 'custom': group == 'other' ? 1 : 0, 'group': group == 'other' ? $('#otherGroup').val() : group}}).success(function()
    {
      showMsg('Cache has been successfully cleaned.');
    });
  });
  // Preparing
  selectCacheType($('#cacheType').val());
  var servers = $('#memServers').val();
  if (servers != '' && typeof(JSON) != 'undefined')
  {
    $('#memServers').val(JSON.stringify(JSON.parse(servers), null, 4));
  }
});

var thMsg;
function showMsg(msg)
{
  if (thMsg) clearTimeout(thMsg);
  $('#msg').html(msg);
  $('#ppMsg').css({'left': 0, 'top': 0});
  $('#ppMsg').css({'left': $(window).width() - $('#ppMsg').width() - 20, 'top': 0});
  $('#ppMsg').fadeIn();
  thMsg = setTimeout(function(){$('#ppMsg').fadeOut();}, 3000);
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