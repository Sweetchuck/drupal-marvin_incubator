<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\marvin\Utils as MarvinUtils;
use Stringy\StaticStringy;
use const JSON_UNESCAPED_SLASHES;

class PhpunitConfigGenerator {

  /**
   * @var int
   */
  protected $jsonEncodeFlagsForXmlAttributes = JSON_UNESCAPED_SLASHES;

  /**
   * @var string
   */
  protected $drupalRoot = '.';

  public function getDrupalRoot(): string {
    return $this->drupalRoot;
  }

  public function setDrupalRoot(string $drupalRoot) {
    $this->drupalRoot = $drupalRoot;

    return $this;
  }

  /**
   * @var string
   */
  protected $url = '';

  public function getUrl(): string {
    return $this->url;
  }

  public function setUrl(string $value) {
    $this->url = $value;

    return $this;
  }

  /**
   * @var array
   */
  protected $dbConnection = [
    'driver' => 'mysql',
    'username' => 'root',
    'password' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => '',
    'prefix' => '',
  ];

  public function getDbConnection(): array {
    return $this->dbConnection;
  }

  public function setDbConnection(array $value) {
    $this->dbConnection = $value;

    return $this;
  }

  /**
   * @var string[]
   */
  protected $packagePaths = [];

  public function getPackagePaths(): array {
    return $this->packagePaths;
  }

  public function setPackagePaths(array $value) {
    $this->packagePaths = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $phpVersion = '0703';

  public function getPhpVersion(): string {
    return $this->phpVersion;
  }

  /**
   * @return $this
   */
  public function setPhpVersion(string $value) {
    $this->phpVersion = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $reportsDir = 'reports';

  public function getReportsDir(): string {
    return $this->reportsDir;
  }

  public function setReportsDir(string $value) {
    $this->reportsDir = $value;

    return $this;
  }

  /**
   * @var \DOMDocument
   */
  protected $doc;

  public function generate(): string {
    $this
      ->init()
      ->elementPhpunit()
      ->elementPhp()
      ->elementTestSuites()
      ->elementListeners()
      ->elementLogging()
      ->elementCoverage();

    return $this->doc->saveXML();
  }

  /**
   * @return $this
   */
  protected function init() {
    $this->doc = new \DOMDocument('1.0', 'UTF-8');
    $this->doc->preserveWhiteSpace = TRUE;
    $this->doc->formatOutput = TRUE;

    return $this;
  }

  /**
   * @return $this
   */
  protected function elementPhpunit() {
    $element = $this->doc->createElement('phpunit');
    $this->doc->appendChild($element);

    $element->setAttribute(
      'xmlns:xsi',
      'http://www.w3.org/2001/XMLSchema-instance'
    );

    // @todo Detect "vendor-dir".
    $element->setAttribute(
      'xsi:noNamespaceSchemaLocation',
      'vendor/phpunit/phpunit/phpunit.xsd'
    );

    $drupalRoot = $this->getDrupalRoot() ?: '.';
    $element->setAttribute(
      'bootstrap',
      "{$drupalRoot}/core/tests/bootstrap.php"
    );

    $element->setAttribute(
      'colors',
      'true'
    );

    $element->setAttribute(
      'beStrictAboutOutputDuringTests',
      'true'
    );

    $element->setAttribute(
      'beStrictAboutChangesToGlobalState',
      'true'
    );

    return $this;
  }

  /**
   * @return $this
   */
  protected function elementPhp() {
    $element = $this->doc->createElement('php');
    $this->doc->firstChild->appendChild($element);

    $tags = [
      'ini' => $this->getPhpIniPairs(),
      'env' => $this->getPhpEnvPairs(),
    ];

    foreach ($tags as $tagName => $pairs) {
      foreach ($pairs as $name => $value) {
        $tag = $this->doc->createElement($tagName);
        $tag->setAttribute('name', $name);
        $tag->setAttribute('value', (string) $value);

        $element->appendChild($tag);
      }
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function elementTestSuites() {
    $element = $this->doc->createElement('testsuites');
    $this->doc->firstChild->appendChild($element);

    $tsNames = $this->getTestSuitNames();
    foreach (array_keys($tsNames) as $tsName) {
      $testSuitElement = $this->doc->createElement('testsuite');
      $element->appendChild($testSuitElement);
      $testSuitElement->setAttribute('name', $tsName);

      $tsNameUpperCamel = StaticStringy::upperCamelize($tsName);
      $testSuitElement->appendChild($this->doc->createElement(
        'file',
        "{$this->drupalRoot}/core/tests/TestSuites/{$tsNameUpperCamel}TestSuite.php"
      ));
    }

    foreach ($this->getPackagePaths() as $packagePath) {
      $packageName = basename($packagePath);
      foreach ($tsNames as $tsName => $tsNamespace) {
        $testSuitElement = $this->doc->createElement('testsuite');
        $element->appendChild($testSuitElement);
        $testSuitElement->setAttribute('name', "$packageName-$tsName");
        $testSuitElement->appendChild($this->doc->createElement(
          'directory',
          "$packagePath/tests/src/$tsNamespace",
        ));
      }
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function elementListeners() {
    $element = $this->doc->createElement('listeners');
    $this->doc->firstChild->appendChild($element);

    foreach ($this->getListenerClassNames() as $className) {
      $element->appendChild($this->doc->createElement('listener'));
      $element->lastChild->setAttribute('class', $className);
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function elementLogging() {
    $element = $this->doc->createElement('logging');
    $this->doc->firstChild->appendChild($element);

    foreach ($this->getLoggingEntries() as $entry) {
      $element->appendChild($this->createDomElementFromEntry($entry));
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function elementCoverage() {
    $coverage = $this->doc->createElement('coverage');
    $this->doc->firstChild->appendChild($coverage);
    $coverage->setAttribute('processUncoveredFiles', 'true');

    $include = $this->doc->createElement('include');
    $coverage->appendChild($include);
    foreach ($this->getPackagePaths() as $packagePath) {
      // @todo Add other plain PHP files such as *.module or *.install.
      $include->appendChild($this->doc->createElement(
        'directory',
        "$packagePath/Commands",
      ));

      $include->appendChild($this->doc->createElement(
        'directory',
        "$packagePath/Generators",
      ));

      $include->appendChild($this->doc->createElement(
        'directory',
        "$packagePath/src",
      ));
    }

    $report = $this->doc->createElement('report');
    $coverage->appendChild($report);
    foreach ($this->getReportEntries() as $entry) {
      $report->appendChild($this->createDomElementFromEntry($entry));
    }

    return $this;
  }

  protected function getPhpIniPairs(): array {
    return [
      'error_reporting' => MarvinUtils::phpErrorAll($this->getPhpVersion()),
      'memory_limit' => '-1',
    ];
  }

  protected function getPhpEnvPairs(): array {
    $phpVersion = $this->getPhpVersion();

    // @todo Fetch other key-value pairs from other phpunit.*.xml files.
    return [
      'PHPUNIT_RESULT_CACHE' => "sites/all/temporary/.phpunit.$phpVersion.result.cache",
      'SIMPLETEST_BASE_URL' => $this->getUrl(),
      'SIMPLETEST_DB' => MarvinUtils::dbUrl($this->getDbConnection()),
      'SYMFONY_DEPRECATIONS_HELPER' => 'weak_vendor',
      'MINK_DRIVER_CLASS' => DrupalSelenium2Driver::class,
      'MINK_DRIVER_ARGS' => json_encode($this->getMinkDriverArgs(), $this->jsonEncodeFlagsForXmlAttributes),
      'MINK_DRIVER_ARGS_WEBDRIVER' => json_encode($this->getMinkDriverArgsWebDriver(), $this->jsonEncodeFlagsForXmlAttributes),
    ];
  }

  protected function getMinkDriverArgs(): array {
    return [
      'chrome',
      NULL,
      'http://localhost:4444/wd/hub',
    ];
  }

  protected function getMinkDriverArgsWebDriver(): array {
    return [
      'chromium',
      [
        'browserName' => 'chrome',
        'chromeOptions' => [
          'args' => [
            '--disable-gpu',
            '--headless',
          ],
        ],
      ],
      'http://localhost:9222',
    ];
  }

  /**
   * @return string[]
   */
  protected function getTestSuitNames(): array {
    return [
      'unit' => 'Unit',
      'kernel' => 'Kernel',
      'functional' => 'Functional',
      'functional-javascript' => 'FunctionalJavascript',
    ];
  }

  /**
   * @return string[]
   */
  protected function getListenerClassNames(): array {
    return [
      '\Drupal\Tests\Listeners\DrupalListener',
    ];
  }

  protected function getReportEntries(): array {
    $reportsDir = $this->getReportsDir();

    return [
      'html' => [
        'type' => 'html',
        'attributes' => [
          'outputDirectory' => "$reportsDir/human/coverage/html",
        ],
      ],
      'clover' => [
        'type' => 'clover',
        'attributes' => [
          'outputFile' => "$reportsDir/machine/coverage/coverage.xml",
        ],
      ],
    ];
  }

  protected function getLoggingEntries(): array {
    $reportsDir = $this->getReportsDir();

    return [
      'text' => [
        'type' => 'text',
        'attributes' => [
          'outputFile' => 'php://stdout',
        ],
      ],
      'testdoxHtml' => [
        'type' => 'testdoxHtml',
        'attributes' => [
          'outputFile' => "$reportsDir/human/unit/junit.html",
        ],
      ],
      'junit' => [
        'type' => 'junit',
        'attributes' => [
          'outputFile' => "$reportsDir/machine/unit/junit.xml",
        ],
      ],
    ];
  }

  protected function createDomElementFromEntry(array $entry): \DOMElement {
    $element = $this->doc->createElement($entry['type']);
    foreach ($entry['attributes'] ?? [] as $key => $value) {
      $element->setAttribute($key, $value);
    }

    return $element;
  }

}
