<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\PurgeStatusReportTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests if diagnostic checks are showing up on Drupal's status report and thus
 * verifies that purge_ui's hook_requirements() implementation works correctly.
 *
 * @group purge
 * @see purge_ui_requirements()
 * @see \Drupal\purge\DiagnosticCheck\ServiceInterface
 */
class PurgeStatusReportTest extends WebTestBase {

  /**
   * @var \Drupal\purge\DiagnosticCheck\ServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * @var string
   */
  protected $path = 'admin/reports/status';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'purge_noqueuer_test',
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
    'purge_ui'
  ];

  /**
   * Setup the test.
   */
  function setUp() {
    parent::setUp();
    $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
  }

  /*
   * Test if the form is at its place and has the right permissions.
   */
  public function testWarningAndErrorChecksPresent() {
    $this->drupalLogin($this->admin_user);
    $this->assertResponse(200);
    $this->drupalGet($this->path);
    $this->assertText('This is an ok for testing.');
    $this->assertText('Purge - Always a warning');
    $this->assertText('This is a warning for testing.');
    $this->assertText('Purge - Always an error');
    $this->assertText('This is an error for testing.');
  }
}
