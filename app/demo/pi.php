<?php

use Aleph\Utils;

require_once(__DIR__ . '/../../connect.php');

Utils\Process::operate();

class PI
{
  public static function start()
  {
    Utils\Process::$php = 'C:\xampp\php\php.exe';
    // Start processes.
    $numproc = 50; $processes = $parts = [];
    for ($i = 0; $i < $numproc; $i++)
    {
      $process = new Utils\Process('PI::calculatePart');
      $process->start(['max' => mt_rand(1000000, 10000000)]);
      $processes[] = $process;
    }
    // Wait until all the processes are done.
    while (count($parts) < $numproc)
    {
      foreach ($processes as $n => $process)
      {
        if (empty($results[$n]) && !$process->isRunning())
        {
          if ($process->isError())
          {
            // Gets error info. 
            $info = $process->read();
            // Stops all process.
            foreach ($processes as $n => $process) $process->stop();
            // Outputs error message.
            echo 'Process #' . $n . ' was terminated. Error: ' . $info['message'];
            return;
          }
          // Gets calculated data.
          $parts[$n] = $process->read();
          // Cleans shared memory.
          $process->clean();
        }
      }
      usleep(100000);
    }
    // Calculate PI number.
    echo 'PI = ' . self::calculate($parts);
  }
  
  public static function calculate(array $parts)
  {
    $target = $count = 0;
    foreach ($parts as $part) 
    {
      $target += $part['target'];
      $count += $part['count'];
    }
    return 4 * $target / $count;
  }
  
  public static function calculatePart(Utils\Process $process)
  {
    $max = $process->data['max'];
    for ($i = $j = 0; $i < $max; $i++)
    {
      $x = mt_rand() / mt_getrandmax();
      $y = mt_rand() / mt_getrandmax();
      if ($x * $x + $y * $y <= 1) $j++;
    }
    $process->write(['count' => $max, 'target' => $j]);
    exit;
  }
}

PI::start();