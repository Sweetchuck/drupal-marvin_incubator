<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

/**
 * @see https://github.com/drush-ops/drush/issues/5662
 */
class SitesPhpGen {

  /**
   * @var string[]
   */
  protected array $siteNames = [];

  public function getSiteNames(): array {
    return $this->siteNames;
  }

  /**
   * @param string[] $siteNames
   */
  public function setSiteNames(array $siteNames): static {
    $this->siteNames = $siteNames;

    return $this;
  }

  /**
   * @var string[]
   */
  protected array $databaseVariantIds = [];

  /**
   * @return string[]
   */
  public function getDatabaseVariantIds(): array {
    return $this->databaseVariantIds;
  }

  /**
   * @param string[] $ids
   */
  public function setDatabaseVariantIds(array $ids): static {
    $this->databaseVariantIds = $ids;

    return $this;
  }

  protected string $siteDirPatternDefault = '{{ siteName }}.{{ dbId }}';

  protected string $siteDirPattern = '';

  public function getSiteDirPattern(): string {
    return $this->siteDirPattern;
  }

  public function setSiteDirPattern(string $pattern): static {
    $this->siteDirPattern = $pattern;

    return $this;
  }

  protected string $urlPatternDefault = '{{ dbId }}.{{ siteName }}.d8.localhost';

  protected string $urlPattern = '';

  public function getUrlPattern(): string {
    return $this->urlPattern;
  }

  public function setUrlPattern(string $value): static {
    $this->urlPattern = $value;

    return $this;
  }

  public function generate(): string {
    $lines = [
      '<?php',
      '',
      '$sites = [',
    ];

    $replacementPairs = [
      '{{ siteName }}' => '',
      '{{ dbId }}' => '',
    ];

    $urlPattern = $this->getUrlPattern() ?: $this->urlPatternDefault;
    $siteDirPattern = $this->getSiteDirPattern() ?: $this->siteDirPatternDefault;
    foreach ($this->getSiteNames() as $siteName) {
      $replacementPairs['{{ siteName }}'] = $siteName;
      foreach ($this->getDatabaseVariantIds() as $dbId) {
        $replacementPairs['{{ dbId }}'] = $dbId;
        $lines[] = sprintf(
          '  %s => %s,',
          var_export(strtr($urlPattern, $replacementPairs), TRUE),
          var_export(strtr($siteDirPattern, $replacementPairs), TRUE)
        );
      }
    }

    if (count($lines) === 3) {
      $lines[2] .= '];';
    }
    else {
      $lines[] = '];';
    }

    $lines[] = '';

    return implode(PHP_EOL, $lines);
  }

}
