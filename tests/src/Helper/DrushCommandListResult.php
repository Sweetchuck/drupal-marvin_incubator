<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Helper;

class DrushCommandListResult {

  public ?\DOMDocument $doc = NULL;

  public ?\DOMXPath $xpath = NULL;

  protected string $result = '';

  public function getResult(): string {
    return $this->result;
  }

  public function setResult(string $result): static {
    $this->result = $result;
    $this->reset();

    return $this;
  }

  protected function reset(): static {
    $this->doc = NULL;
    $this->xpath = NULL;

    if (!$this->result) {
      return $this;
    }

    $this->doc = new \DOMDocument();
    $this->doc->loadXML($this->result);
    $this->xpath = new \DOMXPath($this->doc);

    return $this;
  }

  /**
   * @return string[]
   */
  public function getNamespaces(): array {
    $query = '/symfony/namespaces/namespace[@id]';
    /** @phpstan-var false|array<\DOMElement> $elements */
    $elements = $this->xpath->query($query);
    if (!$elements) {
      // Developer error.
      return [];
    }

    $result = [];
    foreach ($elements as $element) {
      $result[] = $element->getAttribute('id');
    }

    return $result;
  }

}
