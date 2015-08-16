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

use Aleph\Utils;

/**
 * The simple container for HTTP headers from the $_SERVER variable.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.net
 */
class ServerBag extends Utils\Bag
{
    /**
     * Constructor.
     * The most of the code of this method is taken from the Symfony framework (see Symfony\Component\HttpFoundation\ServerBag::getHeaders()).
     *
     * @param array $arr - an array of key/value pairs.
     * @param string $delimiter - the default key delimiter in composite keys.
     * @access public
     */
    public function __construct(array $arr = [], $delimiter = Utils\Arr::DEFAULT_KEY_DELIMITER)
    {
        parent::__construct($arr, $delimiter);
        if (!isset($this->arr['PHP_AUTH_USER']))
        {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */
            $authorizationHeader = null;
            if (isset($this->arr['HTTP_AUTHORIZATION']))
            {
                $authorizationHeader = $this->arr['HTTP_AUTHORIZATION'];
            }
            else if (isset($this->arr['REDIRECT_HTTP_AUTHORIZATION']))
            {
                $authorizationHeader = $this->arr['REDIRECT_HTTP_AUTHORIZATION'];
            }
            if ($authorizationHeader !== null)
            {
                if (stripos($authorizationHeader, 'basic ') === 0)
                {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic.
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (count($exploded) == 2)
                    {
                        list($this->arr['PHP_AUTH_USER'], $this->arr['PHP_AUTH_PW']) = $exploded;
                    }
                }
                else if (empty($this->arr['PHP_AUTH_DIGEST']) && (stripos($authorizationHeader, 'digest ') === 0))
                {
                    $this->arr['PHP_AUTH_DIGEST'] = $authorizationHeader;
                }
                else if (stripos($authorizationHeader, 'bearer ') === 0)
                {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     * I'll just set $headers['AUTHORIZATION'] here.
                     * http://php.net/manual/en/reserved.variables.server.php
                     */
                    $this->arr['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }
    }
    
    /**
     * Returns HTTP headers of the current HTTP request.
     *
     * @return array
     * @access public
     */
    public function getHeaders()
    {
        $headers = [];
        $contentHeaders = [
            'CONTENT_LENGTH' => true,
            'CONTENT_MD5' => true,
            'CONTENT_TYPE' => true
        ];
        foreach ($this->arr as $key => $value)
        {
            if (strpos($key, 'HTTP_') === 0)
            {
                $headers[substr($key, 5)] = $value;
            }
            else if (isset($contentHeaders[$key]))
            {
                $headers[$key] = $value;
            }
        }
        if (isset($this->arr['PHP_AUTH_USER']))
        {
            $headers['PHP_AUTH_USER'] = $this->arr['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($this->arr['PHP_AUTH_PW']) ? $this->arr['PHP_AUTH_PW'] : '';
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        }
        else if (isset($headers['PHP_AUTH_DIGEST']))
        {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }
        return $headers;
    }
    
    /**
     * Returns the request's scheme.
     *
     * @return string
     * @access public
     */
    public function getScheme()
    {
        if ($proto = $this->__get('HTTP_X_FORWARDED_PROTO'))
        {
            $secure = in_array(strtolower(current(explode(',', $proto))), ['https', 'on', 'ssl', '1']);
        }
        else
        {
            $https = $this->__get('HTTPS');
            $secure = !empty($https) && strtolower($https) !== 'off';
        }
        return $secure ? 'https' : 'http';
    }
    
    /**
     * Returns the host name.
     *
     * @return string
     * @access public
     */
    public function getHost()
    {
        if ($host = $this->__get('HTTP_X_FORWARDED_HOST'))
        {
            $parts = explode(',', $host);
            $host = $parts[count($parts) - 1];
        }
        else if (!$host = $this->__get('HTTP_HOST'))
        {
            if (!$host = $this->__get('SERVER_NAME'))
            {
                $host = $this->get('SERVER_ADDR', '');
            }
        }
        return strtolower(preg_replace('/:\d+$/', '', trim($host)));
    }
    
    /**
     * Returns the port on which the request is made.
     *
     * @return integer|null
     * @access public
     */
    public function getPort()
    {
        if ($port = (int)$this->__get('HTTP_X_FORWARDED_PORT'))
        {
            return $port;
        }
        if ($this->__get('HTTP_X_FORWARDED_PROTO') === 'https')
        {
            return 443;
        }
        if ($host = $this->__get('HTTP_HOST'))
        {
            $pos = strrpos($host, ':');
            if ($pos !== false)
            {
                return (int)substr($host, $pos + 1);
            }
            return $this->getScheme() === 'https' ? 443 : 80;
        }
        return $this->__get('SERVER_PORT');
    }
    
    /**
     * Returns the user.
     *
     * @return string|null
     * @access public
     */
    public function getUser()
    {
        return $this->__get('PHP_AUTH_USER');
    }
    
    /**
     * Returns the password.
     *
     * @return string|null
     * @access public
     */
    public function getPassword()
    {
        return $this->__get('PHP_AUTH_PW');
    }
    
    /**
     * Returns URL of the request.
     *
     * @return string
     * @access public
     */
    public function getURL()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();
        if ($scheme === 'http' && $port == 80 || $scheme === 'https' && $port == 443)
        {
            $host = $this->getHost();
        }
        else
        {
            $host = $this->getHost() . ':' . $port;
        }
        $uri = '';
        if ($this->__isset('HTTP_X_ORIGINAL_URL'))
        {
            // IIS with Microsoft Rewrite Module
            $uri = $this->__get('HTTP_X_ORIGINAL_URL');
        }
        else if ($this->__isset('HTTP_X_REWRITE_URL'))
        {
            // IIS with ISAPI_Rewrite
            $uri = $this->__get('HTTP_X_REWRITE_URL');
        }
        else if ($this->__get('IIS_WasUrlRewritten') == '1' && $this->__get('UNENCODED_URL') != '')
        {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            $uri = $this->__get('UNENCODED_URL');
        }
        else if ($this->__isset('REQUEST_URI'))
        {
            $uri = $this->__get('REQUEST_URI');
            // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path, only use URL path
            if (strpos($uri, $host) === 0)
            {
                $uri = substr($uri, strlen($host));
            }
        }
        else if ($this->__isset('ORIG_PATH_INFO'))
        {
            // IIS 5.0, PHP as CGI
            $uri = $this->__get('ORIG_PATH_INFO');
            if ($this->__get('QUERY_STRING') != '')
            {
                $uri .= '?' . $this->__get('QUERY_STRING');
            }
        }
        else if ($this->__isset('PHP_SELF'))
        {
            $uri = $this->__get('PHP_SELF');
        }
        $user = $this->getUser();
        if (strlen($user))
        {
            $host = $user . ':' . $this->getPassword() . '@' . $host;
        }
        return $this->getScheme() . '://' . $host . $uri;
    }
}