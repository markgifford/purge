<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\TestTrait.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Processor\ServiceInterface as ProcessorsService;
use Drupal\purge\Purger\Service as PurgersService;
use Drupal\purge\Queue\Service as QueueService;

/**
 * Several helper properties and methods for purge tests.
 *
 * @see \Drupal\purge\Tests\KernelTestBase
 * @see \Drupal\purge\Tests\WebTestBase
 */
trait TestTrait {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\purge\Processor\ServiceInterface
   */
  protected $purgeProcessors;

  /**
   * @var \Drupal\purge\Purger\ServiceInterface
   */
  protected $purgePurgers;

  /**
   * @var \Drupal\purge\Invalidation\ServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * @var \Drupal\purge\Queue\ServiceInterface
   */
  protected $purgeQueue;

  /**
   * @var \Drupal\purge\Queuer\ServiceInterface
   */
  protected $purgeQueuers;

  /**
   * @var \Drupal\purge\DiagnosticCheck\ServiceInterface
   */
  protected $purgeDiagnostics;

  /**
   * Make $this->purgeProcessors available.
   */
  protected function initializeProcessorsService() {
    if (is_null($this->purgeProcessors)) {
      $this->purgeProcessors = $this->container->get('purge.processors');
      $this->purgeProcessors->reload();
    }
    else {
      $this->purgeProcessors->reload();
    }
  }

  /**
   * Make $this->purgePurgers available.
   *
   * @param $plugin_ids
   *   Array of plugin ids to be enabled.
   */
  protected function initializePurgersService($plugin_ids = []) {
    if (!empty($plugin_ids)) {
      PurgersService::setPluginsStatic($plugin_ids);
    }
    if (is_null($this->purgePurgers)) {
      $this->purgePurgers = $this->container->get('purge.purgers');
    }
    else {
      $this->purgePurgers->reload();
      if (!is_null($this->purgeDiagnostics)) {
        $this->purgeDiagnostics->reload();
      }
    }
  }

  /**
   * Make $this->purgeInvalidationFactory available.
   */
  protected function initializeInvalidationFactoryService() {
    if (is_null($this->purgeInvalidationFactory)) {
      $this->purgeInvalidationFactory = $this->container->get('purge.invalidation.factory');
    }
  }

  /**
   * Make $this->purgeQueue available.
   *
   * @param $plugin_id
   *   The plugin ID of the queue to be configured.
   */
  protected function initializeQueueService($plugin_id = NULL) {
    if (!empty($plugin_id)) {
      if (is_null($this->purgeQueue)) {
        QueueService::setPluginsStatic([$plugin_id]);
        $this->purgeQueue = $this->container->get('purge.queue');
      }
      else {
        $this->purgeQueue->setPluginsEnabled([$plugin_id]);
      }
    }
    if (is_null($this->purgeQueue)) {
      $this->purgeQueue = $this->container->get('purge.queue');
    }
    else {
      if (!is_null($this->purgeDiagnostics)) {
        $this->purgeDiagnostics->reload();
      }
    }
  }

  /**
   * Make $this->purgeQueuers available.
   */
  protected function initializeQueuersService() {
    if (is_null($this->purgeQueuers)) {
      $this->purgeQueuers = $this->container->get('purge.queuers');
      $this->purgeQueuers->reload();
    }
    else {
      $this->purgeQueuers->reload();
    }
  }

  /**
   * Make $this->purgeDiagnostics available.
   */
  protected function initializeDiagnosticsService() {
    if (is_null($this->purgeDiagnostics)) {
      $this->purgeDiagnostics = $this->container->get('purge.diagnostics');
    }
    else {
      $this->purgeDiagnostics->reload();
    }
  }

  /**
   * Create $number requested invalidation objects.
   *
   * @param int $number
   *   The number of objects to generate.
   *
   * @return array|\Drupal\purge\Invalidation\PluginInterface
   */
  public function getInvalidations($number) {
    $this->initializeInvalidationFactoryService();
    $set = [];
    for ($i = 0; $i < $number; $i++) {
      $set[] = $this->purgeInvalidationFactory->get('everything');
    }
    return ($number === 1) ? $set[0] : $set;
  }

}