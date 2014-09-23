<?php

namespace Aleph\Configurator;

class SQLLog extends Module
{
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
        if (Configurator::isCLI()) print_r($data); 
        else echo self::render(__DIR__ . '/html/search.html', ['data' => $data]);
        break;
      case 'clean':
        if (isset(Configurator::getAleph()['db']['log']))
        {
          $file = \Aleph::dir(Configurator::getAleph()['db']['log']);
          unlink($file);
        }
        if (Configurator::isCLI()) echo PHP_EOL . 'The SQL log has been successfully removed.' . PHP_EOL;
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['js' => 'sqllog/js/sqllog.js', 'html' => 'sqllog/html/sqllog.html'];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to search data in the SQL log file and clean it.

The use cases:

    1. cfg sqllog clean
    
       Cleans the SQL log file.
       
    2. cfg sqllog search [--keyword KEYWORD] [--where all|url|dsn|sql|type|mode|duration] [--mode regular|regexp] [--caseSensitive 0|1] [--onlyWholeWord 0|1] [--from FROM] [--to TO]
    
       Searches data in the SQL log.

HELP;
  }
  
  private function searchSQL($keyword, array $options)
  {
    if (isset(Configurator::getAleph()['db']['log'])) $file = \Aleph::dir(Configurator::getAleph()['db']['log']);
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