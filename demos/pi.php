<?php

use Aleph\Utils;

require_once(__DIR__ . '/../connect.php');

$process = Utils\Process::operate();

class PI
{
  public static function start()
  {
    Utils\Process::$php = 'C:\xampp\php\php.exe';
    // Start processes.
    $numproc = 10; $processes = $parts = [];
    for ($i = 0; $i < $numproc; $i++)
    {
      $process = new Utils\Process('PI::calculatePart');
      $process->start(mt_rand(10000000, 100000000));
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
        usleep(100000);
      }
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
    return $target / $count * 4;
  }
  
  public static function calculatePart(Utils\Process $process)
  {
    $max = $process->data;
    $randmax = mt_getrandmax();
    $randmax2 = $randmax * $randmax;
    for ($i = $j = 0; $i < $max; $i++)
    {
      $x = mt_rand(); $y = mt_rand();
      if ($x + $y <= $randmax || $x * $x + $y * $y <= $randmax2) $j++;
    }
    $process->write(['count' => $max, 'target' => $j]);
    exit;
  }
}

if (!$process) PI::start();