<?php

/**
 * @file
 * Contains \Drupal\purge_check_error_test\Plugin\PurgeDiagnosticCheck\AlwaysErrorCheck.
 */

namespace Drupal\purge_check_error_test\Plugin\PurgeDiagnosticCheck;

use Drupal\purge\DiagnosticCheck\PluginInterface;
use Drupal\purge\DiagnosticCheck\PluginBase;

/**
 * Tests if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "alwayserror",
 *   title = @Translation("Always an error"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysErrorCheck extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = "This is an error for testing.";
    return SELF::SEVERITY_ERROR;
  }
}