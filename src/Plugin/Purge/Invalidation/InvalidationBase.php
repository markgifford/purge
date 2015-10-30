<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase.
 */

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException;

/**
 * Provides base implementations for the invalidation object.
 *
 * Invalidations are small value objects that decribe and track invalidations
 * on one or more external caching systems within the Purge pipeline. These
 * objects can be directly instantiated from InvalidationsService and float
 * freely between the QueueService and the PurgersService.
 */
abstract class InvalidationBase extends ImmutableInvalidationBase implements InvalidationInterface {

  /**
   * Constructs \Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param int $id
   *   Unique integer ID for this object instance (during runtime).
   * @param mixed|null $expression
   *   Value - usually string - that describes the kind of invalidation, NULL
   *   when the type of invalidation doesn't require $expression. Types usually
   *   validate the given expression and throw exceptions for bad input.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $id, $expression) {
    parent::__construct([], $plugin_id, $plugin_definition);
    $this->id = $id;
    $this->expression = $expression;
    $this->validateExpression();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      [],
      $plugin_id,
      $plugin_definition,
      $configuration['id'],
      $configuration['expression']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    if (!is_int($state)) {
      throw new InvalidStateException('$state not an integer!');
    }
    if (($state < 0) || ($state > 4)) {
      throw new InvalidStateException('$state is out of range!');
    }
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    $plugin_id = $this->getPluginId();
    $d = $this->getPluginDefinition();
    $topt = ['@type' => strtolower($d['label'])];
    if ($d['expression_required'] && is_null($this->expression)) {
      throw new MissingExpressionException($this->t("Argument required for @type invalidation.", $topt));
    }
    elseif ($d['expression_required'] && empty($this->expression) && !$d['expression_can_be_empty']) {
      throw new InvalidExpressionException($this->t("Argument required for @type invalidation.", $topt));
    }
    elseif (!$d['expression_required'] && !is_null($this->expression)) {
      throw new InvalidExpressionException($this->t("Argument given for @type invalidation.", $topt));
    }
    elseif (!is_null($this->expression) && !is_string($this->expression) && $d['expression_must_be_string']) {
      throw new InvalidExpressionException($this->t("String argument required for @type invalidation.", $topt));
    }
  }

}