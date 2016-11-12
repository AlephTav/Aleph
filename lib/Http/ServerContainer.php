<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Http;

use Aleph\Data\Structures\Container;
use Aleph\Utils\Arr;

/**
 * The simple container for HTTP headers from the $_SERVER variable.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.http
 */
class ServerContainer extends Container
{
    /**
     * Constructor.
     * The most of the code of this method is taken from the Symfony framework (see Symfony\Component\HttpFoundation\ServerBag::getHeaders()).
     *
     * @param array $items An array of key/value pairs.
     * @param string $delimiter The default key delimiter in composite keys.
     */
    public function __construct(array $items = [], string $delimiter = Arr::DEFAULT_KEY_DELIMITER)
    {
        parent::__construct($items, $delimiter);
        if (!isset($this->items['PHP_AUTH_USER'])) {
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
            if (isset($this->items['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->items['HTTP_AUTHORIZATION'];
            } else if (isset($this->items['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $this->items['REDIRECT_HTTP_AUTHORIZATION'];
            }
            if ($authorizationHeader !== null) {
                if (stripos($authorizationHeader, 'basic ') === 0) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic.
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (count($exploded) == 2) {
                        list($this->items['PHP_AUTH_USER'], $this->items['PHP_AUTH_PW']) = $exploded;
                    }
                } else if (empty($this->items['PHP_AUTH_DIGEST']) && (stripos($authorizationHeader, 'digest ') === 0)) {
                    $this->items['PHP_AUTH_DIGEST'] = $authorizationHeader;
                } else if (stripos($authorizationHeader, 'bearer ') === 0) {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     * I'll just set $headers['AUTHORIZATION'] here.
                     * http://php.net/manual/en/reserved.variables.server.php
                     */
                    $this->items['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }
    }

    /**
     * Returns HTTP headers of the current HTTP request.
     *
     * @return array
     */
    public function getHeaders() : array
    {
        $headers = [];
        $contentHeaders = [
            'CONTENT_LENGTH' => true,
            'CONTENT_MD5' => true,
            'CONTENT_TYPE' => true
        ];
        foreach ($this->items as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[substr($key, 5)] = $value;
            } else if (isset($contentHeaders[$key])) {
                $headers[$key] = $value;
            }
        }
        if (isset($this->items['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->items['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($this->items['PHP_AUTH_PW']) ? $this->items['PHP_AUTH_PW'] : '';
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        } else if (isset($headers['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }
        return $headers;
    }

    /**
     * Returns the request's scheme.
     *
     * @return string
     */
    public function getScheme() : string
    {
        if ($proto = $this['HTTP_X_FORWARDED_PROTO']) {
            $secure = in_array(strtolower(current(explode(',', $proto))), ['https', 'on', 'ssl', '1']);
        } else {
            $https = $this['HTTPS'];
            $secure = !empty($https) && strtolower($https) !== 'off';
        }
        return $secure ? 'https' : 'http';
    }

    /**
     * Returns the host name.
     *
     * @return string
     */
    public function getHost() : string
    {
        if ($host = $this['HTTP_X_FORWARDED_HOST']) {
            $parts = explode(',', $host);
            $host = $parts[count($parts) - 1];
        } else if (!$host = $this['HTTP_HOST']) {
            if (!$host = $this['SERVER_NAME']) {
                $host = (string)$this['SERVER_ADDR'];
            }
        }
        return strtolower(preg_replace('/:\d+$/', '', trim($host)));
    }

    /**
     * Returns the port on which the request is made.
     *
     * @return int|null
     */
    public function getPort()
    {
        if ($port = (int)$this['HTTP_X_FORWARDED_PORT']) {
            return $port;
        }
        if ($this['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return 443;
        }
        if ($host = $this['HTTP_HOST']) {
            $pos = strrpos($host, ':');
            if ($pos !== false) {
                return (int)substr($host, $pos + 1);
            }
            return $this->getScheme() === 'https' ? 443 : 80;
        }
        return $this['SERVER_PORT'];
    }

    /**
     * Returns the user.
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this['PHP_AUTH_USER'];
    }

    /**
     * Returns the password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this['PHP_AUTH_PW'];
    }

    /**
     * Returns URL of the request.
     *
     * @return string
     */
    public function getURL() : string
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();
        if ($scheme === 'http' && $port == 80 || $scheme === 'https' && $port == 443) {
            $host = $this->getHost();
        } else {
            $host = $this->getHost() . ':' . $port;
        }
        $uri = '';
        if (isset($this['HTTP_X_ORIGINAL_URL'])) {
            // IIS with Microsoft Rewrite Module
            $uri = $this['HTTP_X_ORIGINAL_URL'];
        } else if (isset($this['HTTP_X_REWRITE_URL'])) {
            // IIS with ISAPI_Rewrite
            $uri = $this['HTTP_X_REWRITE_URL'];
        } else if ($this['IIS_WasUrlRewritten'] == '1' && $this['UNENCODED_URL'] != '') {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            $uri = $this['UNENCODED_URL'];
        } else if (isset($this['REQUEST_URI'])) {
            $uri = $this['REQUEST_URI'];
            // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path, only use URL path
            if (strpos($uri, $host) === 0) {
                $uri = substr($uri, strlen($host));
            }
        } else if (isset($this['ORIG_PATH_INFO'])) {
            // IIS 5.0, PHP as CGI
            $uri = $this['ORIG_PATH_INFO'];
            if ($this['QUERY_STRING'] != '') {
                $uri .= '?' . $this['QUERY_STRING'];
            }
        } else if (isset($this['PHP_SELF'])) {
            $uri = $this['PHP_SELF'];
        }
        $user = $this->getUser();
        if (strlen($user)) {
            $host = $user . ':' . $this->getPassword() . '@' . $host;
        }
        return $this->getScheme() . '://' . $host . $uri;
    }
}