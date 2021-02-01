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

  /**
   * @return $this
   */
  public function setDrupalRoot(string $value) {
    $this->drupalRoot = $value;

    return $this;
  }

  protected string $siteName = '';

  public function getSiteName(): string {
    return $this->siteName;
  }

  /**
   * @return $this
   */
  public function setSiteName(string $value) {
    $this->siteName = $value;

    return $this;
  }

  protected array $dbVariants = [];

  public function getDbVariants(): array {
    return $this->dbVariants;
  }

  /**
   * @return $this
   */
  public function setDbVariants(array $value) {
    $this->dbVariants = $value;

    return $this;
  }

  protected array $phpVariants = [];

  public function getPhpVariants(): array {
    return $this->phpVariants;
  }

  /**
   * @return $this
   */
  public function setPhpVariants(array $value) {
    $this->phpVariants = $value;

    return $this;
  }

  protected string $uriPattern = 'http://{{ phpId }}.dev.{{ dbId }}.{{ siteName }}.marvin_incubator.d8.localhost:1080';

  public function getUriPattern(): string {
    return $this->uriPattern;
  }

  /**
   * @return $this
   */
  public function setUriPattern(string $uriPattern) {
    $this->uriPattern = $uriPattern;

    return $this;
  }

  protected string $siteDirPattern = '{{ siteName }}.{{ dbId }}';

  public function getSiteDirPattern(): string {
    return $this->siteDirPattern;
  }

  /**
   * @return $this
   */
  public function setSiteDirPattern(string $siteDirPattern) {
    $this->siteDirPattern = $siteDirPattern;

    return $this;
  }

  protected CollectionBuilder $cb;

  protected array $dbVariant = [];

  public function setOptions(array $options) {
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

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $this->cb = $this->collectionBuilder();
    foreach ($this->getDbVariants() as $dbVariant) {
      $this->dbVariant = $dbVariant;
      $this
        ->addTaskCreateDirectories()
        ->addTaskHashSalt()
        ->addTaskSettingsPhp()
        ->addTaskDrushSiteAliases($dbVariant);
    }

    $this
      ->cb
      ->run()
      ->stopOnFail();

    return $this;
  }

  /**
   * @return $this
   */
  protected function addTaskCreateDirectories() {
    $siteDir = $this->getSiteDir();
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

  protected function addTaskHashSalt() {
    $outerSitePath = $this->getOuterSitePath();
    $siteDir = $this->getSiteDir();

    $this
      ->cb
      ->addTask($this
        ->taskWriteToFile("$outerSitePath/$siteDir/hash_salt.txt")
        ->text('todo-generate-a-hash')
      );

    return $this;
  }

  protected function addTaskSettingsPhp() {
    $fileContent = <<< PHP
<?php

declare(strict_types = 1);

/**
 * @var string \$app_root
 * @var string \$site_path
 */

require __DIR__ . '/../settings.php';

PHP;

    $drupalRoot = $this->getDrupalRoot();
    $siteDir = $this->getSiteDir();

    $this
      ->cb
      ->addTask($this
        ->taskWriteToFile("$drupalRoot/sites/$siteDir/settings.php")
        ->text($fileContent)
    );

    return $this;
  }

  /**
   * @return $this
   */
  protected function addTaskDrushSiteAliases(array $dbVariant) {
    foreach ($this->getPhpVariants() as $phpVariant) {
      $this->addTaskDrushSiteAlias($dbVariant, $phpVariant);
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function addTaskDrushSiteAlias(array $dbVariant, array $phpVariant) {
    $siteDir = $this->getSiteDir();

    $site = [
      'local' => [
        'uri' => $this->getUri($phpVariant),
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
        ->taskWriteToFile($this->getDrushSiteAliasFileName($phpVariant))
        ->text(Yaml::dump($site, 42, 2))
      );

    return $this;
  }

  protected function getOuterSitePath(): string {
    $drupalRoot = $this->getDrupalRoot();

    return "$drupalRoot/../sites";
  }

  protected function getSiteDir(): string {
    return strtr(
      $this->getSiteDirPattern(),
      [
        '{{ siteName }}' => $this->getSiteName(),
        '{{ dbId }}' => $this->dbVariant['id'],
      ]
    );
  }

  protected function getUri(array $phpVariant): string {
    return strtr(
      $this->getUriPattern(),
      [
        '{{ siteName }}' => $this->getSiteName(),
        '{{ dbId }}' => $this->dbVariant['id'],
        '{{ phpId }}' => $phpVariant['id'],
      ]
    );
  }

  protected function getDrushSiteAliasFileName(array $phpVariant): string {
    return strtr(
      '{{ drupalRoot }}/../drush/sites/{{ siteName }}-{{ dbId }}-{{ phpId }}.site.yml',
      [
        '{{ drupalRoot }}' => $this->getDrupalRoot(),
        '{{ siteName }}' => $this->getSiteName(),
        '{{ dbId }}' => $this->dbVariant['id'],
        '{{ phpId }}' => $phpVariant['id'],
      ]
    );
  }

}
