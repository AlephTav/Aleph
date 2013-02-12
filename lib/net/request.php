<?php
/**
 * Copyright (c) 2012 Aleph Tav
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
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */
 
namespace Aleph\Net;

/**
 * Request Class provides easier interaction with variables of the current HTTP request.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class Request
{
  /**
   * The instance of this class.
   * 
   * @var Aleph\Net\Request $instance
   * @access private
   */           
  private static $instance = null;
  
  /**
   * Method of the current HTTP request.
   *
   * @var string $method
   * @access public
   */
  public $method = null;
  
  /**
   * Instance of Aleph\Net\Headers class.
   *
   * @var Aleph\Net\Headers $headers
   * @access public
   */
  public $headers = null;
  
  /**
   * Raw input data of the current HTTP-request.
   *
   * @var string $input
   * @access public
   */
  public $input = null;
  
  /**
   * Request body that converted according to the given content type.
   *
   * @var mixed $body
   * @access public
   */
  public $body = null;
  
  /**
   * Data from $_GET, $_POST and $_FILES arrays. 
   *
   * @var array $data
   * @access public
   */
  public $data = array();
  
  /**
   * AJAX request indicator.
   * If the current HTTP request is AJAX this property is TRUE and FALSE otherwise.
   *
   * @var boolean $isAjax
   * @access public
   */
  public $isAjax = null;
  
  /**
   * Detects whether the user browser is a mobile browser.
   * The regular expression for mobile browser detection was taken from http://detectmobilebrowsers.com
   *
   * @var boolean $isMobileBrowser
   * @access public
   */
  public $isMobileBrowser = null;
  
  /**
   * IP address of requesting client.
   *
   * @var string $ip
   * @access public
   */
  public $ip = null;
  
  /**
   * Aleph\Net\URL object appropriate to the current requested URL.
   *
   * @var Aleph\Net\URL $url
   * @access public
   */
  public $url = null;
  
  /**
   * Returns an instance of this class.
   * 
   * @return Aleph\Net\Request
   * @access public
   * @static
   */
  public static function getInstance()
  {
    if (self::$instance === null) self::$instance = new self();
    return self::$instance;
  }
  
  /**
   * Clones an object of this class. The private method '__clone' doesn't allow to clone an instance of the class.
   * 
   * @access private
   */
  private function __clone(){}
  
  /**
   * Constructor. Initializes all internal variables of the class.
   *
   * @access public
   */
  private function __construct()
  {
    $this->headers = Headers::getRequestHeaders();
    $this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
    $this->input = file_get_contents('php://input');
    $this->body = $this->convert();
    $this->data = array_merge($_GET, $_POST, $_FILES);
    $this->isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') : false;
    $this->isMobileBrowser = isset($_SERVER['HTTP_USER_AGENT']) ? (bool)(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'], 0, 4))) : false;
    $this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    $this->url = new URL();
  }
  
  /**
   * Converts request raw body according to the given content type.
   *
   * @return string
   * @access protected
   */
  public function convert()
  {
    $type = $this->headers->get('Content-Type');
    if ($type === false || strlen($this->input) == 0) return $this->input;
    switch ($type)
    {
      case 'text/plain':
      case 'text/html':
        try
        {
          return unserialize($this->input);
        }
        catch (\Exception $e)
        {
          return $this->input;
        }
      case 'application/json':
        return json_decode($this->body, true);
      case 'application/xml':
        return json_decode(json_encode(simplexml_load_string($this->input)), true);
    }
    return $this->input;
  }
}