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

use Aleph\Core,
    Aleph\Cache;

if (!defined('START_ALEPH_TS'))
{
    define('START_ALEPH_TS', microtime(true));
}

/**
 * General class of the framework.
 * Using this class you can log error messages, profile your code,
 * catch any errors, load classes and configure your application.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.1
 * @package aleph.core
 */
final class Aleph
{
    /**
     * Bug and debug templates.
     */
    const DEBUG_TEMPLATE = '<!doctype html><html><head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type" /><title>Bug Report</title><body bgcolor="gold">The following error <pre>$message</pre> has been catched in file <b>$file</b> on line $line<br /><br /><b style="font-size: 14px;">Stack Trace:</b><pre>$traceAsString</pre><b>Execution Time:</b><pre>$executionTime sec</pre><b>Memory Usage:</b><pre>$memoryUsage Mb</pre></body></html>';
    const ERROR_TEMPLATE = 'Sorry, server is not available at the moment. Please wait. This site will be working very soon!';
  
    /**
     * Error message templates throwing by Aleph class.
     */
    const ERR_1 = 'Class "%s" is not found.';
    const ERR_2 = 'Class "%s" found in file "%s" is duplicated in file "%s".';
    const ERR_3 = 'Path to the class map file is not set. You should define the configuration variable "classmap" in section "autoload".';
    const ERR_4 = 'Timestamp with mark "%s" is not found.';
    const ERR_5 = 'Failed to build URL. The given directory "%s" is outside of the application document root directory.';
    const ERR_6 = 'Failed to load zlib extension.';
    
    /**
     * Fatal error code.
     */
    const FATAL_ERROR_CODE = 999;
    
    /**
     * Initialization flags.
     */
    const INIT_COMPRESS_OUTPUT = 1;      // determines whether the zlib output compression should be used.
    const INIT_START_SESSION = 2;        // determines whether the session should be started.
    const INIT_CLOSE_OUTPUT_BUFFERS = 4; // determines whether the previous opened output buffers should be closed before Aleph's initialization.
    const INIT_USE_OUTPUT_BUFFERING = 8; // determines whether the output buffering should be used.
    
    /**
     * Determines whether the framework was initialized or not.
     *
     * @var bool
     */
    private static $isInitialized = false;
    
    /**
     * Initialization flags value.
     *
     * @var int
     */
    private static $flags = 0;
  
    /**
     * Unique ID of the application (site).
     *
     * @var string
     */
    private static $appUniqueID = '';
  
    /**
     * Path to site root directory.
     *
     * @var string
     */
    private static $root = '';
  
    /**
     * Array of timestamps.
     *
     * @var array
     */
    private static $ts = [];
  
    /**
     * The response body.
     *
     * @var string
     */
    private static $output = null;
  
    /**
     * Array with information about some code that was executed by the operator eval.
     *
     * @var array
     */
    private static $eval = [];
  
    /**
     * Marker of the error handling mode.
     *
     * @var bool
     */
    private static $errorHandling = false;
    
    /**
     * Custom error handler.
     *
     * @var mixed
     */
    private static $errorHandler = null;
    
    /**
     * Custom log function.
     *
     * @var mixed
     */
    private static $logger = null;
  
    /**
     * Instance of the class Aleph\Cache\Cache (or its child).
     *
     * @var \Aleph\Cache\Cache
     */
    private static $cache = null;
  
    /**
     * Array of paths to all classes of the application and framework.
     *
     * @var array
     */
    private static $classes = [];
  
    /**
     * Path to the class map file.
     *
     * @var string
     */
    private static $classmap = '';
  
    /**
     * Default configuration variables.
     *
     * @var array
     */
    private static $config = [
        // General settings.
        'debugging' => true,
        'logging' => true,
        'debugTemplate' => '@aleph/_templates/debug.tpl',
        'errorTemplate' => '@aleph/_templates/error.tpl',
        // Cache settings.
        'cache' => [
            'type' => 'file',
            'directory' => '@cache',
            'gcProbability' => 5
        ],
        // Class autoload settings.
        'autoload' => [
            'search' => false,
            'unique' => true,
            'classmap' => 'classmap.php',
            'mask' => '/.+\\.php\\z/i', 
            'timeout' => 300,
            'disableExceptions' => false,
            'directories' => [],
            'namespaces' => [
                'App' => '@app'
            ],
            'exclusions' => [
                '@aleph/_tests',
                '@aleph/_templates'
            ]
        ],
        // View settings.
        'view' => [
            'directories' => [
                '@app/views'
            ]
        ],
        // Database log and cache settings
        'db' => [
            'logging' => false,
            'log' => 'tmp/sql.log', 
            'cacheExpire' => 0,
            'cacheGroup' => 'db'
        ],
        // Active Record cache settings
        'ar' => [
            'cacheExpire' => -1,
            'cacheGroup' => 'ar'
        ],
        // Directories' aliases
        'dirs' => [
            'aleph' => 'lib',
            'app' => 'app', 
            'logs' => 'tmp/logs',
            'cache' => 'tmp/cache',
            'temp' => 'tmp/temp'
        ],
        // Console settings
        'console' => [
            'commands' => [
                'Aleph\Console\Commands\Chuck'
            ]
        ]
    ];
    
    /**
     * Initializes the Aleph framework.
     *
     * @param string $root The document root directory. If it is not set the $_SERVER['DOCUMENT_ROOT'] is used.
     * @param string $timezone The current timezone. If it is not set the timezone specified in php.ini is used.
     * @param int|null $flags The initilization flags. If $flags is NULL the default value is used.
     * @param array $sessionOptions An associative array of options that will override the currently set session configuration directives.
     * @return void
     * @throws \RuntimeException When zlib extension is not loaded.
     */
    public static function init(string $root = '', string $timezone = '', int $flags = null, array $sessionOptions = [])
    {
        if (self::$isInitialized)
        {
            return;
        }
        ini_set('html_errors', 0);
        self::$flags = $flags ?: (PHP_SAPI === 'cli' ? 0 : self::INIT_START_SESSION | self::INIT_COMPRESS_OUTPUT | self::INIT_USE_OUTPUT_BUFFERING);
        if (self::$flags & self::INIT_CLOSE_OUTPUT_BUFFERS)
        {
            self::closeOutputBuffers(0);
        }
        if (self::$flags & self::INIT_COMPRESS_OUTPUT)
        {
            if (!extension_loaded('zlib'))
            {
                throw new \RuntimeException(self::ERR_6);
            }
            ini_set('output_handler', '');
            ini_set('output_buffering', 1);
            ini_set('implicit_flush', 0);
            ini_set('zlib.output_compression', 4096);
        }
        $fatal = null;
        $errors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        register_shutdown_function(function() use(&$fatal, $errors)
        {
            Aleph::reserveMemory(0);
            if (Aleph::isErrorHandlingEnabled())
            {
                $fatal = error_get_last();
                if ($fatal && in_array($fatal['type'], $errors))
                {
                    Aleph::exception(new \ErrorException($fatal['message'], self::FATAL_ERROR_CODE, 1, $fatal['file'], $fatal['line']));
                }
            }
        });
        if (self::$flags & self::INIT_USE_OUTPUT_BUFFERING)
        {
            ob_start(function($output) use(&$fatal, $errors)
            {
                if (Aleph::isErrorHandlingEnabled()) 
                {
                    $error = error_get_last();
                    if ($error && in_array($error['type'], $errors) && $error !== $fatal)
                    {
                        Aleph::exception(new \ErrorException($error['message'], self::FATAL_ERROR_CODE, 1, $error['file'], $error['line']));
                    }
                }
                return self::$output !== null ? self::$output : $output;
            });
        }
        if ($timezone) 
        {
            date_default_timezone_set($timezone);
        }
        else if (date_default_timezone_set(@date_default_timezone_get()) === false) 
        {
            date_default_timezone_set('UTC');
        }
        self::setErrorLevel(E_ALL);
        self::enableErrorHandling();
        self::$root = $root ? realpath($root) : ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
        self::$appUniqueID = md5(self::$root);
        $_SERVER['DOCUMENT_ROOT'] = self::$root;
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(function($class)
        {
            Aleph::loadClass($class, empty(self::$config['autoload']['disableExceptions']));
        });
        if (self::$flags & self::INIT_START_SESSION)
        {
            if (!session_id())
            {
                session_start($sessionOptions);
            }
            self::showDebugInfo(true);
        }
        set_time_limit(0);
        self::$isInitialized = true;
    }
    
    /**
     * Returns TRUE if Aleph has been initialized and FALSE otherwise.
     *
     * @return bool
     */
    public static function isInitialized() : bool
    {
        return self::$isInitialized;
    }
    
    /**
     * Reserves a block of memory to prevent out-of-memory errors.
     * If $size is set to 0 the reserved block will be freed.
     *
     * @param int $size The size of the reserved memory.
     * @return void
     */
    public static function reserveMemory(int $size = 5242880)
    {
        static $reservedMemory;
        if ($size === 0)
        {
            $reservedMemory = null;
        }
        else
        {
            $reservedMemory = str_repeat(chr(0), $size);
        }
    }

    /**
     * Sets value of the response body.
     *
     * @param string $output The response body.
     * @param bool $terminate If it equals TRUE the script execution will be immediately terminated.
     * @param int $exitCode The exit status.
     * @return void
     */
    public static function output($output, bool $terminate = true, int $exitCode = 0)
    {
        if (self::$flags & self::INIT_USE_OUTPUT_BUFFERING)
        {
            self::$output = $output;
        }
        else
        {
            echo $output;
        }
        if ($terminate)
        {
            exit($exitCode > 254 ? 254 : $exitCode);
        }
    }
  
    /** 
     * Returns array of configuration variables.
     *
     * @return array
     */
    public static function getConfig() : array
    {
        return self::$config;
    }
    
    /**
     * Specifies configuration variables.
     *
     * @param mixed $data The path to the configuration file or array of configuration variables.
     * @param bool $merge Determines whether the existing variables should be merged with new ones.
     * @return void
     */
    public static function setConfig($data, bool $merge = true)
    {
        if (!is_array($data))
        {
            $data = require($data);
        }
        if ($merge)
        {
            foreach ($data as $name => $value)
            {
                if (is_array($value) && isset(self::$config[$name]) && is_array(self::$config[$name]))
                {
                    self::$config[$name] = array_merge(self::$config[$name], $value);
                }
                else
                {
                    self::$config[$name] = $value;
                }
            }
        }
        else
        {
            self::$config = $data;
        }
    }
  
    /**
     * Returns a configuration variable by its name.
     *
     * @param string $name The name of a configuration variable.
     * @param mixed $default The default value of a configuration variable.
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        $cfg = self::$config;
        foreach (explode('.', $name) as $key)
        {
            if (!is_array($cfg) || !isset($cfg[$key]) && !array_key_exists($key, $cfg))
            {
                return $default;
            }
            $cfg = $cfg[$key];
        }
        return $cfg;
    }
  
    /**
     * Sets new value of a configuration variable.
     *
     * @param string $name The name of a configuration variable.
     * @param mixed $value The value of a configuration variable.
     * @param bool $merge Determines whether the old configuration value should be merged with new one.
     * @return void
     */
    public static function set(string $name, $value, bool $merge = false)
    {
        $cfg = &self::$config;
        foreach (explode('.', $name) as $key)
        {
            $cfg = &$cfg[$key];
        }
        if ($merge && is_array($cfg) && is_array($value))
        {
            $cfg = array_merge($cfg, $value);
        }
        else
        {
            $cfg = $value;
        }
    }
  
    /**
     * Checks whether a configuration variable is defined or not.
     *
     * @param string $name The name of a configuration variable.
     * @return bool
     */
    public static function has(string $name) : bool
    {
        $cfg = self::$config;
        foreach (explode('.', $name) as $key)
        {
            if (!is_array($cfg) || !isset($cfg[$key]) && !array_key_exists($key, $cfg))
            {
                return false;
            }
            $cfg = $cfg[$key];
        }
        return true;
    }
  
    /**
     * Removes a configuration variable by its name. 
     *
     * @param string $name The name of a configuration variable.
     * @return void
     */
    public static function remove(string $name)
    {
        $cfg = &self::$config;
        $keys = explode('.', $name);
        $last = array_pop($keys);
        foreach ($keys as $key)
        {
            if (!is_array($cfg))
            {
                return;
            }
            $cfg = &$cfg[$key];
        }
        unset($cfg[$last]);
    }
    
    /**
     * Cleans or flushes output buffers up to target level.
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @param int $targetLevel The target output buffering level.
     * @param bool $flush Determines whether to flush or clean the buffers.
     * @param bool $returnContent Determines whether the buffer contents should be returned.
     * @return string
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush = false, bool $returnContent = false) : string
    {
        $content = '';
        $status = ob_get_status(true);
        $level = count($status);
        $flags = defined('PHP_OUTPUT_HANDLER_REMOVABLE') ? PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE) : -1;
        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || $flags === ($s['flags'] & $flags) : $s['del']))
        {
            if ($flush)
            {
                if ($returnContent)
                {
                    $content .= ob_get_flush();
                }
                else
                {
                    ob_end_flush();
                }
            }
            else
            {
                if ($returnContent)
                {
                    $content .= ob_get_clean();
                }
                else
                {
                    ob_end_clean();
                }
            }
        }
        return $content;
    }
  
    /**
     * Returns site root directory.
     *
     * @return string
     */
    public static function getRoot() : string
    {
        return self::$root;
    }
  
    /**
     * Sets start time point for some code part.
     *
     * @param string $key The timestamp mark for some code part.
     * @return void
     */
    public static function start(string $key)
    {
        self::$ts[$key] = microtime(true);
    }
  
    /**
     * Returns execution time of some code part by its time mark.
     * If a such time mark doesn't exit then the method return false.
     *
     * @param string $key The timestamp mark of some code part.
     * @return float
     * @throws \OutOfBoundsException If timestamp with the given mark does not exist. 
     */
    public static function stop(string $key) : float
    {
        if (!isset(self::$ts[$key]))
        {
            throw new \OutOfBoundsException(sprintf(self::ERR_4, $key));
        }
        return microtime(true) - self::$ts[$key];
    }

    /**
     * Returns the amount of memory, in bytes, that's currently being allocated to your PHP script.
     *
     * @return int
     */
    public static function getMemoryUsage() : int
    {
        return memory_get_usage(true);
    }
  
    /**
     * Returns the peak of memory, in bytes, that's been allocated to your PHP script.
     *
     * @return int
     */
    public static function getPeakMemoryUsage() : int
    {
        return memory_get_peak_usage(true);
    }
  
    /**
     * Returns the execution time (in seconds) of your PHP script.
     *
     * @return float
     */
    public static function getExecutionTime() : float
    {
        return microtime(true) - START_ALEPH_TS;
    }
  
    /**
     * Returns the request time (in seconds) of your PHP script or 0 on failure. 
     *
     * @return float
     */
    public static function getRequestTime() : float
    {
        if (!isset($_SERVER['REQUEST_TIME']))
        {
            return 0;
        }
        return microtime(true) - (float)$_SERVER['REQUEST_TIME'];
    }
  
    /**
     * Returns the unique ID of your application (site).
     *
     * @return string
     */
    public static function getAppUniqueID() : string
    {
        return self::$appUniqueID;
    }
  
    /**
     * Calls a user-defined callback.
     *
     * @param mixed $callback A user-defined callback.
     * @params mixed $args Arguments of the callback.
     * @return mixed
     */
    public static function call($callback, ...$args)
    {
        if (is_callable($callback))
        {
            return call_user_func_array($callback, $args);
        }
        return (new Core\Callback($callback))->call($args);
    }
    
    /**
     * Returns custom error handler or NULL if not specified.
     *
     * @return mixed
     */
    public static function getErrorHandler()
    {
        return self::$errorHandler;
    }
    
    /**
     * Sets custom error handler.
     *
     * @param mixed $callback A user-defined callback that will be automatically invoked when an error is occurred.
     * @return void
     */
    public static function setErrorHandler($callback)
    {
        self::$errorHandler = $callback;
    }
  
    /**
     * Checks whether the error handling is turned on.
     *
     * @return bool
     */
    public static function isErrorHandlingEnabled() : bool
    {
        return self::$errorHandling;
    }
    
    /**
     * Turns on the error handling.
     *
     * @param bool $enable Determines whether the error handling is enabled.
     * @return void
     */
    public static function enableErrorHandling(bool $enable = true)
    {
        if ($enable)
        {
            if (!self::$errorHandling)
            {
                ini_set('display_errors', 0);
                set_exception_handler([__CLASS__, 'exception']);
                set_error_handler(function($errno, $errstr, $errfile, $errline)
                {
                    if (error_reporting() & $errno)
                    {
                        Aleph::exception(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
                    }
                },
                self::getErrorLevel());
                self::$errorHandling = true;
            }
        }
        else if (self::$errorHandling)
        {
            ini_set('display_errors', !empty(self::$config['debugging']));
            restore_error_handler();
            restore_exception_handler();
            self::$errorHandling = false;
        }
    }
    
    /**
     * Turns off the error handling.
     *
     * @param bool $enable Determines whether the error handling is disabled.
     * @return void
     */
    public static function disableErrorHandling(bool $disable = true)
    {
        self::enableErrorHandling(!$disable);
    }
    
    /**
     * Returns the current error level.
     *
     * @return int
     */
    public static function getErrorLevel() : int
    {
        return error_reporting();
    }
  
    /**
     * Sets error reporting level.
     *
     * @param int $level The error reporting level.
     * @return void
     */
    public static function setErrorLevel(int $level)
    {
        error_reporting($level);
    }
  
    /**
     * Set the debug output for an exception.
     *
     * @param \Throwable $e
     * @return void
     */
    public static function exception(\Throwable $e)
    {
        static $inErrorHandler = false;
        static $inLogger = false;
        self::disableErrorHandling();
        self::enableErrorHandling();
        $info = self::analyzeException($e);
        if (!empty(self::$config['logging']))
        {
            try
            {
                if (!$inLogger)
                {
                    $inLogger = true;
                    if (self::$logger)
                    {
                        self::call(self::$logger, $info);
                    }
                    else
                    {
                        self::log($info);
                    }
                    $inLogger = false;
                }
            }
            catch (\Throwable $e)
            {
                $info = self::analyzeException($e);
            }
        }
        if (self::$errorHandler)
        {
            try
            {
                if (!$inErrorHandler)
                {
                    $inErrorHandler = true;
                    if (!self::call(self::$errorHandler, $e, $info))
                    {
                        $inErrorHandler = false;
                        return;
                    }
                    $inErrorHandler = false;
                }
            }
            catch (\Throwable $e)
            {
                $info = self::analyzeException($e);
            }
        }
        $debug = empty(self::$config['debugging']) ? false : true;
        if (PHP_SAPI === 'cli')
        {
            if ($debug)
            {
                $output = PHP_EOL . PHP_EOL . 'BUG REPORT' . PHP_EOL . PHP_EOL;
                $output .= 'The following error [[ ' . $info['message'] . ' ]] has been catched in file ' . $info['file'] . ' on line ' . $info['line'] . PHP_EOL . PHP_EOL;
                $output .= 'Stack Trace:' . PHP_EOL . $info['traceAsString'] . PHP_EOL . PHP_EOL;
                $output .= 'Execution Time: ' . $info['executionTime'] . ' sec' . PHP_EOL . 'Memory Usage: ' . $info['memoryUsage'] . ' Mb' . PHP_EOL . PHP_EOL;
            }
            else
            {
                $output = self::ERROR_TEMPLATE . PHP_EOL;
            }
        }
        else if ($debug)
        {
            $debugTemplate = empty(self::$config['debugTemplate']) ? null : self::dir(self::$config['debugTemplate']);
            if (!is_file($debugTemplate) || !is_readable($debugTemplate))
            {
                $debugTemplate = self::DEBUG_TEMPLATE;
            }
            $render = function($tpl, $info)
            {
                ${'(_._)'} = $tpl;
                unset($tpl);
                if (is_file(${'(_._)'})) 
                {
                    extract($info);
                    return require(${'(_._)'});
                }
                $info['traceAsString'] = htmlspecialchars($info['traceAsString']);
                extract($info);
                eval('$res = "' . str_replace('"', '\"', ${'(_._)'}) . '";');
                return $res;
            };
            $debugTemplate = $render($debugTemplate, $info);
            if (isset($_SESSION) && (isset($_SERVER['REQUEST_URI']) || isset($_SERVER['PHP_SELF'])))
            {
                $hash = md5(uniqid('', true));
                $_SESSION['__DEBUG_INFORMATION__'][$hash] = $debugTemplate;
                $url = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'];
                $url .= ((strpos($url, '?') !== false) ? '&' : '?') . '__DEBUG_INFORMATION__=' . $hash;
                self::go($url, true);
            }
            else 
            {
                $output = $debugTemplate;
            }
        }
        else
        {
            $errorTemplate = empty(self::$config['errorTemplate']) ? null : self::dir(self::$config['errorTemplate']);
            $output = (is_file($errorTemplate) && is_readable($errorTemplate)) ? file_get_contents($errorTemplate) : self::ERROR_TEMPLATE;
        }
        self::output($output);
    }
    
    /**
     * Displays the debug information about catched errors or exceptions.
     * If there is no catched errors or exception this method do nothing.
     *
     * @param bool $removeDebugInfo Determines whether the debug info should be removed from session.
     * @return void
     */
    public static function showDebugInfo(bool $removeDebugInfo = true)
    {
        if (isset($_GET['__DEBUG_INFORMATION__']) && isset($_SESSION['__DEBUG_INFORMATION__'][$_GET['__DEBUG_INFORMATION__']]))
        {
            $output = $_SESSION['__DEBUG_INFORMATION__'][$_GET['__DEBUG_INFORMATION__']];
            if ($removeDebugInfo)
            {
                unset($_SESSION['__DEBUG_INFORMATION__'][$_GET['__DEBUG_INFORMATION__']]);
            }
            self::output($output);
        }
    }
  
    /**
     * Analyzes an exception.
     *
     * @param \Throwable $e
     * @return array The exception information.
     */
    public static function analyzeException(\Throwable $e) : array
    {
        // Makes an object serializable.
        $makeSerializable = function($obj) use(&$makeSerializable)
        {
            if (is_array($obj))
            {
                $obj = array_diff_key($obj, [
                    'GLOBALS' => true,
                    '_REQUEST' => true,
                    '_GET' => true,
                    '_POST' => true,
                    '_FILES' => true,
                    '_COOKIE' => true,
                    '_SERVER' => true,
                    '_ENV' => true,
                    '_SESSION' => true
                ]);
                foreach ($obj as $k => &$v) 
                {
                    $v = $makeSerializable($v);
                }
                return $obj;
            }
            if (is_object($obj))
            {
                try
                {
                    serialize($obj);
                }
                catch (\Throwable $e)
                {
                    $tmp = new \stdClass;
                    $tmp->object = get_class($obj);
                    $tmp->properties = get_object_vars($obj);
                    foreach ($tmp->properties as &$v)
                    {
                        $v = $makeSerializable($v);
                    }
                    $obj = $tmp;
                }
            }
            else if (is_resource($obj))
            {
                $obj = 'Resource: ' . get_resource_type($obj);
            }
            return $obj;
        };
        // Removes all repeating information from an object.
        $reduceObject = function($obj) use(&$reduceObject)
        {
            if ($obj === null)
            {
                return 'null';
            }
            if (is_bool($obj))
            {
                return $obj ? 'true' : 'false';
            }
            if (is_object($obj))
            {
                return '${\'' . get_class($obj) . '\'}';
            }
            if (is_resource($obj))
            {
                return '${\'' . $obj . '\'}';
            }
            if (is_array($obj))
            {
                if (count($obj) == 0)
                {
                    return '[]';
                }
                $tmp = []; 
                foreach ($obj as $k => $v) 
                {
                    $k = (string)$k;
                    if ($k == '__DEBUG_INFORMATION__')
                    {
                        continue;
                    }
                    if ($k == 'GLOBALS')
                    {
                        $tmp[] = 'GLOBALS => *RECURSION*';
                    }
                    else
                    {
                        $tmp[] = $k . ' => ' . $reduceObject($v);        
                    }
                }
                return '[ ' . implode(', ', $tmp) . ' ]';
            }
            if (is_string($obj)) 
            {
                if (strlen($obj) > 1024)
                {
                    $obj = substr($obj, 0, 512) . ' ... [fragment missing] ... ' . substr($obj, -512);
                }
                return '"' . addcslashes($obj, '"') . '"';
            }
            return $obj;
        };
        // Remove base application path from the given file path.
        $reducePath = function($file)
        {
            if (strpos($file, Aleph::getRoot()) === 0)
            {
                $file = substr($file, strlen(Aleph::getRoot()) + 1);
            }
            return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $file);
        };
        // Gets fragment of a file.
        $fragment = function($file, $line, &$index, &$command = null, $half = 10)
        {
            $lines = explode("\n", preg_replace('/\r\n|\r/', "\n", (is_file($file) && is_readable($file)) ? file_get_contents($file) : $file));
            $count = count($lines); $line--;
            if ($line + $half > $count)
            {
                $min = max(0, $line - $half);
                $max = $count;
            } 
            else
            {
                $min = max(0, $line - $half);
                $max = min($line + $half, $count);
            }
            $lines = array_splice($lines, $min, $max - $min + 1);
            $index = $line - $min;
            $command = empty($lines[$index]) ? '' : $lines[$index];
            return implode("\n", $lines);
        };
        // Searches the given function in a source file.
        $findFunc = function($func, $line, $code)
        {
            $line--;
            foreach (array_reverse($code) as $part)
            {
                $row = explode("\n", $part);
                if (empty($row[$line]))
                {
                    continue;
                }
                $row = $row[$line];
                $tokens = token_get_all('<?php ' . $row . '?>');
                $k = 0; $n = count($tokens);
                while ($k < $n) 
                {
                    $token = $tokens[$k++];
                    if (is_array($token) && $token[0] == T_STRING && $token[1] == $func)
                    {
                        return $part;
                    }
                }
            }
            return end($code);
        };
        // Gets request headers.
        $request = function()
        {
            if (function_exists('apache_request_headers'))
            {
                return apache_request_headers();
            }
            $headers = [];
            foreach ($_SERVER as $key => $value) 
            {
                if (strpos($key, 'HTTP_') === 0) 
                {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
                }
            }
            return $headers;
        };
        // Gets response headers.
        $response = function()
        {
            if (function_exists('apache_response_headers'))
            {
                return apache_response_headers();
            }
            $headers = [];
            foreach (headers_list() as $header) 
            {
                $header = explode(':', $header);
                $headers[array_shift($header)] = trim(implode(':', $header));
            }
            return $headers;
        };
        // Exceptions analysis.
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTrace();
        $info = [];
        $info['time'] = date('Y-m-d H:i:s:u');
        $info['sessionID'] = session_id();
        $info['isFatalError'] = $e->getCode() == self::FATAL_ERROR_CODE;
        if (self::$eval && (strpos($file, 'eval()\'d') !== false || strpos($message, 'eval()\'d') !== false))
        {
            if (preg_match('/, called in ([^ ]+) on line (\d+)/', $message, $matches))
            {
                $line = $matches[2];
                $message = substr($message, 0, strpos($message, ', called in'));
            }
            else if (preg_match('/, called in ([^\(]+)\((\d+)\) : eval\(\)\'d code on line (\d+)/', $message, $matches))
            {
                $line = $matches[3];
                $message = substr($message, 0, strpos($message, ', called in'));
            }
            $file = 'eval()\'s code';
        }
        else if (preg_match('/, called in ([^ ]+) on line (\d+)/', $message, $matches))
        {
            $file = $matches[1];
            $line = $matches[2];
            $message = substr($message, 0, strpos($message, ', called in'));
        }
        $push = true;
        $reducedFile = $reducePath($file);
        foreach ($trace as $k => &$item)
        {
            $item['command'] = isset($item['class']) ? $item['class'] . $item['type'] : '';
            $item['command'] .= $item['function'] . '( ';
            if (isset($item['args']))
            {
                $tmp = [];
                foreach ($item['args'] as &$arg) 
                {
                    $tmp[] = $reduceObject($arg);
                    $arg = $makeSerializable($arg);
                }
                $item['command'] .= implode(', ', $tmp);
            }
            $item['command'] .= ' )';
            if (isset($item['file']))
            {
                if (self::$eval && strpos($item['file'], 'eval()\'d') !== false)
                {
                    $item['file'] = 'eval()\'s code';
                    if ($item['function'] == '{closure}' && isset($item['args'][0]) && $item['args'][0] == 4096)
                    {
                        $item['code'] = $fragment($findFunc($trace[$k + 1]['function'], $item['line'], self::$eval), $item['line'], $index);
                    }
                    else
                    {
                        $item['code'] = $fragment($findFunc($item['function'], $item['line'], self::$eval), $item['line'], $index);
                    }
                }
                else
                {
                    $item['code'] = $fragment($item['file'], $item['line'], $index);
                    $item['file'] = $reducePath($item['file']);
                }
            }
            else
            {
                $index = 0;
                $item['code'] = '';
                if ($file != 'eval()\'s code') 
                {
                    if (is_file($file)) 
                    {
                        $item['code'] = $fragment($file, $line, $index);
                    }
                }
                else if (self::$eval) 
                {
                    $item['code'] = $fragment(array_pop(self::$eval), $line, $index);
                }
                $item['file'] = '[Internal PHP]';
            }
            $item['index'] = $index;
            if ($item['file'] == $reducedFile && $item['line'] == $line)
            {
                $push = false;
            }
        }
        if ($push && !$info['isFatalError'])
        {
            $code = $fragment($file, $line, $index, $command);
            array_unshift($trace, ['file' => $reducedFile, 'line' => $line, 'command' => $command, 'code' => $code, 'index' => $index]);
        }
        $info['memoryUsage'] = number_format(self::getMemoryUsage() / 1048576, 4);
        $info['executionTime'] = self::getExecutionTime();
        $info['message'] = ltrim($message);
        $info['file'] = $reducedFile;
        $info['line'] = $line;
        $info['trace'] = $makeSerializable($trace);
        $info['class'] = method_exists($e, 'getClass') ? $e->getClass() : '';
        $info['token'] = method_exists($e, 'getToken') ? $e->getToken() : '';
        $info['severity'] = method_exists($e, 'getSeverity') ? $e->getSeverity() : '';
        $info['traceAsString'] = $e->getTraceAsString();
        $info['request'] = $request();
        $info['response'] = $response();
        $info['ENV'] = $_ENV ?? [];
        $info['GET'] = $_GET ?? [];
        $info['POST'] = $_POST ?? [];
        $info['COOKIE'] = $_COOKIE ?? [];
        $info['FILES'] = $_FILES ?? [];
        $info['SERVER'] = $_SERVER ?? [];
        $info['SESSION'] = $_SESSION ?? [];
        $info['GLOBALS'] = $makeSerializable($GLOBALS);
        unset($info['SESSION']['__DEBUG_INFORMATION__']);
        unset($info['SESSION']['__VS__']);
        return $info;
    }
  
    /**
     * Collects and stores information about some eval's code. 
     *
     * @param string $code The code that will be executed by eval operator.
     * @return string
     */
    public static function ecode(string $code) : string
    {
        self::$eval[md5($code)] = $code;
        return $code;
    }
  
    /**
     * Executes PHP code that inserted into HTML.
     *
     * @param string $code The PHP inline code.
     * @param array $vars Variables to extract to the PHP code.
     * @return string
     */
    public static function exe(string $code, array $vars = []) : string
    {
        ${'(_._)'} = ' ?>' . $code . '<?php ';
        unset($code);
        if ($vars) 
        {
            extract($vars);
        }
        ob_start();
        ob_implicit_flush(false);
        eval(self::ecode(${'(_._)'}));
        $res = ob_get_clean();
        if (strpos($res, 'eval()\'d') !== false)
        {
            exit($res);  
        }
        return $res;
    }
  
    /**
     * Returns the canonicalized absolute pathname of a directory specified by its alias. 
     * The resulting path will have no symbolic link, '/./' or '/../' components and extra '/' characters.
     * 
     * @param string $dir The directory alias.
     * @return string
     */
    public static function dir(string $dir) : string
    {
        if ($dir === '')
        {
            return self::$root;
        }
        if ($dir[0] == '@')
        {
            $last = strpbrk($dir, '\\/');
            if ($last === false)
            {
                $dir = substr($dir, 1);
                $dir = self::$config['dirs'][$dir] ?? $dir;
            }
            else
            {
                $dir = substr($dir, 1, -strlen($last));
                $dir = self::$config['dirs'][$dir] ?? $dir;
                $dir .= $last;
            }
            return self::dir($dir);
        }
        if (DIRECTORY_SEPARATOR == '\\' && !preg_match('/^([a-zA-Z]:\\\\?|\\\\).*/', $dir) || $dir[0] != '/')
        {
            if (strpos($dir, self::$root) !== 0) 
            {
                $dir = self::$root . DIRECTORY_SEPARATOR . $dir;
            }
        }
        if (file_exists($dir)) 
        {
            return realpath($dir);
        }
        $unipath = strlen($dir) == 0 || $dir[0] != '/';
        if (strpos($dir, ':') === false && $unipath)
        {
            $dir = getcwd() . DIRECTORY_SEPARATOR . $dir;
        }
        $dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $dir), 'strlen');
        $absolutes = [];
        foreach ($parts as $part)
        {
            if ($part === '.')
            {
                continue;
            }
            if ($part === '..')
            {
                array_pop($absolutes);
            }
            else
            {
                $absolutes[] = $part;
            }
        }
        $dir = implode(DIRECTORY_SEPARATOR, $absolutes);
        if (file_exists($dir) && linkinfo($dir) > 0)
        {
            $dir = readlink($dir);
        }
        return $unipath ? $dir : '/' . $dir;
    }
  
    /**
     * Returns a directory url relative to the site root.
     *
     * @param string $dir The directory alias.
     * @return string
     * @throws \LogicException If an URL cannot be constructed for the given directory. 
     */
    public static function url(string $dir) : string
    {
        if ($dir === '')
        {
            return '/';
        }
        if ($dir[0] == '@')
        {
            $dir = self::dir($dir);
            if (strpos($dir, self::$root) !== 0)
            {
                throw new \LogicException(sprintf(self::ERR_5, $dir));
            }
            $dir = substr($dir, strlen(self::$root));
        }
        return '/' . str_replace('\\', '/', ltrim($dir, '\\/'));
    }
    
    /**
     * Returns custom log handler or NULL if not specified.
     *
     * @return mixed
     */
    public static function getLogger()
    {
        return self::$logger;
    }
    
    /**
     * Sets custom log handler.
     *
     * @param mixed $callback A user-defined callback that will be automatically invoked when an error is logging.
     * @return void
     */
    public static function setLogger($callback)
    {
        self::$logger = $callback;
    }
  
    /**
     * Logs some data into log files.
     *
     * @param mixed $data Some data to log.
     * @return void
     */
    public static function log($data)
    {
        $path = self::dir('@logs') . '/' . date('Y F');
        if (is_dir($path) || mkdir($path, 0711, true))
        {
            file_put_contents($path . '/' . date('d H.i.s#') . microtime(true) . '.log', serialize($data));
        }
    }
  
    /**
     * Performs redirect to given URL.
     *
     * @param string $url
     * @param bool $isNewWindow Determines whether the new window should be opened.
     * @param bool $immediately Determines whether the redirect should immediately happen.
     * @return void
     */
    public static function go($url, $inNewWindow = false, $immediately = true)
    {
        $url = addslashes($url);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        {
            if ($inNewWindow)
            {
                $output = 'window.open(\'' . $url . '\');';
            }
            else
            {
                $output = 'window.location.assign(\'' . $url . '\');';
            }
        }
        else
        {
            if ($inNewWindow)
            {
                $output = '<script type="text/javascript">window.open(\'' . $url . '\');</script>';
            }
            else
            {
                $output = '<script type="text/javascript">window.location.assign(\'' . $url . '\');</script>';
            }
        }
        self::output($output, $immediately);
    }
  
    /**
     * Performs the page reloading.
     *
     * @param bool $immediately Determines whether the page should be immediately reloaded.
     * @param bool $forceGet Specifies the type of reloading: FALSE (default) - reloads the current page from the cache, TRUE - reloads the current page from the server.
     * @return void
     */
    public static function reload($immediately = true, $forceGet = false)
    {
        $forceGet = $forceGet ? 'true' : 'false';
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        {
            $output = 'window.location.reload(' . $forceGet . ');';
        }
        else 
        {
            $output = '<script type="text/javascript">window.location.reload(' . $forceGet . ');</script>';
        }
        self::output($output, $immediately);
    }
    
    /**
     * Returns the default cache object.
     *
     * @return \Aleph\Cache\Cache
     */
    public static function getCache() : Cache\Cache
    {
        if (self::$cache === null)
        {
            self::$cache = Cache\Cache::getInstance();
        }
        return self::$cache;
    }
  
    /**
     * Sets the default cache object.
     *
     * @param \Aleph\Cache\Cache $cache
     * @return void
     */
    public static function setCache(Cache\Cache $cache)
    {
        self::$cache = $cache;
    }
    
    /**
     * Creates the class map.
     *
     * @return int The number of all found classes.
     */
    public static function createClassMap() : int
    {
        return self::find();
    }
  
    /**
     * Returns array of class paths.
     *
     * @return string[]
     */
    public static function getClassMap() : array
    {
        $classmap = empty(self::$config['autoload']['classmap']) ? null : self::dir(self::$config['autoload']['classmap']);
        self::$classes = file_exists($classmap) ? (array)require($classmap) : [];
        return self::$classes;
    }
    
    /**
     * Sets array of class paths.
     *
     * @param string[] $classes An array of paths to the class files.
     * @param string $classmap The path to the class map file.     
     * @return void
     * @throws \LogicException If the given classmap file (or config variable autoload.classmap) is not defined.
     */
    public static function setClassMap(array $classes, string $classmap = '')
    {
        $file = $classmap ? self::dir($classmap) : (empty(self::$config['autoload']['classmap']) ? null : self::dir(self::$config['autoload']['classmap']));;
        if (!$file) 
        {
            throw new \LogicException(self::ERR_3);
        }
        $code = [];
        foreach ($classes as $class => $path) 
        {
            if (strlen($class) == 0 || !file_exists(self::dir($path)))
            {
                continue;
            }
            $code[] = "'" . strtolower($class) . "' => '" . str_replace("'", "\'", $path) . "'";
        }
        if (count($code) == 0)
        {
            file_put_contents($file, '<?php return [];');
        }
        else
        {
            file_put_contents($file, '<?php return [' . PHP_EOL . '  ' . implode(',' . PHP_EOL . '  ', $code) . PHP_EOL . '];');
        }
        self::$config['autoload']['classmap'] = $classmap;
        self::$classes = $classes;
    }
    
    /**
     * Searches a class, interface or trait and includes it to the script.
     * Returns FALSE if the given class does not exist and TRUE otherwise.
     *
     * @param string $class A class, an interface or a trait to search and include.
     * @param bool $throwException Determines whether an exception should be thrown if a class is not found.
     * @return bool
     * @throws \RuntimeException When a class is not found and $throwException is TRUE.
     */
    public static function loadClass(string $class, bool $throwException = false) : bool
    {
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false))
        {
            return true;
        }
        $cs = strtolower(ltrim($class, '\\'));
        // Classmap loader.
        $classes = self::getClassMap();
        if (isset($classes[$cs]))
        {
            $file = self::dir($classes[$cs]);
            if (is_file($file))
            {
                require_once($file);
                if (class_exists($cs, false) || interface_exists($cs, false) || trait_exists($cs, false)) 
                {
                    return true;
                }
            }
        }
        // PSR-4 loader.
        if (empty(self::$config['autoload']['namespaces']['Aleph'])) 
        {
            self::$config['autoload']['namespaces'] = array_merge(['Aleph' => __DIR__], isset(self::$config['autoload']['namespaces']) ? (array)self::$config['autoload']['namespaces'] : []);
        }
        $namespaces = self::$config['autoload']['namespaces'];
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\'))
        {
            $prefix = substr($class, 0, $pos);
            if (isset($namespaces[$prefix]))
            {
                $cs = substr($class, $pos + 1);
                foreach ((array)$namespaces[$prefix] as $dir)
                {
                    $file = self::dir($dir) . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $cs) . '.php';
                    if (file_exists($file))
                    {
                        require_once($file);
                        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false))
                        {
                            return true;
                        }
                    }
                }
            }
        }
        if (empty(self::$config['autoload']['search']))
        {
            if ($throwException)
            {
                throw new \RuntimeException(sprintf(self::ERR_1, $class));
            }
            return false;
        }
        // Search class.
        if (self::find($cs)) 
        {
            return true;
        }
        if ($throwException)
        {
            throw new \RuntimeException(sprintf(self::ERR_1, $class));
        }
        return false;
    }
    
    /**
     * Finds a class or interface to include into your PHP script.
     *
     * @param string $class
     * @param array $options An array of additional search parameters.
     * @return int|bool Number of found classes if $class is not specified, TRUE - if $class is found and FALSE if it isn't. 
     * @throws \LogicException If classmap is not defined or some class definition is duplicated.
     */
    private static function find(string $class = '', array $options = [])
    {
        if ($options) 
        {
            $paths = [$options['path'] => true];
            $exclusions = $options['exclusions'];
        }
        else
        {
            $classmap = empty(self::$config['autoload']['classmap']) ? '' : self::dir(self::$config['autoload']['classmap']);
            if (!$classmap)
            {
                throw new \LogicException(self::ERR_3);
            }
            if (file_exists($classmap) && (require($classmap)) === false)
            {
                $seconds = 0;
                $timeout = self::$config['autoload']['timeout'] ?? 300;
                while (($classes = require($classmap)) === false && ++$seconds <= $timeout) sleep(1);
                if ($seconds <= $timeout)
                {
                    if (isset($classes[$class]) && is_file($classes[$class]))
                    {
                        require_once($classes[$class]);
                        return class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false);
                    }
                    return false;
                }
                // if we wait more than $timeout seconds then it's probably something went wrong and we should try to perform searching again.
                file_put_contents($classmap, '<?php return [];');
                return false;
            }
            else
            {
                file_put_contents($classmap, '<?php return false;');
            }
            $exclusions = empty(self::$config['autoload']['exclusions']) ? [] : (array)self::$config['autoload']['exclusions'];
            foreach ($exclusions as &$item)
            {
                $item = self::dir($item);
            }
            unset($item);
            $paths = empty(self::$config['autoload']['directories']) ? [] : (array)self::$config['autoload']['directories'];
            self::$classes = $tmp = [];
            foreach ($paths as $item => $flag)
            {
                $tmp[self::dir($item)] = $flag;
            }
            $paths = count($tmp) ? $tmp : [self::$root => true];
            $first = true;
        }
        foreach ($paths as $path => $searchRecursively)
        {
            foreach (scandir($path) as $item)
            {
                if ($item === '.' || $item === '..' || $item === '.svn' || $item === '.hg' || $item === '.git')
                {
                    continue; 
                }
                $file = $path . DIRECTORY_SEPARATOR . $item;
                if (in_array($file, $exclusions))
                {
                    continue;
                }
                if (is_file($file))
                {
                    if (isset(self::$config['autoload']['mask']) && !preg_match(self::$config['autoload']['mask'], $item))
                    {
                        continue;
                    }
                    $tokens = token_get_all(file_get_contents($file));
                    for ($i = 0, $max = count($tokens), $namespace = ''; $i < $max; $i++)
                    {
                        $token = $tokens[$i];
                        if (is_string($token))
                        {
                            continue;
                        }
                        switch ($token[0])            
                        {
                            case T_NAMESPACE:
                                $namespace = '';
                                for (++$i; $i < $max; $i++)
                                {
                                    $t = $tokens[$i];
                                    if (is_string($t))
                                    {
                                        break;
                                    }
                                    if ($t[0] == T_STRING || $t[0] == T_NS_SEPARATOR)
                                    {
                                        $namespace .= $t[1];
                                    }
                                }
                                $namespace .= '\\';
                                break;
                            case T_CLASS:
                            case T_INTERFACE;
                            case T_TRAIT:
                                for (++$i; $i < $max; $i++)
                                {
                                    $t = $tokens[$i];
                                    if (!is_array($t))
                                    {
                                        continue 2;
                                    }
                                    if ($t[0] == T_STRING)
                                    {
                                        break;
                                    }
                                }
                                $cs = strtolower(ltrim($namespace . $t[1], '\\'));
                                if (!empty(self::$config['autoload']['unique']) && isset(self::$classes[$cs])) 
                                {
                                    $normalize = function($dir)
                                    {
                                        return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $dir);
                                    };
                                    file_put_contents(self::dir(self::$config['autoload']['classmap']), '<?php return [];');
                                    throw new \LogicException(sprintf(self::ERR_2, ltrim($namespace . $t[1], '\\'), $normalize(self::dir(self::$classes[$cs])), $normalize($file)));
                                }
                                self::$classes[$cs] = strpos($file, self::$root) === 0 ? ltrim(substr($file, strlen(self::$root)), DIRECTORY_SEPARATOR) : $file;
                                break;
                        }
                    }
                }
                else if ($searchRecursively && is_dir($file))
                {
                    self::find($class, ['path' => $file, 'exclusions' => $exclusions]);
                }
            }
        }
        if (isset($first)) 
        {
            self::setClassMap(self::$classes, self::$config['autoload']['classmap'] ?? '');
            if ($class !== null)
            {
                if (isset(self::$classes[$class]))
                {
                    $file = self::dir(self::$classes[$class]);
                    require_once($file);
                    return (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false));
                }
                return false;
            }
            return count(self::$classes);
        }
    }
  
    /**
     * Protects against creation through "new".
     *
     * @return void
     */
    private function __construct(){}
    
    /**
     * Protects against creation through "clone".
     *
     * @return void
     */
    private function __clone(){}
    
    /**
     * Protects against creation through "unserialize".
     *
     * @return void
     */
    private function __wakeup(){}
}