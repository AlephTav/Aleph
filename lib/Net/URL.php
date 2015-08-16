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
 
namespace Aleph\Net;

use Aleph\Core,
	Aleph\Utils;

/**
 * URL Class is designed to modify existing URL strings and to construct new ones.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.0
 * @package aleph.net
 */
class URL
{ 
    /**
     * The following constants are used to get separate parts of a URL.
     * Eeach constant is related to a particular part of a URL.
     * E.g.: URL http://user:password@my.host.com:8080/one/two/three?var1=val1&var2=val2&var3=val3#fragment
     * Here: 
     * ALL corresponds whole URL.
     * SCHEME corresponds http://
     * HOST corresponds user:password@my.host.com:8080
     * QUERY corresponds var1=val1&var2=val2&var3=val3
     * PATH corresponds /one/two/three
     * FRAGMENT corresponds fragment
     * Also you can combine these constants to get needed part of URL.
     * For more information about URL structure, please see http://en.wikipedia.org/wiki/Uniform_resource_locator
     */
    const ALL = 31;
    const SCHEME = 1;
    const HOST = 2;
    const PATH = 4;
    const QUERY = 8;
    const FRAGMENT = 16;
  
    /**
     * Scheme component of a URL.
     *
     * @var string $scheme
     * @access public
     */
    public $scheme = null;

    /**
     * The host of the URL.
     *
     * @var string $host
     * @access public
     */
    public $host = null;
    
    /**
     * The port of the URL.
     *
     * @var integer $port
     * @access public
     */
    public $port = null;
    
    /**
     * The user.
     *
     * @var string $user
     * @access public
     */
    public $user = null;
    
    /**
     * The password.
     *
     * @var string $password
     * @access public
     */
    public $password = null;

    /**
     * Path component of a URL.
     * Path represents array of separate parts of URL path component.
     * E.g.: URL path /one/two/three will be represented as ['one', 'two', 'three'].
     *
     * @var array $path
     * @access public
     */
    public $path = [];

    /**
     * Query component of a URL.
     * Query represents associative array in which keys and values are names and values of query fields.
     * E.g.: URL query ?var1=val1&var2=val2&var3=val3 will be represented as ['var1' => 'val1', 'var2' => 'val2', 'var3' => 'val3']
     *
     * @var Aleph\Utils\Bag $query
     * @access public
     */
    public $query = null;

    /**
     * Fragment component of a URL.
     *
     * @var string $fragment
     * @access public
     */
    public $fragment = null;

    /**
     * Constructs a new URL object.
     * - new URL() parses the current requested URL.
     * - new URL($url) parses the given URL - $url.
     *
     * @param string $url
     * @access public
     */
    public function __construct($url = null)
    {
        $this->parse($url);
    }
  
    /**
     * Returns the current URL of the request.
     *
     * @param boolean $asString - determines whether the current URL should be returned as string, not as object.
     * @return string
     * @access public
     * @static
     */
    public static function createFromGlobals($asString = false)
    {
		$url = '';
		if (!empty($_SERVER) && is_array($_SERVER))
		{
            $url = (new ServerBag($_SERVER))->getURL();
		}
        return $asString ? $url : new static($url);
    }
    
    /**
     * Resets URL parameters.
     *
     * @access public
     */
    public function reset()
    {
        $this->scheme = '';
        $this->host = '';
        $this->port = '';
        $this->user = '';
        $this->password = '';
        $this->path = [];
        $this->query = new Utils\Bag([]);
        $this->fragment = '';
    }
  
    /**
     * Parses URL. After parsing you can access to all URL components.
     *
     * @param string $url
     * @access public
     */
    public function parse($url)
    {
        $url = (string)$url;
        if ($url === '')
        {
            $this->reset();
            return;
        }
        preg_match_all('@^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?@', $url, $arr);
        $this->scheme = strtolower($arr[2][0]);
        $data = explode('@', $arr[4][0]);
        if (empty($data[1]))
        {
            $data = explode(':', $data[0]);
            $this->host = urldecode($data[0]);
            $this->port = isset($data[1]) ? (int)$data[1] : '';
            $this->user = '';
            $this->password = '';
        }
        else
        {
            $d1 = explode(':', $data[1]);
            $this->host = urldecode($d1[0]);
            $this->port = isset($d1[1]) ? (int)$d1[1] : '';
            $d2 = explode(':', $data[0]);
            $this->user = urldecode($d2[0]);
            $this->password = urldecode(isset($d2[1]) ? $d2[1] : '');
        }
        $this->path = ($arr[5][0] != '') ? array_values(array_filter(explode('/', $arr[5][0]), 'strlen')) : [];
        foreach ($this->path as &$part)
        {
            $part = urldecode($part);
        }
        $this->query = $arr[7][0];
        parse_str($this->query, $this->query);
		$this->query = new Utils\Bag($this->query);
        $this->fragment = urldecode($arr[9][0]);
    }
  
    /**
     * Returns URL or a part of URL.
     * E.g. URL http://user:pass@my.host.com/some/path?p1=v1&p2=v2#frag
     * <code>
     * $url = new URL('http://user:pass@my.host.com/some/path?p1=v1&p2=v2#frag');
     * echo $url->build();                              // shows whole URL
     * echo $url->build(URL::SCHEME);                   // shows http://
     * echo $url->build(URL::HOST);                     // shows user:pass@my.host.com
     * echo $url->build(URL::PATH);                     // shows /some/path
     * echo $url->build(URL::QUERY);                    // shows p1=v1&p2=v2
     * echo $url->build(URL::PATH | URL::QUERY);        // shows /some/path?p1=v1&p2=v2
     * echo $url->build(URL::QUERY | URL::FRAGMENT);    // shows p1=v1&p2=v2#frag
     * </code>
     *
     * @param string $component - name of an URL component.
     * @return string
     * @access public
     */
    public function build($component = null)
    {
        $url = '';
		$component = $component === null ? static::ALL : $component;
        if ($component & static::SCHEME && isset($this->scheme) && strlen($this->scheme)) 
        {
            $url .= strtolower($this->scheme) . '://';
        }
        if ($component & static::HOST && isset($this->host) && strlen($this->host))
        {
            $credentials = isset($this->user) && strlen($this->user) ? $this->user . ':' . (isset($this->password) ? $this->password : '') : '';
            $url .= ($credentials ? $credentials . '@' : '') . $this->host;
            if (isset($this->port))
            {
                $secure = $this->isSecure();
                if ($secure && $this->port != 443 || !$secure && $this->port != 80)
                {
                    $url .= strlen($this->port) ? ':' . (int)$this->port : '';
                }
            }
        }
        if ($component & static::PATH && isset($this->path))
        {
            $tmp = [];
            foreach ((array)$this->path as $part)
            {
                if (strlen($part))
                {
                    $tmp[] = urlencode($part);
                }
            }
            if ($component & static::HOST && count($tmp))
            {
                $url .= '/';
            }
            $url .= implode('/', $tmp);
        }
        if ($component & static::QUERY && isset($this->query) && $this->query instanceof Utils\Bag && count($this->query))
        {
            if (strlen($url) || $component & static::PATH)
            {
                $url .= '?';
            }
            $url .= http_build_query($this->query->all());
        }
        if ($component & static::FRAGMENT && isset($this->fragment) && strlen($this->fragment))
        {
            if (strlen($url))
            {
                $url .= '#';
            }
            $url .= urlencode($this->fragment);
        }
        return $url;
    }
  
    /**
     * Returns TRUE, if using HTTPS protocol or FALSE otherwise.
     *
     * @return boolean
     * @access public
     */
    public function isSecure()
    {
        return isset($this->scheme) && strtolower($this->scheme) === 'https';
    }
  
    /**
     * Set or unset HTTPS protocol.
     * If using some other protocol (ftp, mailto etc.) then it will be replaced with HTTP/HTTPS protocol.
     *
     * @param boolean $flag - sign of the HTTPS protocol.
     * @access public
     */
    public function secure($flag = true)
    {
        $this->scheme = $flag ? 'https' : 'http';
    }
  
    /**
     * Converts object of this class in string corresponding whole URL.
     * This method is equivalent to method build().
     *
     * @return string
     * @access public
     */
    public function __toString()
    {
        return $this->build();
    }
}