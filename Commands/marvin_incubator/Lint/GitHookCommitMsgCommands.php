<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Lint;

use Drush\Commands\marvin\CommandsBase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\Stringy\StringyTaskLoader;
use Sweetchuck\Utils\ArrayFilterInterface;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Console\Input\InputInterface;

class GitHookCommitMsgCommands extends CommandsBase implements LoggerAwareInterface {

  use StringyTaskLoader;
  use LoggerAwareTrait;

  /**
   * @hook on-event marvin:git-hook:commit-msg
   */
  public function gitHookCommitMsg(InputInterface $input) {
    $commitMsgFileName = $input->getArgument('commitMsgFileName');

    return [
      'marvin.git-hook.commit-msg.read' => [
        'weight' => -200,
        'task' => $this->getTaskRead($commitMsgFileName),
      ],
      'marvin.git-hook.commit-msg.sanitize' => [
        'weight' => -190,
        'task' => $this->getTaskSanitize(),
      ],
      'marvin.git-hook.commit-msg.validate' => [
        'weight' => -180,
        'task' => $this->getTaskValidate(),
      ],
    ];
  }

  protected function getTaskRead(string $commitMsgFileName): \Closure {
    return function (RoboStateData $data) use ($commitMsgFileName): int {
      $content = @file_get_contents($commitMsgFileName);
      if ($content === FALSE) {
        throw new \RuntimeException(
          sprintf('Read file content from "%s" file failed', $commitMsgFileName),
          1
        );
      }

      $data['commitMsg'] = $content;

      return 0;
    };
  }

  protected function getTaskSanitize(): TaskInterface {
    return $this
      ->taskStringy()
      ->callRegexReplace('(^|(\r\n)|(\n\r)|\r|\n)#([^\r\n]*)|$', '')
      ->callTrim("\n\r")
      ->deferTaskConfiguration('setString', 'commitMsg');
  }

  protected function getTaskValidate(): \Closure {
    return function (RoboStateData $data): int {
      $exitCode = 0;
      foreach ($this->getRules() as $rule) {
        if (preg_match($rule['pattern'], $data['commitMsg']) !== 1) {
          $this->logger->error($rule['errorMessage']);
          $exitCode = 1;
        }
      }

      return $exitCode;
    };
  }

  protected function getRules(): array {
    $rules = array_replace_recursive(
      $this->getDefaultRules(),
      $this->getConfig()->get('command.marvin.git-hook.commit-msg.settings.rules') ?: []
    );

    foreach (array_keys($rules) as $ruleName) {
      $rules[$ruleName]['name'] = $ruleName;
      $rules[$ruleName] += [
        'enabled' => TRUE,
        'description' => '- Missing -',
        'errorMessage' => '- Missing -',
      ];
    }

    return array_filter($rules, $this->getRuleFilter());
  }

  protected function getRuleFilter(): ArrayFilterInterface {
    return new ArrayFilterEnabled();
  }

  protected function getDefaultRules(): array {
    return [
      'subjectLine' => [
        'name' => 'subjectLine',
        'pattern' => "/^(Issue #[0-9]+ - .{5,})|(Merge( remote-tracking){0,1} branch '[^\\s]+?'(, '[^\\s]+?'){0,} into [^\\s]+?)(\\n|$)/u",
        'description' => '@todo',
        'errorMessage' => '@todo',
      ],
    ];
  }

}
