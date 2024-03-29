<?php

/**
 * @file
 * Git hook callback handler for managed extensions.
 */

declare(strict_types = 1);

use Drupal\marvin_incubator\GitHookHandler;

call_user_func(function () {
  $rootProjectDir = '';
  $composerExecutable = '';
  $marvinIncubatorDir = '';
  $packagePath = getcwd();

  if ($marvinIncubatorDir === '') {
    // Nothing to do.
    // This file gets included by the code coverage reporter during phpunit tests.
    return;
  }

  if (!class_exists(GitHookHandler::class)) {
    require_once "$marvinIncubatorDir/src/GitHookHandler.php";
  }

  $gitHookHandler = new GitHookHandler();
  register_shutdown_function([$gitHookHandler, 'writeFooter']);

  $context = $gitHookHandler
    ->init(
      $GLOBALS['argv'],
      $packagePath,
      $rootProjectDir,
      $composerExecutable,
      $marvinIncubatorDir
    )
    ->writeHeader()
    ->doIt();

  if ($context) {
    $_SERVER['argv'] = $GLOBALS['argv'] = $context['cliArgs'];
    $_SERVER['argc'] = $GLOBALS['argc'] = count($context['cliArgs']);

    require $context['pathToDrushPhp'];
  }
});
