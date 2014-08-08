<?php

$highlight = function($code)
{
  $map = array(T_COMMENT                     => 'syntax-comment',
               T_DOC_COMMENT                 => 'syntax-comment',
               T_ABSTRACT                    => 'syntax-keyword',
               T_AS                          => 'syntax-keyword',
               T_BREAK                       => 'syntax-keyword',
               T_CASE                        => 'syntax-keyword',
               T_CATCH                       => 'syntax-keyword',
               T_CLASS                       => 'syntax-keyword',
               T_CONST                       => 'syntax-keyword',
               T_CONTINUE                    => 'syntax-keyword',
               T_DECLARE                     => 'syntax-keyword',
               T_DEFAULT                     => 'syntax-keyword',
               T_DO                          => 'syntax-keyword',
               T_ELSE                        => 'syntax-keyword',
               T_ELSEIF                      => 'syntax-keyword',
               T_ENDDECLARE                  => 'syntax-keyword',
               T_ENDFOR                      => 'syntax-keyword',
               T_ENDFOREACH                  => 'syntax-keyword',
               T_ENDIF                       => 'syntax-keyword',
               T_ENDSWITCH                   => 'syntax-keyword',
               T_ENDWHILE                    => 'syntax-keyword',
               T_EXTENDS                     => 'syntax-keyword',
               T_FINAL                       => 'syntax-keyword',
               T_FOR                         => 'syntax-keyword',
               T_FOREACH                     => 'syntax-keyword',
               T_FUNCTION                    => 'syntax-keyword',
               T_GLOBAL                      => 'syntax-keyword',
               T_GOTO                        => 'syntax-keyword',
               T_IF                          => 'syntax-keyword',
               T_IMPLEMENTS                  => 'syntax-keyword',
               T_INSTANCEOF                  => 'syntax-keyword',
               T_INTERFACE                   => 'syntax-keyword',
               T_LOGICAL_AND                 => 'syntax-keyword',
               T_LOGICAL_OR                  => 'syntax-keyword',
               T_LOGICAL_XOR                 => 'syntax-keyword',
               T_NAMESPACE                   => 'syntax-keyword',
               T_NEW                         => 'syntax-keyword',
               T_PRIVATE                     => 'syntax-keyword',
               T_PUBLIC                      => 'syntax-keyword',
               T_PROTECTED                   => 'syntax-keyword',
               T_RETURN                      => 'syntax-keyword',
               T_STATIC                      => 'syntax-keyword',
               T_SWITCH                      => 'syntax-keyword',
               T_THROW                       => 'syntax-keyword',
               T_TRY                         => 'syntax-keyword',
               T_USE                         => 'syntax-keyword',
               T_VAR                         => 'syntax-keyword',
               T_WHILE                       => 'syntax-keyword',
               T_CLASS_C                     => 'syntax-literal',
               T_DIR                         => 'syntax-literal',
               T_FILE                        => 'syntax-literal',
               T_FUNC_C                      => 'syntax-literal',
               T_LINE                        => 'syntax-literal',
               T_METHOD_C                    => 'syntax-literal',
               T_NS_C                        => 'syntax-literal',
               T_DNUMBER                     => 'syntax-literal',
               T_LNUMBER                     => 'syntax-literal',
               T_CONSTANT_ENCAPSED_STRING    => 'syntax-string',
               T_VARIABLE                    => 'syntax-variable',
               T_STRING                      => 'syntax-function',
               T_ARRAY                       => 'syntax-function',
               T_CLONE                       => 'syntax-function',
               T_ECHO                        => 'syntax-function',
               T_EMPTY                       => 'syntax-function',
               T_EVAL                        => 'syntax-function',
               T_EXIT                        => 'syntax-function',
               T_HALT_COMPILER               => 'syntax-function',
               T_INCLUDE                     => 'syntax-function',
               T_INCLUDE_ONCE                => 'syntax-function',
               T_ISSET                       => 'syntax-function',
               T_LIST                        => 'syntax-function',
               T_REQUIRE_ONCE                => 'syntax-function',
               T_PRINT                       => 'syntax-function',
               T_REQUIRE                     => 'syntax-function',
               T_UNSET                       => 'syntax-function');
  $res = '';
  $tokens = @token_get_all('<?php ' . $code);
  unset($tokens[0]);
  foreach ($tokens as $token)
  {
    if (isset($map[$token[0]])) $res .= '<span class="' . $map[$token[0]] . '">' . htmlspecialchars($token[1]) . '</span>';
    else $res .= htmlspecialchars(is_array($token) ? $token[1] : $token);
  }
  return $res;
};

$convertToHTML = function($obj) use(&$convertToHTML)
{
  if (is_object($obj))
  {
    if ($obj instanceof \Closure) return '<span class="syntax-variable">$Closure</span>()';
    $class = get_class($obj);
    if (preg_match('/\b[A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*/', $class)) return '<span class="syntax-literal">$' . $class . '</span>';
    return '<span class="syntax-variable">$' . $class . '</span>';
  }
  if (is_array($obj))
  {
    if (count($obj) == 0) return '[]';
    $tmp = array(); 
    foreach ($obj as $k => $v) $tmp[] = htmlspecialchars($k) . ' => ' . $convertToHTML($v);
    return '[ ' . implode(', ', $tmp) . ' ]';
  }
  if (is_string($obj)) return '<span class=\'syntax-string\'>&quot;' . htmlspecialchars($obj) . '&quot;</span>';
  if ($obj === null) $obj = 'null';
  else if (is_bool($obj)) $obj = $obj ? 'true' : 'false';
  return '<span class=\'syntax-literal\'>' . $obj . '</span>';
};

$file2class = function($file)
{
  if ($file == '' || $file == '[Internal PHP]') return 'file-internal-php';
  $config = \Aleph::getInstance()->getConfig();
  if (isset($config['dirs']['application']) && strpos($file, $config['dirs']['application']) === 0) return 'file-app';
  if (isset($config['dirs']['framework']) && strpos($file, $config['dirs']['framework']) === 0) return 'file-ignore';
  if ($file[0] != DIRECTORY_SEPARATOR && is_file(\Aleph::getRoot() . DIRECTORY_SEPARATOR . $file)) return 'file-root';
  return 'file-common';
};

$maxLineLen = $maxFileLen = 0; $hli = -1;
foreach ($trace as $n => $item) 
{
  if (empty($item['line'])) continue;
  if ($maxLineLen < strlen($item['line'])) $maxLineLen = strlen($item['line']);
  if ($maxFileLen < strlen($item['file'])) $maxFileLen = strlen($item['file']);
  if ($hli == -1 && $item['line'] == $line) $hli = $n;
}

$files = '<div id="error-files">';
foreach ($trace as $n => $item)
{
  $files .= '<div id="file-line-' . $n . '" class="error-file-lines' . (($hli == $n || count($trace) == 1) ? ' show' : '') . '">';
  foreach (explode("\n", $highlight($item['code'])) as $k => $command)
  {
    $files .= '<div class="error-file-line' . ($k == $item['index'] ? ' highlight' : '') . '" style="padding-left:' . (8 * (strlen($command) - strlen(ltrim($command, ' ')))) . 'px;">';
    $files .= '<span class="error-file-line-content">' . ltrim($command, ' ') . '</span>';
    $files .= '</div>';
  }    
  $files .= '</div>';
}
$files .= '</div>';

$stack = '<table id="error-stack-trace">';
foreach ($trace as $n => $item)
{
  $stack .= '<tr data-file-lines-id="file-line-' . $n . '" class="error-stack-trace-line' . ($hli == $n ? ' highlight' : ($hli > $n ? ' pre-highlight' : '')) . ($item['file'] != '[Internal PHP]' ? '' : ' is-native') . '">';
  $stack .= '<td class="linenumber">' . str_pad(isset($item['line']) ? $item['line'] : '', $maxLineLen, ' ', STR_PAD_LEFT) . '</td>';
  $stack .= '<td class="filename ' . $file2class($item['file']) . '">' . str_pad($item['file'], $maxFileLen, ' ', STR_PAD_RIGHT) . '</td>';
  $stack .= '<td class="lineinfo">' . $highlight($item['command']) . '</td>';
  $stack .= '</tr>';
}
$stack .= '</table>';

$dump = '';
foreach (array(array('request', 'response'), array('GET', 'POST'), array('COOKIE', 'FILES'), array('SESSION', 'SERVER')) as $key)
{
  if ($info[$key[0]])
  {
    $dump .= '<div class="error_dump dump_left">';
    $dump .= '<h2 class="error_dump_header">' . ucfirst($key[0]) . '</h2>';
    foreach ($info[$key[0]] as $k => $v)
    {
      $dump .= '<div class="error_dump_key">' . htmlspecialchars($k) . '</div>';
      $dump .= '<div class="error_dump_mapping">=></div>';
      $dump .= '<div class="error_dump_value">' . $convertToHTML($v) . '</div>';
    }
    $dump .= '</div>';
  }
  if ($info[$key[1]])
  {
    $dump .= '<div class="error_dump dump_right">';
    $dump .= '<h2 class="error_dump_header">' . ucfirst($key[1]) . '</h2>';
    foreach ($info[$key[1]] as $k => $v)
    {
      $dump .= '<div class="error_dump_key">' . htmlspecialchars($k) . '</div>';
      $dump .= '<div class="error_dump_mapping">=></div>';
      $dump .= '<div class="error_dump_value">' . $convertToHTML($v) . '</div>';
    }
    $dump .= '</div>';
  }
}

$fileClass = $file2class($file);
$title = isset($SERVER['HTTP_HOST']) ? $SERVER['HTTP_HOST'] : '';
$title = $title . ($title ? ' | ' : '') . (isset($SERVER['DOCUMENT_ROOT']) ? $SERVER['DOCUMENT_ROOT'] : '');

return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8" /><title>Bug Report</title><script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<style type="text/css">
html,body{width:100%;height:100%;margin:0;padding:0;}
body{color:#f0f0f0;tab-size:4;}
a,.error-stack-trace-line{-webkit-transition:color 120ms linear, background 120ms linear;-moz-transition:color 120ms linear, background 120ms linear;-ms-transition:color 120ms linear, background 120ms linear;-o-transition:color 120ms linear, background 120ms linear;transition:color 120ms linear, background 120ms linear;}
a,a:visited,a:hover,a:active{color:#9ae;text-decoration:none;}
a:hover{color:#aff;}
h1,h2,.background{font:17px monaco, consolas, monospace;}
h1{font-size:32px;margin-bottom:0;}
h2{font-size:24px;margin-top:0;}
.background{width:100%;background:#111;-moz-box-sizing:border-box;box-sizing:border-box;position:relative;height:100%;overflow:auto;padding:18px 24px;}
html.ajax{background:transparent;}
html.ajax > body{background:rgba(0,0,0,0.3);-moz-box-sizing:border-box;box-sizing:border-box;padding:30px 48px;}
html.ajax > body > .background{border-radius:4px;box-shadow:5px 8px 18px rgba(0,0,0,0.4);height:auto;min-height:0;overflow:hidden;}
#ajax-info{display:none;position:relative;line-height:100%;white-space:nowrap;}
html.ajax #ajax-info{display:block;}
html.ajax #error-file-root{display:none;}
.ajax-button{margin-top:-3px;border-radius:3px;color:#bbb;padding:3px 12px;}
.ajax-button,.ajax-button:visited,.ajax-button:active,.ajax-button:hover{text-decoration:none;}
a.ajax-button:hover{color:#fff;}
#ajax-tab{float:left;margin-right:12px;background:#000;color:inherit;border:3px solid #333;margin-top:-6px;}
.ajax-buttons{position:absolute;right:0;top:0;}
#ajax-retry{float:right;background:#0E4973;margin-right:12px;}
#ajax-retry:hover{background:#0C70B7;}
#ajax-close{float:right;background:#622;}
#ajax-close:hover{background:#aa4040;}
#error-title{position:relative;white-space:pre-wrap;}
#error-wrap{right:0;top:0;position:absolute;width:100%;height:0;}
#error-back{font-size:240px;color:#211600;position:absolute;top:60px;right:-40px;-webkit-transform:rotate(24deg);-moz-transform:rotate(24deg);-ms-transform:rotate(24deg);-o-transform:rotate(24deg);transform:rotate(24deg);}
#error-file.has_code{position:relative;margin:24px 0 0 167px;}
#error-linenumber{position:absolute;text-align:right;right:101%;width:178px;}
#ajax-info,#error-file-root{color:#666;}
#error-file-root{position:relative;}
#error-files{line-height:0;font-size:0;position:relative;display:inline-block;width:100%;-moz-box-sizing:border-box;box-sizing:border-box;overflow:hidden;padding:3px 0 24px 166px;}
.error-file-lines{display:inline-block;opacity:0;float:left;clear:none;width:100%;margin-right:-100%;-webkit-transition:opacity 300ms;-moz-transition:opacity 300ms;-ms-transition:opacity 300ms;-o-transition:opacity 300ms;transition:opacity 300ms;}
.error-file-lines.show{height:auto;opacity:1;-webkit-transition:opacity 300ms margin 100ms linear 300ms;-moz-transition:opacity 300ms margin 100ms linear 300ms;-ms-transition:opacity 300ms margin 100ms linear 300ms;-o-transition:opacity 300ms margin 100ms linear 300ms;transition:opacity 300ms margin 100ms linear 300ms;margin:0;}
.error-file-line{line-height:21px;font-size:16px;color:#ddd;list-style-type:none;min-height:20px;padding-right:18px;padding-bottom:1px;border-radius:2px;-moz-box-sizing:border-box;box-sizing:border-box;display:inline-block;float:left;clear:both;position:relative;}
.error-file-line-number{position:absolute;top:0;right:100%;margin-right:12px;display:block;text-indent:0;text-align:left;}
#error-stack-trace,.error-stack-trace-line{border-spacing:0;width:100%;}
#error-stack-trace{position:relative;line-height:28px;cursor:pointer;}
.error-stack-trace-exception{color:#b33;}
.error-stack-trace-exception > td{padding-top:18px;}
.error-stack-trace-line{float:left;}
.error-stack-trace-line.is-exception{margin-top:18px;border-top:1px solid #422;}
.error-stack-trace-line:first-of-type > td:first-of-type{border-top-left-radius:2px;}
.error-stack-trace-line:first-of-type > td:last-of-type{border-top-right-radius:2px;}
.error-stack-trace-line:last-of-type > td:first-of-type{border-bottom-left-radius:2px;}
.error-stack-trace-line:last-of-type > td:last-of-type{border-bottom-right-radius:2px;}
.error-stack-trace-line > td{vertical-align:top;padding:3px 0;}
.error-stack-trace-line > .linenumber,.error-stack-trace-line > .filename,.error-stack-trace-line > .file-internal-php,.error-stack-trace-line > .lineinfo{padding-left:18px;padding-right:12px;}
.error-stack-trace-line > .linenumber,.error-stack-trace-line > .file-internal-php,.error-stack-trace-line > .filename{white-space:pre;}
.error-stack-trace-line > .linenumber{text-align:right;}
.error-stack-trace-line > .lineinfo{padding-right:18px;padding-left:82px;text-indent:-64px;}
.error-dumps{position:relative;margin-top:48px;padding-top:32px;width:100%;max-width:100%;overflow:hidden;}
.error_dump{float:left;clear:none;-moz-box-sizing:border-box;box-sizing:border-box;max-width:100%;padding:0 32px 24px 12px;}
.error_dump.dump_left{clear:left;max-width:50%;min-width:50%;}
.error_dump.dump_right{max-width:50%;min-width:50%;}
.error_dump_header{font-size:24px;color:#eb4;margin:0 0 0 -6px;}
.error_dump_key,.error_dump_mapping,.error_dump_value{white-space:pre;float:left;padding:3px 6px;}
.error_dump_key{clear:left;}
.error_dump_mapping{padding:3px 12px;}
.error_dump_value{clear:right;white-space:normal;max-width:100%;}
.is-native,.pre-highlight{opacity:0.3;color:#999;}
.is-native{opacity:0.3!important;}
.highlight,.pre-highlight.highlight,.highlight ~ .pre-highlight{color:#eee;opacity:1;}
.select-highlight{background:#261313;}
.select-highlight.is-native{background:#222;}
.highlight{background:#391414;}
.highlight.select-highlight{background:#451915;}
.pre-highlight span,.pre-highlight:not(.highlight):first-of-type span{color:#999;border:none!important;}
.pre-highlight:first-of-type .syntax-function,.highlight ~ .pre-highlight .syntax-function,.pre-highlight.highlight .syntax-function,.syntax-function{color:#F9EE98;}
.pre-highlight:first-of-type .syntax-literal,.highlight ~ .pre-highlight .syntax-literal,.pre-highlight.highlight .syntax-literal,.syntax-literal{color:#cF5d33;}
.pre-highlight:first-of-type .syntax-string,.highlight ~ .pre-highlight .syntax-string,.pre-highlight.highlight .syntax-string,.syntax-string{color:#7C9D5D;}
.pre-highlight:first-of-type .syntax-variable-not-important,.highlight ~ .pre-highlight .syntax-variable-not-important,.pre-highlight.highlight .syntax-variable-not-important,.syntax-variable-not-important{opacity:0.5;}
.pre-highlight:first-of-type .syntax-higlight-variable,.highlight ~ .pre-highlight .syntax-higlight-variable,.pre-highlight.highlight .syntax-higlight-variable,.syntax-higlight-variable{color:red;border-bottom:3px dashed #c33;}
.pre-highlight:first-of-type .syntax-variable,.highlight ~ .pre-highlight .syntax-variable,.pre-highlight.highlight .syntax-variable,.syntax-variable{color:#798aA0;}
.pre-highlight:first-of-type .syntax-comment,.highlight ~ .pre-highlight .syntax-comment,.pre-highlight.highlight .syntax-comment,.syntax-comment{color:#5a5a5a;}
.file-internal-php{color:#555!important;}
.pre-highlight:first-of-type .file-common,.highlight ~ .pre-highlight .file-common,.pre-highlight.highlight .file-common,.file-common{color:#eb4;}
.pre-highlight:first-of-type .file-ignore,.highlight ~ .pre-highlight .file-ignore,.pre-highlight.highlight .file-ignore,.file-ignore{color:#585;}
.pre-highlight:first-of-type .file-app,.highlight ~ .pre-highlight .file-app,.pre-highlight.highlight .file-app,.file-app{color:#66c6d5;}
.pre-highlight:first-of-type .file-root,.highlight ~ .pre-highlight .file-root,.pre-highlight.highlight .file-root,.file-root{color:#b69;}
::-moz-selection,::selection{background:#662039!important;color:#fff!important;text-shadow:none;}
.pre-highlight:first-of-type .syntax-class,.highlight ~ .pre-highlight .syntax-class,.pre-highlight.highlight .syntax-class,.syntax-class,.pre-highlight:first-of-type .syntax-keyword,.highlight ~ .pre-highlight .syntax-keyword,.pre-highlight.highlight .syntax-keyword,.syntax-keyword{color:#C07041;}
</style>
</head>
<body>
  <div class="background">
    <h2 id="error-file-root">$title</h2>
    <h1 id="error-title">$message</h1>
    <h2 id="error-file" class="has_code">
      <span id="error-linenumber">$line</span>
      <span id="error-filename" class="$fileClass">$file</span>
    </h2>
    $files
    $stack
    <div class="error-dumps">
      <div class="error_dump dump_left">
        <h2 class="error_dump_header">Memory Usage</h2>
        <div class="error_dump_value"><span class="syntax-literal">$memoryUsage</span> Mb</div>
      </div>
      <div class="error_dump dump_right">
        <h2 class="error_dump_header">Execution Time</h2>
        <div class="error_dump_value"><span class="syntax-literal">$executionTime</span> sec</div>
      </div>
      $dump
    </div>
  </div>
<script type="text/javascript">
$(document).ready(function()
{
  if ($('#error-files').size() > 0 && $('#error-stack-trace').size() > 0)
  {
    var FADE_SPEED = 150, lines = $('#error-files .error-file-lines'), currentID = '#' + lines.filter('.show').attr('id');
    var filename   = $('#error-filename'), linenumber = $('#error-linenumber');
    $('.error-stack-trace-line').mouseover(function()
    {
      $(this).toggleClass('select-highlight');
    }).mouseout(function(ev)
    {
      $(this).removeClass('select-highlight');
    }).click(function()
    {
      var bind = $(this);
      if (!bind.hasClass('highlight') && !bind.hasClass('is-native'))
      {
        $('.error-stack-trace-line.highlight' ).removeClass('highlight');
        bind.addClass('highlight');
        var lineID = bind.data( 'file-lines-id' );
        if (lineID) 
        {
          var newCurrent = '#' + lineID;
          if (newCurrent !== currentID) 
          {
            currentID = newCurrent;
            lines.removeClass('show');
            lines.filter(currentID).addClass('show');
            var fln = bind.find('.filename');
            var file = fln.text(), line = bind.find('.linenumber').text();
            filename.text(file);
            filename.attr('class', fln.attr('class'));
            linenumber.text(line);
          }
        }
      }
    });
    $('#error-stack-trace').mouseleave(function()
    {
      lines.filter('.show').removeClass('show');
      lines.filter(currentID).addClass('show');
    });
  }
});
</script>
</body>
</html>
HTML;

?>