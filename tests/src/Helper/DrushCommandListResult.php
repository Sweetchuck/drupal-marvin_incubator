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

  public function getNamespaces(): array {
    $query = '/symfony/namespaces/namespace[@id]';
    $result = [];
    /** @var \DOMElement $item */
    foreach ($this->xpath->query($query) as $item) {
      $result[] = $item->getAttribute('id');
    }

    return $result;
  }

}
