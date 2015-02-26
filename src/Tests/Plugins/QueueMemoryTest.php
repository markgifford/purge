<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Plugins\QueueMemoryTest.
 */

namespace Drupal\purge\Tests\Plugins;

use Drupal\purge\Tests\Queue\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\PurgeQueue\Memory.
 *
 * @group purge
 * @see \Drupal\purge\Queue\PluginInterface
 */
class QueueMemoryTest extends PluginTestBase {
  protected $plugin_id = 'memory';

}
