<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Web\POM;

use Aleph\Core,
    Aleph\Utils;

/**
 * Use this control when you need simple functionality for uploading and editing images.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * tag - determines HTML tag of the container element.
 * expire - determines the cache lifetime (in seconds) of the render process. The default value is 0 (no cache).
 *
 * The special control attributes:
 * overlay - if this attribute is defined, the popup will have the overlay.
 * overlayClass - the CSS class of the popup overlay.
 * overlaySelector - the popup overlay selector.
 * closeByEscape - determines whether the popup should be closed when the escape button is pressed.
 * closeByDocument - determines whether the popup should be closed when the document is clicked.
 * closeButtons - the selector for buttons that should close the popup when they are clicked.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class ImgEditor extends Popup
{
  /**
   * Error message templates.
   */
  const ERR_IMGEDITOR_1 = 'Control with ID "%s" is not found.';
  const ERR_IMGEDITOR_2 = 'Control with ID "%s" is not an upload control.';

  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'imgeditor';
  
  /**
   * Allowed file extensions.
   *
   * @var array $extensions
   * @access protected
   */
  protected $extensions = ['png', 'jpg', 'jpeg', 'gif'];
  
  /**
   * Configuration options of the image editor on the client side.
   *
   * @var array $options
   * @access protected
   */
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
  
  /**
   * Constructor. Initializes the panel template object.
   *
   * @param string $id - the logic identifier of the image editor.
   * @param string $template - the image editor's template or the full path to the template file.
   * @param integer $expire - the cache lifetime of the panel template.
   * @access public
   */
  public function __construct($id, $template = null, $expire = 0)
  {
    parent::__construct($id, $template ?: \Aleph::dir('framework') . '/web/js/imgeditor/imgeditor.html', $expire);
    $this->attributes['class'] = 'imgeditor';
  }
  
  /**
   * Initializes the control.
   *
   * @return self
   * @access public
   */
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
    $this->attributes['closebuttons'] = '#' . $this->get('btnCancel')->attr('id');
    return $this;
  }
  
  /**
   * Returns information about uploaded image.
   * It returns TRUE on success and FALSE on failure.
   *
   * @param string $uniqueID - the unique identifier of the uploaded image.
   * @return boolean
   * @access public
   */
  public function getImageInfo($uniqueID)
  {
    if (isset($this->tpl->uploads[$uniqueID]))
    {
      $data = $this->tpl->uploads[$uniqueID];
      if (isset($data['info'])) return $data['info'];
    }
    return false;
  }
  
  /**
   * Removes uploaded image.
   *
   * @param string $uniqueID - the unique identifier of the uploaded image.
   * @return self
   * @access public
   */
  public function removeImage($uniqueID)
  {
    $info = $this->getImageInfo($uniqueID);
    if ($info)
    {
      if (isset($info['path']) && is_file($info['path'])) unlink($info['path']);
      if (isset($info['originalPath']) && is_file($info['originalPath'])) unlink($info['originalPath']);
    }
    unset($this->tpl->uploads[$uniqueID]['info']);
    return $this;
  }
  
  /**
   * Binds the given upload controls with the image editor.
   * For each upload control can be set own unique set of options. Available options are:
   * cropEnabled - determines whether or not the crop operation is used.
   * cropResizable - determines whether the crop is resizable.
   * cropWidth - the crop width.
   * cropHeight - the crop height.
   * cropMinWidth - the minimal crop width.
   * cropMinHeight - the minimal crop height.
   * cropMaxWidth - the maximal crop width.
   * cropMaxHeight - the maximal crop height.
   * destination - the path to the directory for storing edited image.
   * extensions - the array of permitted file extensions.
   * types - the array of permitted mime types of the image.
   * maxSize - the maximum size of the image.
   * preserveOriginal - determines whether the original image should be saved.
   * onSuccess - the delegate to call when the given image is successfully edited.
   * onFail - the delegate to call when the editing of the given image is failed.
   * onAlways - the delegate that always called after the image editing, no matter whether the editing has been successful or not.
   *
   * @param array $uploads - the image editor options for each upload control.
   * @return self
   * @access public
   */
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
    return $this;
  }
  
  /**
   * Validates the uploaded image and launches the image editor.
   *
   * @param string $uniqueID - the unique identifier of the uploaded image.
   * @return self
   * @access public
   */
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
    return $this;
  }
  
  /**
   * Launches the image editor to edit the given image.
   *
   * @param string $url - the image URL.
   * @param string $uniqueID - the unique identifier of the image.
   * @param array $data - the additional options of editing.
   * @return self   
   * @access public
   */
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
    return $this;
  }
  
  /**
   * Launches the image editor to edit the previously uploaded image.
   *
   * @param string $uniqueID - the unique identifier of the uploaded image.
   * @return self   
   * @access public
   */
  public function launch($uniqueID)
  {
    if (empty($this->tpl->uploads[$uniqueID])) return false;
    $data = $this->tpl->uploads[$uniqueID];
    if (empty($data['info'])) return false;
    $info = $data['info'];
    $ops = ['callback' => stripslashes($this->callback('apply')), 'UID' => $uniqueID];
    foreach ($data as $k => $v) if (isset($this->options[$k])) $ops[$k] = $v;
    $ops = Utils\PHP\Tools::php2js($ops, false, View::JS_MARK);
    $size = getimagesize($info['path']);
    $this->view->action('$pom.get(\'' . $this->attributes['id'] . '\').load(\'' . $info['url'] . '?' . rand(999, 999999) . '\', ' . (int)$size[0] . ', ' . (int)$size[1] . ', ' . $ops . ')', 100);
    return $this;
  }
  
  /**
   * Applies all changes that set up on the client side to the image.
   *
   * @param string $uniqueID - the unique identifier of the image.
   * @return self
   * @access public
   */
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
    return $this;
  }
}