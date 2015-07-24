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

namespace Aleph\Utils;

use Aleph\Core;

/**
 * The simple class that allows to manage asset dependencies and compound several asset files to the single one.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.utils
 */
class AssetManager
{
    /**
     * Conversion modes.
     */
    const MODE_DEFAULT = 0;
    const MODE_MINIFY = 1;
    const MODE_OBFUSCATE = 2;
    
    /**
     * Error message templates.
     */
    const ERR_ASSET_MANAGER_1 = 'Destination asset file "%s" is already defined.';
    const ERR_ASSET_MANAGER_2 = 'Destination asset file "%s" is not defined.';
    
    /**
     * Determines whether the development mode is enabled.
     * If the development mode is disabled no asset manipulations happen.
     *
     * @var boolean $devMode
     * @access public
     */
    public $devMode = true;
    
    /**
     * The path to the asset destination directory.
     *
     * @var string $basePath
     * @access public
     */
    public $basePath = null;
    
    /**
     * The base URL of the destination assets.
     *
     * @var string $baseURL
     * @access public
     */
    public $baseURL = null;

    /**
     * The path to the source asset directory.
     *
     * @var string $sourcePath
     * @access public
     */
    public $sourcePath = null;
    
    /**
     * Permissions for newly created asset destination directory.
     *
     * @var integer $directoryMode
     * @access public
     */
    public $directoryMode = 0711;
    
    /**
     * Permissions for newly created asset file.
     *
     * @var integer $fileMode
     * @access public
     */
    public $fileMode = 0644;
    
    /**
     * The asset dependencies:
     * [
     *     'lib1' => [
     *         'js' => [
     *             'path_to_js_file_1',
     *             'path_to_js_file_2',
     *             ...
     *             'path_to_js_file_N'
     *         ],
     *         'css' => [
     *             'path_to_css_file_1',
     *             'path_to_css_file_2',
     *             ...
     *             'path_to_css_file_N'
     *         ]
     *     ],
     *     'lib2' => [
     *         'js' => [
     *             'path_to_js_file_1',
     *             'path_to_js_file_2',
     *             ...
     *             'path_to_js_file_N'
     *         ],
     *         'css' => [
     *             'path_to_css_file_1',
     *             'path_to_css_file_2',
     *             ...
     *             'path_to_css_file_N'
     *         ]
     *     ],
     *     ...
     *     'libN' => [
     *         'js' => [
     *             'path_to_js_file_1',
     *             'path_to_js_file_2',
     *             ...
     *             'path_to_js_file_N'
     *         ],
     *         'css' => [
     *             'path_to_css_file_1',
     *             'path_to_css_file_2',
     *             ...
     *             'path_to_css_file_N'
     *         ],
     *         'depends' => [
     *             'lib1',
     *             'lib2', 
     *             ...
     *         ]
     *     ]
     * ]
     *
     * @var array $dependencies
     * @access public
     */
    public $dependencies = [];
   
    /**
     * The asset collection.
     *
     * @var array $assets
     * @access protected
     */
    protected $assets = [];
    
    /**
     * Asset converters.
     *
     * @var array $converters
     * @access protected
     */
    protected $converters = [
        self::MODE_MINIFY => [
            'css' => null,
            'js' => null
        ],
        self::MODE_OBFUSCATE => [
            'css' => null,
            'js' => null
        ]
    ];
    
    /**
     * Constructor. Sets values of class properties.
     *
     * @param array $options - the values of class properties.
     * @access public
     */
    public function __construct(array $options = null)
    {
        foreach ((array)$options as $opt => $val)
        {
            $this->{$opt} = $val;
        }
    }
    
    /**
     * Sets callback for CSS minification.
     *
     * @param mixed $callback - the minification method.
     * @access public
     */
    public function setCSSMinifier($callback)
    {
        $this->setConverter($callback, self::MODE_MINIFY, 'css');
    }
    
    /**
     * Sets callback for JS minification.
     *
     * @param mixed $callback - the minification method.
     * @access public
     */
    public function setJSMinifier($callback)
    {
        $this->setConverter($callback, self::MODE_MINIFY, 'js');
    }
    
    /**
     * Sets callback for CSS obfuscation.
     *
     * @param mixed $callback - the obfuscation method.
     * @access public
     */
    public function setCSSObfuscator($callback)
    {
        $this->setConverter($callback, self::MODE_OBFUSCATE, 'css');
    }
    
    /**
     * Sets callback for JS obfuscation.
     *
     * @param mixed $callback - the obfuscation method.
     * @access public
     */
    public function setJSObfuscator($callback)
    {
        $this->setConverter($callback, self::MODE_OBFUSCATE, 'js');
    }
    
    /**
     * Add source file(s) for CSS asset.
     *
     * @param string $dest - the asset filename.
     * @param array $src - the asset source files.
     * @param boolean $merge - determines whether the previously added asset sources should be merged with the new ones.
     * @param boolean $checkDuplicates - determines whether the exception should be thrown if asset duplicates are found.
     * @access public
     */
    public function css($dest, $src, $merge = true, $checkDuplicates = true)
    {
        $this->add($dest, (array)$src, $merge, $checkDuplicates, 'css');
    }
    
    /**
     * Add source file(s) for JS asset.
     *
     * @param string $dest - the asset filename.
     * @param array $src - the asset source files.
     * @param boolean $merge - determines whether the previously added asset sources should be merged with the new ones.
     * @param boolean $checkDuplicates - determines whether the exception should be thrown if asset duplicates are found.
     * @access public
     */
    public function js($dest, $src, $merge = true, $checkDuplicates = true)
    {
		$this->add($dest, (array)$src, $merge, $checkDuplicates, 'js');
    }
    
    /**
     * Creates single asset file.
     * The method returns URL of the generated asset. 
     *
     * @param string $dest - the asset filename.
     * @param integer $mode - the asset conversion mode. The default mode means that conversion is not applied.
     * @return string
     * @access public
     */
    public function asset($dest, $mode = self::MODE_DEFAULT)
    {
        if (!empty($this->devMode))
        {
            if (empty($this->assets[$dest]))
            {
                throw new Core\Exception([$this, 'ERR_ASSET_MANAGER_2'], $dest);
            }
            $asset = empty($this->basePath) ? $dest : rtrim($this->basePath, '\\/') . DIRECTORY_SEPARATOR . $dest;
            $srcPath = empty($this->sourcePath) ? '' : rtrim($this->sourcePath, '\\/') . DIRECTORY_SEPARATOR;
            $flag = file_exists($asset);
            if ($flag)
            {
                $time = filemtime($asset);
                foreach ($this->assets[$dest][0] as $src)
                {
                    foreach (glob($srcPath . $src) as $file)
                    {
                        if (filemtime($file) > $time)
                        {
                            $flag = false;
                            break 2;
                        }
                    }
                }
            }
            if (!$flag)
            {
                $dir = pathinfo($asset, PATHINFO_DIRNAME);
                if (!file_exists($dir))
                {						
                    mkdir($dir, isset($this->directoryMode) ? $this->directoryMode : 0711, true);
                }
                $fd = fopen($asset, 'w');
                foreach ($this->assets[$dest][0] as $src)
                {
                    foreach (glob($srcPath . $src) as $file)
                    {
                        $fs = fopen($file, 'r');
                        stream_copy_to_stream($fs, $fd);
                        fclose($fs);
                    }
                }
                fclose($fd);
                chmod($asset, isset($this->fileMode) ? $this->fileMode : 0644);
                if ($mode)
                {
                    $type = $this->assets[$dest][1];
                    foreach ([self::MODE_MINIFY, self::MODE_OBFUSCATE] as $mask)
                    {
                        if ($mode & $mask && isset($this->converters[$mask][$type]))
                        {
                            \Aleph::delegate($this->converters[$mask][$type], $asset);
                        }
                    }
                }
            }
        }
        return (isset($this->baseURL) ? rtrim($this->baseURL, '\\/') . '/' : '/') . $dest;
    }
    
    /**
     * Sets asset converter.
     *
     * @param mixed $callback - the callable object that automatically invoked to convert asset.
     * @param integer $mode - the conversion mode.
     * @param string $type - the asset type. Valid values are 'css' or 'js'.
     * @access protected
     */
    protected function setConverter($callback, $mode, $type)
    {
        if (!empty($this->devMode))
        {
            $this->converters[$mode][$type] = $callback;
        }
    }
    
    /**
     * Adds asset source.
     *
     * @param string $dest - the asset filename.
     * @param array $src - the asset source files.
     * @param boolean $merge - determines whether the previously added asset sources should be merged with the new ones.
     * @param boolean $checkDuplicates - determines whether the exception should be thrown if asset duplicates are found.
     * @param string $type - the asset type. Valid values are 'css' or 'js'.
     * @access protected
     */
    protected function add($dest, array $src, $merge, $checkDuplicates, $type)
    {
        if (!empty($this->devMode))
        {
            $src = $this->resolveDependencies($src, $type);
            if (isset($this->assets[$dest]))
            {
                if ($merge)
                {
                    $this->assets[$dest] = [array_unique(array_merge($this->assets[$dest][0], $src)), $type];
                    return;
                }
                else if ($checkDuplicates)
                {
                    throw new Core\Exception([$this, 'ERR_ASSET_MANAGER_1'], $dest);
                }
            }
            $this->assets[$dest] = [array_unique($src), $type];
        }
    }
    
    /**
     * Adds dependencies to the list of the asset source files.
     *
     * @param array $src - the asset source files.
     * @param string $type - the asset type. Valid values are 'css' or 'js'.
     * @return array
     * @access private
     */
    private function resolveDependencies(array $src, $type)
    {
        $deps = empty($this->dependencies) ? [] : $this->dependencies;
        if (!is_array($deps) || count($deps) == 0)
        {
            return $src;
        }
        $res = [];
        foreach ($src as $value)
        {
            if (isset($deps[$value]))
            {
                $lib = $deps[$value];
                if (isset($lib['depends']))
                {
                    $res = array_unique(array_merge($res, $this->resolveDependencies((array)$lib['depends'], $type)));
                }
                if (isset($lib[$type]))
                {
                    $res = array_unique(array_merge($res, (array)$lib[$type]));
                }
            }
            else
            {
                $res[] = $value;
            }
        }
        return $res;
    }
}