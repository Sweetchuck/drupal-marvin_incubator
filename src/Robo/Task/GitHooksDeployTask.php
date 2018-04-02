<?php

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin_incubator\Utils;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\OutputAwareInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class GitHooksDeployTask extends BaseTask implements
    ContainerAwareInterface,
    OutputAwareInterface {

  use ContainerAwareTrait;
  use IO;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var string
   */
  protected $packagePath = '';

  public function getPackagePath(): string {
    return $this->packagePath;
  }

  /**
   * @return $this
   */
  public function setPackagePath(string $value) {
    $this->packagePath = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $hookFilesSourceDir = '';

  public function getHookFilesSourceDir(): string {
    return $this->hookFilesSourceDir;
  }

  /**
   * @return $this
   */
  public function setHookFilesSourceDir(string $value) {
    $this->hookFilesSourceDir = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $commonTemplateFileName = '';

  public function getCommonTemplateFileName(): string {
    return $this->commonTemplateFileName;
  }

  /**
   * @return $this
   */
  public function setCommonTemplateFileName(string $value) {
    $this->commonTemplateFileName = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $rootProjectDir = '';

  public function getRootProjectDir(): string {
    return $this->rootProjectDir;
  }

  /**
   * Absolute path to the project root dir.
   *
   * @return $this
   */
  public function setRootProjectDir(string $value) {
    $this->rootProjectDir = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $composerExecutable = 'composer';

  public function getComposerExecutable(): string {
    return $this->composerExecutable;
  }

  /**
   * @return $this
   */
  public function setComposerExecutable(string $value) {
    $this->composerExecutable = $value;

    return $this;
  }

  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('packagePath', $options)) {
      $this->setPackagePath($options['packagePath']);
    }

    if (array_key_exists('hookFilesSourceDir', $options)) {
      $this->setHookFilesSourceDir($options['hookFilesSourceDir']);
    }

    if (array_key_exists('commonTemplateFileName', $options)) {
      $this->setCommonTemplateFileName($options['commonTemplateFileName']);
    }

    if (array_key_exists('composerExecutable', $options)) {
      $this->setComposerExecutable($options['composerExecutable']);
    }

    return $this;
  }

  protected function runPrepare() {
    parent::runPrepare();

    $this->fs = new Filesystem();

    return $this;
  }

  protected function runHeader() {
    $this->printTaskInfo(
      "Deploy git hooks for '{packagePath}'",
      [
        'packagePath' => Path::makeRelative($this->getPackagePath(), $this->getRootProjectDir()),
      ]
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $commonTemplateFileName = $this->getCommonTemplateFileName();
    $packagePath = $this->getPackagePath();

    $context = [
      'packagePath' => $packagePath,
      'commonTemplateFileName' => $commonTemplateFileName,
    ];

    // @todo Create a runValidate() method.
    if (!$this->fs->exists($packagePath)) {
      $this->printTaskError("The '{packagePath}' directory does not exists.", $context);

      // @todo Set an error.
      return $this;
    }

    if (!$this->fs->exists("$packagePath/.git")) {
      $this->printTaskError("The '{packagePath}' directory is not a Git repository.", $context);

      // @todo Set an error.
      return $this;
    }

    if (!$this->fs->exists($commonTemplateFileName)) {
      $this->printTaskError("The '{commonTemplateFileName}' file does not exists.", $context);

      // @todo Set an error.
      return $this;
    }

    return $this
      ->runActionPrepareDestinationDir()
      ->runActionCopyHookFiles()
      ->runActionCopyCommonFile();
  }

  /**
   * @return $this
   */
  protected function runActionPrepareDestinationDir() {
    $destinationDir = $this->getDestinationDir();

    if (is_link($destinationDir)) {
      $this->fs->remove($destinationDir);
    }

    // @todo This looks like a PrepareDirectoryTask.
    if (!$this->fs->exists($destinationDir)) {
      $this->fs->mkdir($destinationDir, 0777 - umask());
    }
    else {
      $directDescendants = (new Finder())
        ->in($destinationDir)
        ->depth('== 0')
        ->ignoreDotFiles(TRUE);
      $this->fs->remove($directDescendants);
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function runActionCopyHookFiles() {
    $hookFiles = $this->getHookFiles($this->getHookFilesSourceDir());
    $destinationDir = $this->getDestinationDir();

    foreach ($hookFiles as $hookFile) {
      $this->fs->copy($hookFile, Path::join($destinationDir, $hookFile->getFilename()));
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function runActionCopyCommonFile() {
    $this->fs->dumpFile(
      Path::join($this->getDestinationDir(), '_common.php'),
      $this->replaceTemplateVariables(file_get_contents($this->getCommonTemplateFileName()))
    );

    return $this;
  }

  protected function replaceTemplateVariables(string $content): string {
    $marvinIncubatorDir = Utils::marvinIncubatorDir();
    $rootProjectDir = $this->getRootProjectDir();
    $packagePath = $this->getPackagePath();
    $rootProjectDirRelativeToPackagePath = Path::makeRelative($rootProjectDir, $packagePath);

    $variables = [
      '$rootProjectDir' => $rootProjectDirRelativeToPackagePath,
      '$composerExecutable' => $this->getComposerExecutable(),
      '$marvinIncubatorDir' => $marvinIncubatorDir,
    ];

    $pattern = " %s = %s;\n";
    $replacePairs = [];
    foreach ($variables as $varName => $value) {
      $from = sprintf($pattern, $varName, var_export('', TRUE));
      $replacePairs[$from] = sprintf($pattern, $varName, var_export($value, TRUE));
    }

    return strtr($content, $replacePairs);
  }

  protected function getDestinationDir(): string {
    // @todo Support for ".git" file.
    return Path::join($this->getPackagePath(), '.git', 'hooks');
  }

  protected function getHookFiles(string $dir): Finder {
    return (new Finder())
      ->in($dir)
      ->notName('/^_/')
      ->depth('== 0')
      ->files();
  }

}
