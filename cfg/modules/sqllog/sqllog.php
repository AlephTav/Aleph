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

namespace Aleph\Configuration;

/**
 * Module for reviewing and management of SQL log. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
class SQLLog extends Module
{
  /**
   * Performs the given command.
   *
   * @param string $command - the command name.
   * @param array $args - the command arguments.
   * @access public
   * @abstract
   */
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'search':
        if (empty($args['options']) || !is_array($args['options'])) 
        {
          $where = ['all' => -1, 'url' => 0, 'dsn' => 1, 'sql' => 2, 'type' => 3, 'mode' => 4, 'duration' => 5];
          $options = ['where' => isset($args['where']) && isset($where[$args['where']]) ? $where[$args['where']] : -1,
                      'mode' => isset($args['mode']) ? $args['mode'] : 'regular',
                      'onlyWholeWord' => isset($args['onlyWholeWord']) ? (int)$args['onlyWholeWord'] : 0,
                      'caseSensitive' => isset($args['caseSensitive']) ? (int)$args['caseSensitive'] : 0,
                      'from' => isset($args['from']) ? $args['from'] : '',
                      'to' => isset($args['to']) ? $args['to'] : ''];
        }
        else
        {
          $options = $args['options'];
        }
        $data = $this->searchSQL(isset($args['keyword']) ? $args['keyword'] : '', $options);
        if (Configurator::isCLI()) $this->write(PHP_EOL . print_r($data, true)); 
        else echo $this->render(__DIR__ . '/html/search.html', ['data' => $data]);
        break;
      case 'clean':
        if (isset(\Aleph::getInstance()['db']['log']))
        {
          $file = \Aleph::dir(\Aleph::getInstance()['db']['log']);
          if (is_file($file)) unlink($file);
        }
        $this->write(PHP_EOL . 'The SQL log has been successfully removed.' . PHP_EOL);
        break;
      default:
        $this->showCommandHelp();
        break;
    }
  }
  
  /**
   * Returns HTML/CSS/JS data for the module GUI.
   *
   * @access public
   * @return array
   */
  public function getData()
  {
    return ['js' => 'sqllog/js/sqllog.js', 'html' => 'sqllog/html/sqllog.html'];
  }

  /**
   * Returns command help of the module.
   *
   * @return string
   * @access public
   */
  public function getCommandHelp()
  {
    return <<<HELP

\e[33mAllows to search data in the SQL log file and clean it.\e[0m

\e[36mThe use cases:\e[0m

    1. \e[32mcfg sqllog clean\e[0m
    
       Cleans the SQL log file.
       
    2. \e[32mcfg sqllog search [--keyword KEYWORD] [--where all|url|dsn|sql|type|mode|duration] [--mode regular|regexp] [--caseSensitive 0|1] [--onlyWholeWord 0|1] [--from FROM] [--to TO]\e[0m
    
       Searches data in the SQL log.

HELP;
  }
  
  /**
   * Searches data in the sql log.
   *
   * @param string $keyword - the search keyword.
   * @param array $options - the search parameters.
   * @return array
   * @access private
   */
  private function searchSQL($keyword, array $options)
  {
    if (isset(\Aleph::getInstance()['db']['log'])) $file = \Aleph::dir(\Aleph::getInstance()['db']['log']);
    if (empty($file) || !is_file($file)) return [];
    $tmp = [];
    $fh = fopen($file, 'r');
    if (!$fh) return [];
    fgetcsv($fh);
    if ($keyword == '' && strlen($options['from']) == 0 && strlen($options['to']) == 0 && $options['mode'] != 'regexp')
    {
      while (($row = fgetcsv($fh)) !== false) $tmp[] = $row;
    }
    else
    {
      if ($options['mode'] == 'regexp' && @preg_match($keyword, '') === false) return ['error' => 'Regular expression is wrong'];
      $isMatched = function($n, $value) use ($keyword, $options)
      {
        if ($options['mode'] == 'regexp') return preg_match($keyword, $value);
        if ($n >= 4 && $n <= 7)
        {
          if (strlen($options['from']) == 0 && strlen($options['to']) == 0) return $keyword == $value;
          return (strlen($options['from']) == 0 || $options['from'] <= $value) && (strlen($options['to']) == 0 || $options['to'] >= $value);
        }
        if ($options['onlyWholeWord']) 
        {
          if ($options['caseSensitive']) return (string)$keyword === (string)$value;
          return strcasecmp($keyword, $value) == 0;
        }
        if ($options['caseSensitive']) return strpos($value, $keyword) !== false;
        return strripos($value, $keyword) !== false;
      };
      while (($row = fgetcsv($fh)) !== false)
      {
        foreach ($row as $n => $value)
        {
          if (($options['where'] < 0 || $n == $options['where']) && $isMatched($n, $value))
          {
            $tmp[] = $row;
            break;
          }
        }
      }
    }
    fclose($fh);
    return $tmp;
  }
}