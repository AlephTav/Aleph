<?php
/**
 * Copyright (c) 2014 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Web\POM;

use Aleph\Core,
    Aleph\Utils;

class ImgEditor extends Popup
{
  /**
   * Error message templates.
   */
  const ERR_IMGEDITOR_1 = 'Control with ID "[{var}]" is not found.';
  const ERR_IMGEDITOR_2 = 'Control with ID "[{var}]" is not an upload control.';

  protected $ctrl = 'imgeditor';
  
  protected $extensions = ['png', 'jpg', 'jpeg', 'gif'];
  
  protected $options = ['scale' => 1,
                        'angle' => 1,
                        'cropEnabled' => 1,
                        'cropResizable' => 1,
                        'cropWidth' => 1,
                        'cropHeight' => 1,
                        'cropMinWidth' => 1,
                        'cropMinHeight' => 1,
                        'cropMaxWidth' => 1,
                        'cropMaxHeight' => 1];
  
  public function __construct($id, $template = null, $expire = 0)
  {
    parent::__construct($id, $template ?: \Aleph::dir('framework') . '/web/js/imgeditor/imgeditor.html', $expire);
    $this->attributes['class'] = 'imgeditor';
  }
  
  public function init()
  {
    $this->view->addCSS(['href' => \Aleph::url('framework') . '/web/js/imgeditor/imgeditor.css']);
    $this->view->addJS(['src' => \Aleph::url('framework') . '/web/js/imgeditor/raphael-min.js']);
    $this->tpl->id = $id = $this->attributes['id'];
    $this->get('rotate')->settings = ['start' => 0, 
                                      'connect' => 'lower', 
                                      'range' => ['min' => -180, 'max' => 180], 
                                      'serialization' => ['lower' => ['js::$.Link({target: $("#angle_' . $id . '")})']]];
    $this->get('zoom')->settings = ['start' => 100,
                                    'connect' => 'lower', 
                                    'range' => ['min' => 1, 'max' => 1000], 
                                    'serialization' => ['lower' => ['js::$.Link({target: $("#scale_' . $id . '")})']]];
    $this->attributes['closebuttons'] = '#' . $this->get('btnCancel')->attr('id') . ',#' . $this->get('btnApply')->attr('id') . ',#' . $this->get('btnUseOriginal')->attr('id');
    return $this;
  }
  
  public function getImageInfo($uniqueID)
  {
    if (isset($this->tpl->uploads[$uniqueID]))
    {
      $data = $this->tpl->uploads[$uniqueID];
      if (isset($data['info'])) return $data['info'];
    }
    return false;
  }
  
  public function removeImage($uniqueID)
  {
    $info = $this->getImageInfo($uniqueID);
    if ($info)
    {
      if (isset($info['path']) && is_file($info['path'])) unlink($info['path']);
      if (isset($info['originalPath']) && is_file($info['originalPath'])) unlink($info['originalPath']);
    }
    unset($this->tpl->uploads[$uniqueID]['info']);
  }
  
  public function setup(array $uploads)
  {
    $tmp = [];
    foreach ($uploads as $id => $data)
    {
      $ctrl = $this->view->get($id);
      if (!$ctrl) throw new Core\Exception($this, 'ERR_IMGEDITOR_1', $id);
      if (!($ctrl instanceof Upload)) throw new Core\Exception($this, 'ERR_IMGEDITOR_2', $id);
      $ctrl->multiple = false;
      $ctrl->settings['singleFileUploads'] = true;
      $ctrl->callback = stripslashes($this->callback('upload'));
      if (!isset($data['cropEnabled'])) $data['cropEnabled'] = true;
      $tmp[$ctrl->attr('id')] = $data;
    }
    $this->tpl->uploads = $tmp;
  }
  
  public function upload($uniqueID)
  {
    if (empty($this->tpl->uploads[$uniqueID])) return false;
    $data = $this->tpl->uploads[$uniqueID];
    $file = new Utils\UploadedFile($_FILES[$uniqueID]);
    $file->unique = true;
    $file->destination = isset($data['destination']) ? $data['destination'] : \Aleph::dir('temp');
    $file->extensions = isset($data['extensions']) ? $data['extensions'] : $this->extensions;
    $file->types = isset($data['types']) ? $data['types'] : [];
    $file->max = isset($data['maxSize']) ? $data['maxSize'] : null;
    if (false !== $info = $file->move())
    {
      $this->tpl->uploads[$uniqueID]['info'] = $info;
      $this->launch($uniqueID);
    }
    else
    {
      $error = $file->getErrorMessage();
      if (isset($data['onFail'])) \Aleph::delegate($data['onFail'], $uniqueID, $error);
      else $this->view->action('alert', 'Error! ' . $error);
      if (isset($data['onAlways'])) \Aleph::delegate($data['onAlways'], $uniqueID, $error);
    }
  }
  
  public function edit($url, $uniqueID, array $data = null)
  {
    $path = \Aleph::dir($url);
    $info = pathinfo($path);
    $info = ['path' => $path,
             'url' => $url,
             'name' => $info['basename'],
             'extension' => $info['extension'],
             'originalName' => $info['filename'],
             'size' => filesize($path),
             'type' => mime_content_type($path)];
    $data['info'] = $info;
    if (isset($this->tpl->uploads[$uniqueID])) $data = array_replace_recursive($this->tpl->uploads[$uniqueID], $data);
    $this->tpl->uploads[$uniqueID] = $data;
    $file = new Utils\UploadedFile($path, $info['name'], $info['type'], $info['size']);
    $file->extensions = isset($data['extensions']) ? $data['extensions'] : $this->extensions;
    $file->types = isset($data['types']) ? $data['types'] : [];
    $file->max = isset($data['maxSize']) ? $data['maxSize'] : null;
    if ($file->validate())
    {
      $this->launch($uniqueID);
    }
    else
    {
      $error = $file->getErrorMessage();
      if (isset($data['onFail'])) \Aleph::delegate($data['onFail'], $uniqueID, $error);
      else $this->view->action('alert', 'Error! ' . $error);
      if (isset($data['onAlways'])) \Aleph::delegate($data['onAlways'], $uniqueID, $error);
    }
  }
  
  public function launch($uniqueID)
  {
    if (empty($this->tpl->uploads[$uniqueID])) return false;
    $data = $this->tpl->uploads[$uniqueID];
    if (empty($data['info'])) return false;
    $info = $data['info'];
    $btn = $this->get('btnApply');
    if ($btn->visible) $btn->onclick = $this->method('apply', [$uniqueID, View::JS_MARK . '$pom.get(\'' . $this->attributes['id'] . '\').getTransformData()']);
    $btn = $this->get('btnUseOriginal');
    if ($btn->visible) $btn->onclick = $this->method('apply', [$uniqueID]);
    $ops = [];
    foreach ($data as $k => $v) if (isset($this->options[$k])) $ops[$k] = $v;
    $ops = Utils\PHP\Tools::php2js($ops, true, View::JS_MARK);
    $size = getimagesize($info['path']);
    $this->view->action('$pom.get(\'' . $this->attributes['id'] . '\').load(\'' . $info['url'] . '?' . rand(999, 999999) . '\', ' . (int)$size[0] . ', ' . (int)$size[1] . ', ' . $ops . ')');
  }
  
  public function apply($uniqueID, array $transformData = null)
  {
    if (empty($this->tpl->uploads[$uniqueID])) return false;
    $data = $this->tpl->uploads[$uniqueID];
    if (empty($data['info'])) return false;
    $info = $data['info'];
    if ($transformData)
    {
      $scale = $transformData['scale'];
      $angle = $transformData['angle'];
      $bgcolor = $transformData['bgcolor'] ? array_map('trim', explode(',', trim($transformData['bgcolor'], 'rgba()'))) : [0, 0, 0, 1];
      $bgcolor = Utils\Picture::rgb2int($bgcolor[0], $bgcolor[1], $bgcolor[2], isset($bgcolor[3]) ? $bgcolor[3] : 1);
      $pic = new Utils\Picture($info['path']);
      if ($angle != 0) $pic->rotate($angle, $bgcolor);
      if ($scale != 1) $pic->resize($pic->getWidth() * $scale, $pic->getHeight() * $scale, Utils\Picture::PIC_MANUAL);
      if (!empty($data['cropEnabled'])) $pic->crop($transformData['cropLeft'], $transformData['cropTop'], $transformData['cropWidth'], $transformData['cropHeight'], $bgcolor, $transformData['isSmartCrop']);
      if (!empty($data['preserveOriginal']))
      {
        $info['originalPath'] = $info['path'] . '.orig';
        $info['originalURL'] = $info['url'] . '.orig';
        $this->tpl->uploads[$uniqueID]['info'] = $info;
        copy($info['path'], $info['originalPath']);
      }
      $pic->save();
    }
    if (isset($data['onSuccess'])) \Aleph::delegate($data['onSuccess'], $uniqueID, $info);
    if (isset($data['onAlways'])) \Aleph::delegate($data['onAlways'], $uniqueID, $info);
  }
}