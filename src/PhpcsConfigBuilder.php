<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

class PhpcsConfigBuilder {

  protected ?\DOMDocument $doc = NULL;

  public function getDoc(): ?\DOMDocument {
    return $this->doc;
  }

  protected ?\DOMXPath $xpath = NULL;

  public function reset() {
    $this->doc = NULL;
    $this->xpath = NULL;

    return $this;
  }

  /**
   * @return $this
   */
  public function init(string $name = 'Custom') {
    if ($this->doc) {
      return $this;
    }

    $this->doc = new \DOMDocument('1.0', 'UTF-8');
    $this->doc->preserveWhiteSpace = TRUE;
    $this->doc->formatOutput = TRUE;

    $ruleset = $this->doc->createElement('ruleset');
    $this->doc->appendChild($ruleset);

    $ruleset->setAttribute('name', $name);

    $ruleset->setAttribute(
      'xmlns:xsi',
      'http://www.w3.org/2001/XMLSchema-instance'
    );

    // @todo Detect "vendor-dir".
    $ruleset->setAttribute(
      'xsi:noNamespaceSchemaLocation',
      './vendor/squizlabs/php_codesniffer/phpcs.xsd'
    );

    $this->xpath = new \DOMXPath($this->doc);

    return $this;
  }

  /**
   * @return $this
   */
  public function addFile(string $fileName, ?bool $phpcsOnly = NULL, ?bool $phpcbfOnly = NULL) {
    $this->init();

    $file = $this->doc->firstChild->appendChild($this->doc->createElement('file'));
    $file->appendChild($this->doc->createTextNode($fileName));
    if ($phpcsOnly !== NULL) {
      $file->setAttribute('phpcs-only', Utils::boolToString($phpcsOnly, FALSE));
    }

    if ($phpcbfOnly !== NULL) {
      $file->setAttribute('phpcbf-only', Utils::boolToString($phpcbfOnly, FALSE));
    }

    return $this;
  }

  /**
   * @return $this
   */
  public function addExcludePattern(string $pattern, ?bool $phpcsOnly = NULL, ?bool $phpcbfOnly = NULL) {
    $this->init();

    $file = $this->doc->firstChild->appendChild($this->doc->createElement('exclude-pattern'));
    $file->appendChild($this->doc->createTextNode($pattern));

    if ($phpcsOnly !== NULL) {
      $file->setAttribute('phpcs-only', Utils::boolToString($phpcsOnly, FALSE));
    }

    if ($phpcbfOnly !== NULL) {
      $file->setAttribute('phpcbf-only', Utils::boolToString($phpcbfOnly, FALSE));
    }

    return $this;
  }

  /**
   * @return $this
   */
  public function addArg(string $name, string $value) {
    $this->init();

    $arg = $this->doc->firstChild->appendChild($this->doc->createElement('arg'));
    $arg->setAttribute('name', $name);
    $arg->setAttribute('value', $value);

    return $this;
  }

  /**
   * @return $this
   */
  public function addRule(string $ref, array $definition = []) {
    $this->init();

    $this->ensureRuleElement($ref);

    if (array_key_exists('message', $definition)) {
      $this->setRuleMessage($ref, $definition['message']);
    }

    if (array_key_exists('severity', $definition)) {
      $this->setRuleSeverity($ref, $definition['severity']);
    }

    if (array_key_exists('type', $definition)) {
      $this->setRuleType($ref, $definition['type']);
    }

    if (array_key_exists('exclude', $definition)) {
      $this->addRuleExclude($ref, $definition['exclude']);
    }

    if (array_key_exists('exclude-pattern', $definition)) {
      $this->addRuleExcludePattern($ref, $definition['exclude-pattern']);
    }

    if (array_key_exists('include-pattern', $definition)) {
      $this->addRuleIncludePattern($ref, $definition['include-pattern']);
    }

    if (array_key_exists('properties', $definition)) {
      $this->addRuleProperties($ref, $definition['properties']);
    }

    return $this;
  }

  public function addRuleExclude(string $ref, iterable $subRefs) {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    foreach ($subRefs as $subRef) {
      $exclude = $this->doc->createElement('exclude');
      $exclude->setAttribute('name', $subRef);
      $rule->appendChild($exclude);
    }

    return $this;
  }

  public function setRuleMessage(string $ref, string $message) {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $this->addUniqueChildElement($rule, 'message', $message);

    return $this;
  }

  public function setRuleSeverity(string $ref, int $severity) {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $this->addUniqueChildElement($rule, 'severity', (string) $severity);

    return $this;
  }

  public function setRuleType(string $ref, string $type) {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $this->addUniqueChildElement($rule, 'type', $type);

    return $this;
  }

  public function addRuleExcludePattern(string $ref, iterable $patterns) {
    $this->init();
    $rule = $this->ensureRuleElement($ref);
    foreach ($patterns as $pattern) {
      $rule->appendChild($rule->ownerDocument->createElement('exclude-pattern', $pattern));
    }

    return $this;
  }

  public function addRuleIncludePattern(string $ref, iterable $patterns) {
    $this->init();
    $rule = $this->ensureRuleElement($ref);
    foreach ($patterns as $pattern) {
      $rule->appendChild($rule->ownerDocument->createElement('include-pattern', $pattern));
    }

    return $this;
  }

  public function addRuleProperties(string $ref, iterable $properties) {
    $this->init();

    $rule = $this->ensureRuleElement($ref);
    $parent = $this->ensureElement($rule, 'properties');
    foreach ($properties as $name => $value) {
      $elements = $this->xpath->query("./property[@name='$name']", $parent);
      if ($elements->count()) {
        $property = $elements->item(0);
      }
      else {
        $property = $rule->ownerDocument->createElement('property');
        $parent->appendChild($property);
      }

      $property->setAttribute('name', $name);
      $property->setAttribute('value', $value);
    }

    return $this;
  }

  public function build(): string {
    $this->init();

    return $this->doc->saveXML();
  }

  protected function addUniqueChildElement(\DOMElement $parent, string $name, string $value) {
    $elements = $parent->getElementsByTagName($name);
    if (!$elements->count()) {
      $parent->appendChild($this->doc->createElement($name, $value));

      return $this;
    }

    $elements->item(0)->nodeValue = $value;

    return $this;
  }

  protected function ensureElement(\DOMElement $parent, string $name): \DOMElement {
    $elements = $parent->getElementsByTagName($name);
    if ($elements->count()) {
      return $elements->item(0);
    }

    return $parent->appendChild($parent->ownerDocument->createElement($name));
  }

  protected function ensureRuleElement(string $ref): \DOMElement {
    return $this->findRuleElement($ref) ?: $this->createRuleElement($ref);
  }

  protected function findRuleElement(string $ref): ?\DOMElement {
    $result = $this->xpath->query("/ruleset/rule[@ref='$ref']");

    return $result->count() ? $result->item(0) : NULL;
  }

  protected function createRuleElement(string $ref): \DOMElement {
    $rule = $this
      ->doc
      ->firstChild
      ->appendChild($this->doc->createElement('rule'));
    $rule->setAttribute('ref', $ref);

    return $rule;
  }

}
