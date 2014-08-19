var ImgEditor = function(el, pom)
{
  ImgEditor.superclass.constructor.call(this, el, pom);
  
  var id = el.attr('id'), ops, paper, image;
  var defaults = {scale: 100,
                  angle: 0,
                  cropEnabled: true,
                  cropResizable: false,
                  cropWidth: 50,
                  cropHeight: 50,
                  cropMinWidth: 10,
                  cropMinHeight: 10,
                  cropMaxWidth: 0,
                  cropMaxHeight: 0};
  
  paper = new Raphael('canvas_' + id);
  
  var resize = function()
  {
    if (!ops) return;
    var canvas = $('#canvas_' + id), crop = $('#crop_' + id);
    var canvasWidth = canvas.width(), canvasHeight = canvas.height();
    if (crop.width() > canvasWidth)
    {
      ops.cropWidth = canvasWidth;
      crop.width(canvasWidth);
    }
    if (crop.height() > canvasHeight)
    {
      ops.cropHeight = canvasHeight;
      crop.height(canvasHeight);
    }
    var offset = crop.position(), right = offset.left + ops.cropWidth, bottom = offset.top + ops.cropHeight;
    if (right > canvasWidth) offset.left = canvasWidth - ops.cropWidth;
    if (bottom > canvasHeight) offset.top = canvasHeight - ops.cropHeight;
    draw(offset.left, offset.top);
  };
  
  var changeAngle = function()
  {
    transform(image.attrs.x, image.attrs.y, $(this).val(), ops.scale);
  };
  
  var changeScale = function()
  {
    transform(image.attrs.x, image.attrs.y, ops.angle, $(this).val());
  };
  
  var transform = function(x, y, angle, scale)
  {
    image.transform('');
    image.attr({'x': x, 'y': y});
    ops.angle = angle;
    image.rotate(angle);
    ops.scale = scale;
    scale /= 100;
    image.scale(scale, scale);
  };
  
  var draw = function(cropLeft, cropTop)
  {
    $('#crop_' + id).css({left: cropLeft, top: cropTop, width: ops.cropWidth, height: ops.cropHeight});
    $('#cropLineLeft_' + id).css({left: cropLeft, top: cropTop, width: 1, height: ops.cropHeight});
    $('#cropLineRight_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop, width: 1, height: ops.cropHeight});
    $('#cropLineTop_' + id).css({left: cropLeft, top: cropTop, width: ops.cropWidth, height: 1});
    $('#cropLineBottom_' + id).css({left: cropLeft, top: cropTop + ops.cropHeight, width: ops.cropWidth, height: 1});
    $('#shadowTop_' + id).css({left: 0, top: 0, right: 0, height: cropTop});
    $('#shadowBottom_' + id).css({left: 0, top: cropTop + ops.cropHeight, right: 0, bottom: 0});
    $('#shadowLeft_' + id).css({left: 0, top: cropTop, width: cropLeft, height: ops.cropHeight});
    $('#shadowRight_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop, right: 0, height: ops.cropHeight});
    if (ops.cropResizable)
    {
      $('#cropSnapNW_' + id).css({left: cropLeft, top: cropTop});
      $('#cropSnapN_' + id).css({left: cropLeft + ops.cropWidth / 2, top: cropTop});
      $('#cropSnapNE_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop});
      $('#cropSnapW_' + id).css({left: cropLeft, top: cropTop + ops.cropHeight / 2});
      $('#cropSnapE_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop + ops.cropHeight / 2});
      $('#cropSnapSW_' + id).css({left: cropLeft, top: cropTop + ops.cropHeight});
      $('#cropSnapS_' + id).css({left: cropLeft + ops.cropWidth / 2, top: cropTop + ops.cropHeight});
      $('#cropSnapSE_' + id).css({left: cropLeft + ops.cropWidth, top: cropTop + ops.cropHeight});
    }
    $('#width_' + id).text(ops.cropWidth);
    $('#height_' + id).text(ops.cropHeight);
  };
  
  var crop = function()
  {
    var crop = $('#crop_' + id), canvas = $('#canvas_' + id);
    if (ops.cropEnabled)
    {
      crop.off().on('mousedown', function(e)
      {
        var canvasWidth = canvas.width(), canvasHeight = canvas.height();
        var offset = $(this).position(), cropmove = {left: e.pageX - offset.left, top: e.pageY - offset.top};
        $(document).on('mousemove', function(e)
        {
          var left = e.pageX - cropmove.left, top = e.pageY - cropmove.top;
          if (left < 0) left = 0;
          if (top < 0) top = 0;
          if (left > canvasWidth - ops.cropWidth - 1) left = canvasWidth - ops.cropWidth - 1;
          if (top > canvasHeight - ops.cropHeight - 1) top = canvasHeight - ops.cropHeight - 1;
          draw(left, top);
          e.preventDefault();
        }).on('mouseup', function(e)
        {
          $(this).off('mousemove');
        });
        e.preventDefault();
        e.stopPropagation();
      });
      if (ops.cropResizable)
      {
        $('.crop-snap', '#' + id).off().show().on('mousedown', function(e)
        {
          var canvasWidth = canvas.width(), canvasHeight = canvas.height();
          var snap = $(this), offset = snap.position(), snapmove = {left: e.pageX - offset.left, top: e.pageY - offset.top};
          var sid = snap.attr('id').substr(8, snap.attr('id').length - id.length - 9);
          var cropMinWidth = ops.cropMinWidth, cropMinHeight = ops.cropMinHeight;
          var cropMaxWidth = (ops.cropMaxWidth < 1 || ops.cropMaxWidth > canvasWidth) ? canvasWidth - 1: ops.cropMaxWidth;
          var cropMaxHeight = (ops.cropMaxHeight < 1 || ops.cropMaxHeight > canvasHeight) ? canvasHeight - 1: ops.cropMaxHeight;
          $(document).on('mousemove', function(e)
          {
            var x = e.pageX - snapmove.left, y = e.pageY - snapmove.top;
            var offset = crop.position(), crp = {left: offset.left, top: offset.top, width: crop.width(), height: crop.height()};
            crp.right = crp.left + crp.width;
            crp.bottom = crp.top + crp.height;
            switch (sid)
            {
              case 'N':
                if (crp.bottom - y > cropMaxHeight) y = crp.bottom - cropMaxHeight;
                if (crp.bottom - y < cropMinHeight) y = crp.bottom - cropMinHeight;
                ops.cropHeight = crp.bottom - y;
                crp.top = y;
                break;
              case 'S':
                if (y - crp.top > cropMaxHeight) y = crp.top + cropMaxHeight;
                if (y - crp.top < cropMinHeight) y = crp.top + cropMinHeight;
                ops.cropHeight = y - crp.top;
                break;
              case 'W':
                if (crp.right - x > cropMaxWidth) x = crp.right - cropMaxWidth;
                if (crp.right - x < cropMinWidth) x = crp.right - cropMinWidth;
                ops.cropWidth = crp.right - x;
                crp.left = x;
                break;
              case 'E':
                if (x - crp.left > cropMaxWidth) x = crp.left + cropMaxWidth;
                if (x - crp.left < cropMinWidth) x = crp.left + cropMinWidth;
                ops.cropWidth = x - crp.left;
                break;
              case 'NW':
                if (crp.bottom - y > cropMaxHeight) y = crp.bottom - cropMaxHeight;
                if (crp.bottom - y < cropMinHeight) y = crp.bottom - cropMinHeight;
                if (crp.right - x > cropMaxWidth) x = crp.right - cropMaxWidth;
                if (crp.right - x < cropMinWidth) x = crp.right - cropMinWidth;
                ops.cropHeight = crp.bottom - y;
                ops.cropWidth = crp.right - x;
                crp.top = y;
                crp.left = x;
                break;
              case 'NE':
                if (crp.bottom - y > cropMaxHeight) y = crp.bottom - cropMaxHeight;
                if (crp.bottom - y < cropMinHeight) y = crp.bottom - cropMinHeight;
                if (x - crp.left > cropMaxWidth) x = crp.left + cropMaxWidth;
                if (x - crp.left < cropMinWidth) x = crp.left + cropMinWidth;
                ops.cropHeight = crp.bottom - y;
                ops.cropWidth = x - crp.left;
                crp.top = y;
                break;
              case 'SW':
                if (y - crp.top > cropMaxHeight) y = crp.top + cropMaxHeight;
                if (y - crp.top < cropMinHeight) y = crp.top + cropMinHeight;
                if (crp.right - x > cropMaxWidth) x = crp.right - cropMaxWidth;
                if (crp.right - x < cropMinWidth) x = crp.right - cropMinWidth;
                ops.cropHeight = y - crp.top;
                ops.cropWidth = crp.right - x;
                crp.left = x;
                break;
              case 'SE':
                if (y - crp.top > cropMaxHeight) y = crp.top + cropMaxHeight;
                if (y - crp.top < cropMinHeight) y = crp.top + cropMinHeight;
                if (x - crp.left > cropMaxWidth) x = crp.left + cropMaxWidth;
                if (x - crp.left < cropMinWidth) x = crp.left + cropMinWidth;
                ops.cropHeight = y - crp.top;
                ops.cropWidth = x - crp.left;
                break;
            }
            if (crp.left < 0) crp.left = 0;
            if (crp.top < 0) crp.top = 0;
            if (crp.left > canvasWidth - ops.cropWidth - 1) crp.left = canvasWidth - ops.cropWidth - 1;
            if (crp.top > canvasHeight - ops.cropHeight - 1) crp.top = canvasHeight - ops.cropHeight - 1;
            draw(crp.left, crp.top);
            e.preventDefault();
          }).on('mouseup', function(e)
          {
            $(this).off('mousemove');
          });
          e.preventDefault();
          e.stopPropagation();
        });
      }
      crop.show();
      $('.shadow, .crop-line', '#' + id).show();
      draw((canvas.width() - ops.cropWidth) / 2, (canvas.height() - ops.cropHeight) / 2);
    }
    else
    {
      crop.off().hide();
      $('.shadow, .crop-line, .crop-snap', '#' + id).off().hide();
    }
    canvas.off().on('mousedown', function(e)
    {
      var cursor = $(document.body).css('cursor');
      $(document.body).css('cursor', 'move');
      var left = e.pageX - image.attrs.x, top = e.pageY - image.attrs.y;
      $(document).on('mousemove', function(e)
      {
        transform(e.pageX - left, e.pageY - top, ops.angle, ops.scale);
        e.preventDefault();
      }).on('mouseup', function()
      {
        $(this).off('mousemove');
        $(document.body).css('cursor', cursor);
      });
      e.preventDefault();
      e.stopPropagation();
    });
  };
  
  $(window).resize(resize);
  
  this.load = function(url, width, height, options)
  {
    ops = $.extend({}, defaults, options);
    paper.clear();
    this.show(true);
    paper.setSize('100%', '100%');
    image = paper.image(url, 0, 0, width, height);
    var canvas = $('#canvas_' + id), canvasWidth = canvas.width(), canvasHeight = canvas.height();
    if (ops.cropEnabled)
    {
      if (ops.cropMinWidth < 1) ops.cropMinWidth = 1;
      if (ops.cropMinHeight < 1) ops.cropMinHeight = 1;
      if (ops.cropWidth < 1) ops.cropWidth = 1;
      if (ops.cropHeight < 1) ops.cropHeight = 1;
      if (ops.cropWidth > canvasWidth) ops.cropWidth = canvasWidth;
      if (ops.cropHeight > canvasHeight) ops.cropHeight = canvasHeight;
    }
    else
    {
      $('#width_' + id).text(width);
      $('#height_' + id).text(height);
    }
    transform((canvasWidth - image.attrs.width) / 2, (canvasHeight - image.attrs.height) / 2, ops.angle, ops.scale);
    this.get('rotate').el.off().on('slide', changeAngle).on('set', changeAngle).val(ops.angle);
    this.get('zoom').el.off().on('slide', changeScale).on('set', changeScale).val(ops.scale);
    crop();
    return this;
  };
  
  this.getTransformData = function()
  {
    if (!image) return [];
    var data = {};
    if (ops.cropEnabled)
    {
      var box = image.getBBox(), crop = $('#crop_' + id), offset = crop.position();
      data.cropLeft = offset.left - box.x;
      data.cropTop = offset.top - box.y;
      data.cropWidth = crop.width();
      data.cropHeight = crop.height();
    }
    data.angle = -ops.angle;
    data.scale = ops.scale / 100;
    data.bgcolor = this.get('bgcolor').value();
    data.isSmartCrop = this.get('smartCrop').el.prop('checked') ? 1 : 0;
    return data;
  };
  
  this.remove = function()
  {
    $(window).off('resize', resize);
    this.hide();
    Popup.superclass.remove.call(this);
    return this;
  };
};

$pom.registerControl('imgeditor', ImgEditor, Popup);