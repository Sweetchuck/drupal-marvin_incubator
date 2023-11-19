<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin_incubator\Utils;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class GitHooksDeployTask extends BaseTask implements
  ContainerAwareInterface,
  OutputAwareInterface {

  use ContainerAwareTrait;
  use IO;

  protected Filesystem $fs;

  protected string $packagePath = '';

  public function getPackagePath(): string {
    return $this->packagePath;
  }

  public function setPackagePath(string $value): static {
    $this->packagePath = $value;

    return $this;
  }

  protected string $hookFilesSourceDir = '';

  public function getHookFilesSourceDir(): string {
    return $this->hookFilesSourceDir;
  }

  public function setHookFilesSourceDir(string $value): static {
    $this->hookFilesSourceDir = $value;

    return $this;
  }

  protected string $commonTemplateFileName = '';

  public function getCommonTemplateFileName(): string {
    return $this->commonTemplateFileName;
  }

  public function setCommonTemplateFileName(string $value): static {
    $this->commonTemplateFileName = $value;

    return $this;
  }

  protected string $rootProjectDir = '';

  public function getRootProjectDir(): string {
    return $this->rootProjectDir;
  }

  /**
   * Absolute path to the project root dir.
   */
  public function setRootProjectDir(string $value): static {
    $this->rootProjectDir = $value;

    return $this;
  }

  protected string $composerExecutable = 'composer';

  public function getComposerExecutable(): string {
    return $this->composerExecutable;
  }

  public function setComposerExecutable(string $value): static {
    $this->composerExecutable = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param marvin-incubator-robo-task-git-hooks-deploy-options $options
   */
  public function setOptions(array $options): static {
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

  protected function runPrepare(): static {
    parent::runPrepare();

    $this->fs = new Filesystem();

    return $this;
  }

  protected function runHeader(): static {
    $this->printTaskInfo(
      "Deploy git hooks for '{packagePath}'",
      [
        'packagePath' => Path::makeRelative($this->getPackagePath(), $this->getRootProjectDir()),
      ]
    );

    return $this;
  }

  protected function runAction(): static {
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

  protected function runActionPrepareDestinationDir(): static {
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

  protected function runActionCopyHookFiles(): static {
    $hookFiles = $this->getHookFiles($this->getHookFilesSourceDir());
    $destinationDir = $this->getDestinationDir();

    foreach ($hookFiles as $hookFile) {
      $this->fs->copy(
        $hookFile->getPathname(),
        Path::join($destinationDir, $hookFile->getFilename()),
      );
    }

    return $this;
  }

  protected function runActionCopyCommonFile(): static {
    $this->fs->dumpFile(
      Path::join($this->getDestinationDir(), '_common.php'),
      $this->replaceTemplateVariables(file_get_contents($this->getCommonTemplateFileName()) ?: ''),
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
