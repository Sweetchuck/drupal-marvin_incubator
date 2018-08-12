<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinLintComposerValidateTest extends CommandsTestBase {

  public function caseMarvinLintComposerValidate(): array {
    $fixturesDir = static::getTmp();
    $options = $this->getDefaultDrushCommandOptions();

    return [
      'dummy_m1' => [
        [
          'exitCode' => 0,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => './composer.json is valid',
            ],
          ],
          'stdError' => [
            'contains' => [
              'stdError contains' => " Running composer validate in $fixturesDir/extensions/dummy_m1\n",
            ],
          ],
        ],
        ['dummy_m1'],
        $options,
      ],
      'dummy_m2' => [
        [
          'exitCode' => 2,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => implode(PHP_EOL, [
                './composer.json is valid for simple usage with composer but has',
                'strict errors that make it unable to be published as a package:',
                'See https://getcomposer.org/doc/04-schema.md for details on the schema',
                'description : The property description is required',
              ]),
            ],
          ],
          'stdError' => [
            'contains' => [
              'stdError contains' => " Running composer validate in $fixturesDir/extensions/dummy_m2\n",
            ],
          ],
        ],
        ['dummy_m2'],
        $options,
      ],
    ];
  }

  /**
   * @dataProvider caseMarvinLintComposerValidate
   */
  public function testMarvinLintComposerValidate(array $expected, array $args, array $options): void {
    $this->drush(
      'marvin:lint:composer-validate',
      $args,
      $options,
      NULL,
      static::getSut(),
      $expected['exitCode']
    );

    $this->assertText($expected['stdOutput'],  $this->getOutput());
    $this->assertText($expected['stdError'], $this->getErrorOutput());
  }

  protected function assertText(array $rules, $text) {
    foreach ($rules as $assertType => $expectations) {
      foreach ($expectations as $message => $expected) {
        switch ($assertType) {
          case 'same':
            $this->assertSame($expected, $text, $message);
            break;

          case 'contains':
            $this->assertContains($expected, $text, $message);
            break;
        }
      }
    }
  }

}
