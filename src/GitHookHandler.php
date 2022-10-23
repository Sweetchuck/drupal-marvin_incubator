<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

class GitHookHandler {

  protected string $rootProjectDir = '';

  protected string $composerExecutable = '';

  protected string $marvinIncubatorDir = '';

  protected string $packagePath = '';

  protected string $gitHook = '';

  protected string $drushCommand = '';

  protected string $binDir = '';

  protected string $vendorDir = '';

  /**
   * @var resource
   */
  protected $stdOutput;

  /**
   * @var resource
   */
  protected $stdError;

  protected array $cliArgs = [];

  /**
   * @param resource $stdOutput
   * @param resource $stdError
   */
  public function __construct($stdOutput = NULL, $stdError = NULL) {
    $this->stdOutput = $stdOutput ?? STDOUT;
    $this->stdError = $stdError ?? STDERR;
  }

  public function init(
    array $cliArgs,
    string $packagePath,
    string $rootProjectDir,
    string $composerExecutable,
    string $marvinIncubatorDir,
  ): static {
    $this->cliArgs = $cliArgs;
    $this->packagePath = $packagePath;
    $this->rootProjectDir = $rootProjectDir;
    $this->composerExecutable = $composerExecutable;
    $this->marvinIncubatorDir = $marvinIncubatorDir;

    return $this
      ->initGitHook()
      ->initDrushCommand()
      ->changeDirToRootProject()
      ->initBinDir();
  }

  protected function initGitHook(): static {
    $this->gitHook = basename($this->cliArgs[0]);

    return $this;
  }

  protected function initDrushCommand(): static {
    $this->drushCommand = "marvin:git-hook:{$this->gitHook}";

    return $this;
  }

  protected function changeDirToRootProject(): static {
    chdir($this->rootProjectDir);

    return $this;
  }

  protected function initBinDir(): static {
    $output = exec(sprintf(
      '%s config bin-dir 2>/dev/null',
      escapeshellcmd($this->composerExecutable)
    ));

    $this->binDir = $this->getLastLine($output);

    return $this;
  }

  protected function initVendorDir(): static {
    $output = exec(sprintf(
      '%s config vendor-dir 2>/dev/null',
      escapeshellcmd($this->composerExecutable)
    ));

    $this->vendorDir = $this->getLastLine($output);

    return $this;
  }

  public function doIt(): ?array {
    if (!$this->isGitHookExists()) {
      $this->logError("There is no corresponding 'drush marvin:git-hook:{$this->gitHook}' command.");

      return NULL;
    }

    return $this
      ->initVendorDir()
      ->getContext();
  }

  public function writeHeader(): static {
    $this->logError("BEGIN {$this->gitHook}");

    return $this;
  }

  public function writeFooter(): static {
    $this->logError("END   {$this->gitHook}");

    return $this;
  }

  protected function isGitHookExists(): bool {
    $cmdPattern = '%s --config=%s --config=%s --include=%s help %s 2>&1';
    $cmdArgs = [
      escapeshellcmd("{$this->binDir}/drush"),
      escapeshellarg('drush'),
      escapeshellarg("{$this->marvinIncubatorDir}/Commands"),
      escapeshellarg($this->marvinIncubatorDir),
      escapeshellarg($this->drushCommand),
    ];

    $output = NULL;
    $exitCode = NULL;
    exec(vsprintf($cmdPattern, $cmdArgs), $output, $exitCode);

    return $exitCode === 0;
  }

  protected function getContext(): array {
    $args = $this->cliArgs;
    array_shift($args);

    return [
      'cliArgs' => array_merge(
        [
          "{$this->binDir}/drush",
          "--define=marvin.gitHook={$this->gitHook}",
          '--config=drush',
          "--config={$this->marvinIncubatorDir}/Commands",
          "--include={$this->marvinIncubatorDir}",
          $this->drushCommand,
        ],
        [
          $this->packagePath,
        ],
        $args
      ),
      'pathToDrushPhp' => "{$this->vendorDir}/drush/drush/drush.php",
    ];
  }

  protected function logOutput(string $message): static {
    $this->log($this->stdOutput, $message);

    return $this;
  }

  protected function logError(string $message): static {
    $this->log($this->stdError, $message);

    return $this;
  }

  /**
   * @param resource $output
   * @param string $message
   */
  protected function log($output, string $message): static {
    fwrite($output, $message . PHP_EOL);

    return $this;
  }

  protected function getLastLine(string $string): string {
    $lines = preg_split('/[\n\r]+/', trim($string));
    $last = end($lines);

    return $last === FALSE ?
      ''
      : $last;
  }

}
