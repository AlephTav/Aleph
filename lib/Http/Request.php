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

use Aleph\Data,
    Aleph\Data\Converters;

/**
 * Request Class provides easier interaction with variables of the current HTTP request.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.http
 */
class Request
{   
    /**
     * The request URL instance.
     *
     * @var \Aleph\Http\URL
     */
    public $url = null;

    /**
     * The HTTP headers of the request.
     *
     * @var \Aleph\Http\HeaderBag
     */
    public $headers = null;
    
    /**
     * Query string parameters ($_GET).
     *
     * @var \Aleph\Data\Bag
     */
    public $get = null;
    
    /**
     * Request body parameters ($_POST).
     *
     * @var \Aleph\Data\Bag
     */
    public $post = null;
  
    /**
     * Uploaded files ($_FILES).
     *
     * @var \Aleph\Http\FileBag
     */
    public $files = null;
  
    /**
     * Server and execution environment parameters ($_SERVER).
     *
     * @var \Aleph\Http\ServerBag
     */
    public $server = null;
  
    /**
     * Cookies ($_COOKIE).
     *
     * @var \Aleph\Http\Bag
     */
    public $cookies = null;
  
    /**
     * The raw body of the current request.
     *
     * @var string|resource|null
     */
    protected $body = null;
    
    /**
     * Used for singleton version of the request.
     *
     * @var \Aleph\Http\Request
     */
    private static $request = null;
    
    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @param bool $asSingleton Determines whether the request instance should be stored as singleton.
     * @return static
     */
    public static function createFromGlobals(bool $asSingleton = false)
    {
        if ($asSingleton && self::$request)
        {
            return self::$request;
        }
        $request = new static(
            URL::createFromGlobals(true),
            $_SERVER,
            isset($_GET) ? $_GET : [],
            isset($_POST) ? $_POST : [],
            isset($_COOKIES) ? $_COOKIES : [],
            isset($_FILES) ? $_FILES : [],
            HeaderBag::getRequestHeaders()
        );
        if ($request->headers->getContentType() == HeaderBag::$contentTypeMap['json'])
        {
            $body = $request->getBody();
            if (is_array($body))
            {
                $request->post = new Data\Bag(array_merge(isset($_POST) ? $_POST : [], $data));
            }
        }
        else if (in_array(strtoupper($request->server['REQUEST_METHOD']), ['PUT', 'DELETE', 'PATCH']))
        {
            $body = $request->getRawBody();
            if (strlen($body))
            {
                parse_str($body, $data);
                $request->post = new Data\Bag(array_merge(isset($_POST) ? $_POST : [], $data));
            }
        }
        if ($asSingleton)
        {
            self::$request = $request;
        }
        return $request;
    }
  
    /**
     * Constructor. Initializes the request properties.
     * 
     * @param string|\Aleph\Net\URL $url The URL string.
     * @param array $server The SERVER parameters.
     * @param array $get The GET parameters.
     * @param array $post The POST parameters.
     * @param array $cookies The COOKIE parameters.
     * @param array $files The FILES parameters.
     * @param array $headers The HTTP headers.
     * @param string|resource|null $body The raw body data.
     * @return void
     */
    public function __construct($url = '', array $server = [], array $get = [], array $post = [], array $cookies = [], array $files = [], array $headers = [], $body = null)
    {
        $this->url = $url instanceof URL ? $url : new URL($url);
        $this->server = new ServerBag($server);
        $this->get = new Data\Bag($get);
        $this->post = new Data\Bag($post);
        $this->cookies = new Data\Bag($cookies);
        $this->files = new FileBag($files);
        $this->headers = new HeaderBag($headers ?: $this->server->getHeaders());
        $this->body = $body;
    }
  
    /**
     * Clones the current request.
     *
     * @return void
     */
    public function __clone()
    {
        $this->url = clone $this->url;
        $this->server = clone $this->server;
        $this->get = clone $this->get;
        $this->post = clone $this->post;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->headers = clone $this->headers;
    }
    
    /**
     * Checks if the request method is of specified type.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod($method) : bool
    {
        return $this->getMethod() === strtoupper($method);
    }
    
    /**
     * Checks whether the HTTP method is safe or not.
     *
     * @return bool
     */
    public function isMethodSafe() : bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD']);
    }

    
    /**
     * Returns TRUE if the request is a XMLHttpRequest and FALSE otherwise.
     *
     * @return bool
     */
    public function isAjax() : bool
    {
        return $this->headers['X-Requested-With'] == 'XMLHttpRequest';
    }
    
    /**
     * Returns TRUE if the current request was sent from the same host and FALSE otherwise.
     *
     * @return bool
     */
    public function isOwnHost() : bool
    {
        if (isset($this->server['HTTP_REFERER']))
        {
            return $this->server->getHost() === (new URL($this->server['HTTP_REFERER']))->host;
        }
        return false;
    }
    
    /**
     * Returns TRUE if the request sent from mobile browser and FALSE otherwise.
     *
     * @return bool
     */
    public function isMobileBrowser() : bool
    {
        if ($agent = $this->server['HTTP_USER_AGENT'])
        {
            return (bool)(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $agent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($agent, 0, 4)));
        }
        return false;
    }
    
    /**
     * Gets the request "intended" method.
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     * The method is always an uppercased string.
     *
     * @return string
     */
    public function getMethod() : string
    {
        $method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
        if ($method === 'POST')
        {
            if ($method = $this->headers['X-HTTP-METHOD-OVERRIDE'])
            {
                return strtoupper($method);
            }
        }
        return $method;
    }
    
    /**
     * Returns the ETags.
     *
     * @return array
     */
    public function getETags() : array
    {
        return preg_split('/\s*,\s*/', $this->headers['If-None-Match'], null, PREG_SPLIT_NO_EMPTY);
    }
    
    /**
     * Returns the request raw body data or a resource to read the body stream.
     *
     * @param bool $asResource If it is TRUE, a resource will be returned.
     * @return string|resource
     */
    public function getRawBody(bool $asResource = false)
    {
        $isResource = is_resource($this->body);
        if ($asResource)
        {
            if ($isResource)
            {
                rewind($this->body);
                return $this->body;
            }
            if ($this->body !== null)
            {
                $resource = fopen('php://temp', 'r+');
                fwrite($resource, $this->body);
                rewind($resource);
                return $resource;
            }
            return fopen('php://input', 'rb');
        }
        if ($isResource)
        {
            rewind($this->body);
            return stream_get_contents($this->body);
        }
        if ($this->body === null)
        {
            $this->body = file_get_contents('php://input');
        }
        return $this->body;
    }
    
    /**
     * Returns the request body converted according to the request content type.
     *
     * @return mixed
     */
    public function getBody()
    {
        $output = [
            'application/json' => Converters\TextConverter::JSON_ENCODED
        ];
        $type = $this->headers->getContentType();
        $converter = new Converters\TextConverter();
        $converter->input = isset($output[$type]) ? $output[$type] : Converters\TextConverter::ANY;
        return $converter->convert($this->getRawBody());
    }
  
    /**
     * Returns the request as a string.
     *
     * @return string
     */
    public function __toString() : string
    {
        $body = $this->getBody();
        $query = $this->url->build(URL::PATH | URL::QUERY);
        $protocol = $this->server['SERVER_PROTOCOL'];
        return $this->getMethod() . ' ' . '/' . ltrim($query, '/') . ' ' . $protocol . "\r\n" . $this->headers . "\r\n" . $body;
    }
}
