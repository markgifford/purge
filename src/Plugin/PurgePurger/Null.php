<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgePurger\Null.
 */

namespace Drupal\purge\Plugin\PurgePurger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Purger\PluginBase;
use Drupal\purge\Purger\PluginInterface;
use Drupal\purge\Purgeable\PluginInterface as Purgeable;

/**
 * API-compliant null purger back-end.
 *
 * This plugin is not intended for usage but gets loaded during module
 * installation, when configuration rendered invalid or when no other plugins
 * are available. Because its API compliant, Drupal won't crash visibly.
 *
 * @PurgePurger(
 *   id = "null",
 *   label = @Translation("Null"),
 *   description = @Translation("API-compliant null purger back-end."),
 * )
 */
class Null extends PluginBase implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function purge(Purgeable $purgeable) {
    $this->numberFailed += 1;
    $purgeable->setState(Purgeable::STATE_PURGEFAILED);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function purgeMultiple(array $purgeables) {
    foreach ($purgeables as $purgeable) {
      $this->numberFailed += 1;
      $purgeable->setState(Purgeable::STATE_PURGEFAILED);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCapacityLimit() {
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimTimeHint() {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberPurging() {
    return 0;
  }
}
