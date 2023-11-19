<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Helper;

use Symfony\Component\Process\Process;

class ProcessResult {

  public ?int $exitCode = NULL;

  public string $stdOutput = '';

  public string $stdError = '';

  /**
   * @return static
   */
  public static function createFromProcess(Process $process) {
    // @phpstan-ignore-next-line
    $result = new static();
    $result->exitCode = $process->getExitCode();
    $result->stdOutput = $process->getOutput();
    $result->stdError = $process->getErrorOutput();

    return $result;
  }

}
