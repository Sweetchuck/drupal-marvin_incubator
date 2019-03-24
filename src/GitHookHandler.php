<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

class GitHookHandler {

  /**
   * @var string
   */
  protected $rootProjectDir = '';

  /**
   * @var string
   */
  protected $composerExecutable = '';

  /**
   * @var string
   */
  protected $marvinIncubatorDir = '';

  /**
   * @var string
   */
  protected $packagePath = '';

  /**
   * @var string
   */
  protected $gitHook = '';

  /**
   * @var string
   */
  protected $drushCommand = '';

  /**
   * @var string
   */
  protected $binDir = '';

  /**
   * @var string
   */
  protected $vendorDir = '';

  /**
   * @var resource
   */
  protected $stdOutput;

  /**
   * @var resource
   */
  protected $stdError;

  /**
   * @var array
   */
  protected $cliArgs = [];

  /**
   * @param resource $stdOutput
   * @param resource $stdError
   */
  public function __construct($stdOutput = NULL, $stdError = NULL) {
    $this->stdOutput = $stdOutput ?? STDOUT;
    $this->stdError = $stdError ?? STDERR;
  }

  /**
   * @return $this
   */
  public function init(array $cliArgs, string $packagePath, string $rootProjectDir, string $composerExecutable, string $marvinIncubatorDir) {
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

  /**
   * @return $this
   */
  protected function initGitHook() {
    $this->gitHook = basename($this->cliArgs[0]);

    return $this;
  }

  /**
   * @return $this
   */
  protected function initDrushCommand() {
    $this->drushCommand = "marvin:git-hook:{$this->gitHook}";

    return $this;
  }

  /**
   * @return $this
   */
  protected function changeDirToRootProject() {
    chdir($this->rootProjectDir);

    return $this;
  }

  /**
   * @return $this
   */
  protected function initBinDir() {
    $output = exec(sprintf(
      '%s config bin-dir 2>/dev/null',
      escapeshellcmd($this->composerExecutable)
    ));

    $this->binDir = $this->getLastLine($output);

    return $this;
  }

  /**
   * @return $this
   */
  protected function initVendorDir() {
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

  /**
   * @return $this
   */
  public function writeHeader() {
    $this->logError("BEGIN {$this->gitHook}");

    return $this;
  }

  /**
   * @return $this
   */
  public function writeFooter() {
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

  /**
   * @return $this
   */
  protected function logOutput(string $message) {
    $this->log($this->stdOutput, $message);

    return $this;
  }

  /**
   * @return $this
   */
  protected function logError(string $message) {
    $this->log($this->stdError, $message);

    return $this;
  }

  /**
   * @param resource $output
   * @param string $message
   *
   * @return $this
   */
  protected function log($output, string $message) {
    fwrite($output, $message . PHP_EOL);

    return $this;
  }

  protected function getLastLine(string $string): string {
    $lines = preg_split('/[\n\r]+/', trim($string));

    return end($lines);
  }

}
