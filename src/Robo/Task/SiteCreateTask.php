<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Drupal\marvin\Robo\Task\BaseTask;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Task\File\Tasks as FileTaskLoader;
use Robo\Task\Filesystem\Tasks as FilesystemTaskLoader;
use Symfony\Component\Yaml\Yaml;

class SiteCreateTask extends BaseTask implements
  BuilderAwareInterface,
  ContainerAwareInterface,
  OutputAwareInterface {

  use BuilderAwareTrait;
  use ContainerAwareTrait;
  use IO;
  use FilesystemTaskLoader;
  use FileTaskLoader;

  protected string $drupalRoot = '.';

  public function getDrupalRoot(): string {
    return $this->drupalRoot;
  }

  public function setDrupalRoot(string $value): static {
    $this->drupalRoot = $value;

    return $this;
  }

  protected string $siteName = '';

  public function getSiteName(): string {
    return $this->siteName;
  }

  public function setSiteName(string $value): static {
    $this->siteName = $value;

    return $this;
  }

  /**
   * @phpstan-var array<string, marvin-incubator-db-variant>
   */
  protected array $dbVariants = [];

  /**
   * @phpstan-return array<string, marvin-incubator-db-variant>
   */
  public function getDbVariants(): array {
    return $this->dbVariants;
  }

  /**
   * @phpstan-param array<string, marvin-incubator-db-variant> $value
   */
  public function setDbVariants(array $value): static {
    $this->dbVariants = $value;

    return $this;
  }

  /**
   * @phpstan-var array<string, marvin-php-variant>
   */
  protected array $phpVariants = [];

  /**
   * @phpstan-return array<string, marvin-php-variant>
   */
  public function getPhpVariants(): array {
    return $this->phpVariants;
  }

  /**
   * @phpstan-param  array<string, marvin-php-variant> $value
   */
  public function setPhpVariants(array $value): static {
    $this->phpVariants = $value;

    return $this;
  }

  protected string $uriPattern = 'http://{{ phpId }}.dev.{{ dbId }}.{{ siteName }}.marvin_incubator.d09.localhost:1080';

  public function getUriPattern(): string {
    return $this->uriPattern;
  }

  public function setUriPattern(string $uriPattern): static {
    $this->uriPattern = $uriPattern;

    return $this;
  }

  protected string $siteDirPattern = '{{ siteName }}.{{ dbId }}';

  public function getSiteDirPattern(): string {
    return $this->siteDirPattern;
  }

  public function setSiteDirPattern(string $siteDirPattern): static {
    $this->siteDirPattern = $siteDirPattern;

    return $this;
  }

  protected CollectionBuilder $cb;

  /**
   * {@inheritdoc}
   *
   * @phpstan-param marvin-incubator-robo-task-site-create-options $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('drupalRoot', $options)) {
      $this->setDrupalRoot($options['drupalRoot']);
    }

    if (array_key_exists('siteName', $options)) {
      $this->setSiteName($options['siteName']);
    }

    if (array_key_exists('dbVariants', $options)) {
      $this->setDbVariants($options['dbVariants']);
    }

    if (array_key_exists('phpVariants', $options)) {
      $this->setPhpVariants($options['phpVariants']);
    }

    if (array_key_exists('uriPattern', $options)) {
      $this->setUriPattern($options['uriPattern']);
    }

    if (array_key_exists('siteDirPattern', $options)) {
      $this->setSiteDirPattern($options['siteDirPattern']);
    }

    return $this;
  }

  protected function runAction(): static {
    $this->cb = $this->collectionBuilder();
    foreach ($this->getDbVariants() as $dbVariant) {
      $this
        ->addTaskCreateDirectories($dbVariant)
        ->addTaskHashSalt($dbVariant)
        ->addTaskSettingsPhp($dbVariant)
        ->addTaskDrushSiteAliases($dbVariant);
    }

    $this
      ->cb
      ->run()
      ->stopOnFail();

    return $this;
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   */
  protected function addTaskCreateDirectories(array $dbVariant): static {
    $siteDir = $this->getSiteDir($dbVariant);
    $drupalRoot = $this->getDrupalRoot();
    $outerSitePath = $this->getOuterSitePath();

    $this->cb
      ->addTask($this
        ->taskFilesystemStack()
        ->mkdir("$drupalRoot/sites/$siteDir/files")
        ->mkdir("$outerSitePath/$siteDir/backup")
        ->mkdir("$outerSitePath/$siteDir/config/prod")
        ->mkdir("$outerSitePath/$siteDir/private")
        ->mkdir("$outerSitePath/$siteDir/temporary")
      );

    return $this;
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   */
  protected function addTaskHashSalt(array $dbVariant): static {
    $outerSitePath = $this->getOuterSitePath();
    $siteDir = $this->getSiteDir($dbVariant);

    $this
      ->cb
      ->addTask($this
        ->taskWriteToFile("$outerSitePath/$siteDir/hash_salt.txt")
        ->text('todo-generate-a-hash')
      );

    return $this;
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   */
  protected function addTaskSettingsPhp(array $dbVariant): static {
    $fileContent = <<< 'PHP'
    <?php

    declare(strict_types = 1);

    /**
     * @var string $app_root
     * @var string $site_path
     */

    require __DIR__ . '/../settings.php';

    PHP;

    $drupalRoot = $this->getDrupalRoot();
    $siteDir = $this->getSiteDir($dbVariant);

    $this
      ->cb
      ->addTask($this
        ->taskWriteToFile("$drupalRoot/sites/$siteDir/settings.php")
        ->text($fileContent)
    );

    return $this;
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   */
  protected function addTaskDrushSiteAliases(array $dbVariant): static {
    foreach ($this->getPhpVariants() as $phpVariant) {
      $this->addTaskDrushSiteAlias($dbVariant, $phpVariant);
    }

    return $this;
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   * @phpstan-param marvin-php-variant $phpVariant
   */
  protected function addTaskDrushSiteAlias(array $dbVariant, array $phpVariant): static {
    $siteDir = $this->getSiteDir($dbVariant);

    $site = [
      'local' => [
        'uri' => $this->getUri($dbVariant, $phpVariant),
        'root' => '${runtime.project}/docroot',
        'command' => [
          'site' => [
            'install' => [
              'options' => [
                'sites-subdir' => $siteDir,
                'site-name' => sprintf(
                  '%s %s – %s – %s',
                  '${marvin.vendorLabel}',
                  '${marvin.projectLabel}',
                  $this->getSiteName(),
                  $dbVariant['id'],
                ),
              ],
            ],
          ],
        ],
      ],
    ];

    $this
      ->cb
      ->addTask($this
        ->taskWriteToFile($this->getDrushSiteAliasFileName($dbVariant, $phpVariant))
        ->text(Yaml::dump($site, 42, 2))
      );

    return $this;
  }

  protected function getOuterSitePath(): string {
    $drupalRoot = $this->getDrupalRoot();

    return "$drupalRoot/../sites";
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   */
  protected function getSiteDir(array $dbVariant): string {
    return strtr(
      $this->getSiteDirPattern(),
      [
        '{{ siteName }}' => $this->getSiteName(),
        '{{ dbId }}' => $dbVariant['id'],
      ]
    );
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   * @phpstan-param marvin-php-variant $phpVariant
   */
  protected function getUri(array $dbVariant, array $phpVariant): string {
    return strtr(
      $this->getUriPattern(),
      [
        '{{ siteName }}' => $this->getSiteName(),
        '{{ dbId }}' => $dbVariant['id'],
        '{{ phpId }}' => $phpVariant['id'],
      ]
    );
  }

  /**
   * @phpstan-param marvin-incubator-db-variant $dbVariant
   * @phpstan-param marvin-php-variant $phpVariant
   */
  protected function getDrushSiteAliasFileName(array $dbVariant, array $phpVariant): string {
    return strtr(
      '{{ drupalRoot }}/../drush/sites/{{ siteName }}-{{ dbId }}-{{ phpId }}.site.yml',
      [
        '{{ drupalRoot }}' => $this->getDrupalRoot(),
        '{{ siteName }}' => $this->getSiteName(),
        '{{ dbId }}' => $dbVariant['id'],
        '{{ phpId }}' => $phpVariant['id'],
      ]
    );
  }

}
