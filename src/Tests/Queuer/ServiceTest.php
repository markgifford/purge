<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Queuer\ServiceTest.
 */

namespace Drupal\purge\Tests\Queuer;

use Drupal\purge\Tests\KernelTestBase;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Queuer\ServiceInterface;
use Drupal\purge\Queuer\QueuerInterface;

/**
 * Tests \Drupal\purge\Queuer\Service.
 *
 * @group purge
 * @see \Drupal\purge\Queuer\Service
 * @see \Drupal\purge\Queuer\ServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.queuers';
  public static $modules = ['purge_queuer_test'];

  /**
   * Set up the test.
   */
  function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelTestBase::setUp();
    $this->installConfig(['purge_queuer_test']);
  }

  /**
   * Tests the \Iterator implementation, ::getEnabled and ::getDisabled.
   */
  public function testWholeService() {
    $this->initializeService();
    // Tests \Drupal\purge\Queuer\ServiceInterface::current
    // Tests \Drupal\purge\Queuer\ServiceInterface::key
    // Tests \Drupal\purge\Queuer\ServiceInterface::next
    // Tests \Drupal\purge\Queuer\ServiceInterface::rewind
    // Tests \Drupal\purge\Queuer\ServiceInterface::valid
    $this->assertTrue($this->service instanceof \Iterator);
    $items = 0;
    foreach ($this->service as $id => $queuer) {
      $this->assertTrue($queuer instanceof QueuerInterface);
      $this->assertTrue(in_array($id, [
        'purge.queuers.cache_tags',
        'purge_queuer_test.queuera',
        'purge_queuer_test.queuerb',
        'purge_queuer_test.queuerc']));
      $items++;
    }
    $this->assertEqual(4, $items);
    $this->assertFalse($this->service->current());
    $this->assertFalse($this->service->valid());
    $this->assertNull($this->service->rewind());
    $this->assertEqual('purge.queuers.cache_tags', $this->service->current()->getId());
    $this->assertNull($this->service->next());
    $this->assertEqual('purge_queuer_test.queuera', $this->service->current()->getId());
    $this->assertTrue($this->service->valid());
    // Tests \Drupal\purge\Queuer\ServiceInterface::getEnabled.
    $this->assertEqual(2, count($this->service->getEnabled()));
    foreach ($this->service->getEnabled() as $id => $queuer) {
      $this->assertTrue(in_array($id, ['purge.queuers.cache_tags', 'purge_queuer_test.queuera']), $id);
    }
    // Tests \Drupal\purge\Queuer\ServiceInterface::getDisabled
    $this->assertEqual(2, count($this->service->getDisabled()));
    foreach ($this->service->getDisabled() as $id => $queuer) {
      $this->assertTrue(in_array($id, ['purge_queuer_test.queuerb', 'purge_queuer_test.queuerc']), $id);
    }
  }

}
