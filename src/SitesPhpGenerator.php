<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

class SitesPhpGenerator {

  /**
   * @var string[]
   */
  protected $siteNames = [];

  public function getSiteNames(): array {
    return $this->siteNames;
  }

  /**
   * @param string[] $siteNames
   *
   * @return $this
   */
  public function setSiteNames(array $siteNames) {
    $this->siteNames = $siteNames;

    return $this;
  }

  /**
   * @var string[]
   */
  protected $databaseVariantIds = [];

  /**
   * @return string[]
   */
  public function getDatabaseVariantIds(): array {
    return $this->databaseVariantIds;
  }

  /**
   * @param string[] $ids
   */
  public function setDatabaseVariantIds(array $ids) {
    $this->databaseVariantIds = $ids;
  }

  /**
   * @var string
   */
  protected $siteDirPattern = '{{ siteName }}.{{ dbId }}';

  public function getSiteDirPattern(): string {
    return $this->siteDirPattern;
  }

  /**
   * @return $this
   */
  public function setSiteDirPattern(string $pattern) {
    $this->siteDirPattern = $pattern;

    return $this;
  }

  /**
   * @var string
   */
  protected $urlPattern = '{{ dbId }}.{{ siteName }}.d8.localhost';

  public function getUrlPattern(): string {
    return $this->urlPattern;
  }

  public function setUrlPattern(string $value) {
    $this->urlPattern = $value;
  }

  public function generate(): string {
    $content = [
      '<?php',
      '',
      '$sites = [',
    ];

    $replacementPairs = [
      '{{ siteName }}' => '',
      '{{ dbId }}' => '',
    ];

    $urlPattern = $this->getUrlPattern();
    $siteDirPattern = $this->getSiteDirPattern();
    foreach ($this->getSiteNames() as $siteName) {
      $replacementPairs['{{ siteName }}'] = $siteName;
      foreach ($this->getDatabaseVariantIds() as $dbId) {
        $replacementPairs['{{ dbId }}'] = $dbId;
        $content[] = sprintf(
          '  %s => %s,',
          var_export(strtr($urlPattern, $replacementPairs), TRUE),
          var_export(strtr($siteDirPattern, $replacementPairs), TRUE)
        );
      }
    }

    if (count($content) === 3) {
      $content[2] .= '];';
    }
    else {
      $content[] = '];';
    }

    $content[] = '';

    return implode(PHP_EOL, $content);
  }

}
