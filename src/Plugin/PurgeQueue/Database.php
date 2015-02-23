<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\PurgeQueue\Database.
 */

namespace Drupal\purge\Plugin\PurgeQueue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\QueueDatabaseFactory;
use Drupal\purge\Queue\PluginInterface as Queue;
use Drupal\purge\Queue\PluginBase;

/**
 * A \Drupal\purge\Queue\PluginInterface compliant database backed queue.
 *
 * @PurgeQueue(
 *   id = "database",
 *   label = @Translation("Database"),
 *   description = @Translation("A scalable database backed queue."),
 * )
 */
class Database extends PluginBase implements Queue {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var \Drupal\Core\Queue\QueueDatabaseFactory
   */
  protected $queueDatabase;

  /**
   * Holds the 'queue.database' queue retrieved from Drupal.
   */
  protected $dbqueue;

  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  /**
   * Constructs a \Drupal\purge\Plugin\PurgeQueue\Database object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\Queue\QueueDatabaseFactory $queue_database
   *   The 'queue.database' service creating database queue objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, QueueDatabaseFactory $queue_database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->queueDatabase = $queue_database;

    // The name of the database queue we are storing items in.
    $this->name = 'purge';

    // Instantiate the database queue using the factory.
    $this->dbqueue = $this->queueDatabase->get($this->name);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('queue.database')
    );
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createItem().
   */
  public function createItem($data) {
    return $this->dbqueue->createItem($data);
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $item_ids = $records = [];

    // Build a array with all exactly records as they should turn into rows.
    $time = time();
    foreach ($items as $data) {
      $records[] = [
        'name' => $this->name,
        'data' => serialize($data),
        'created' => $time,
      ];
    }

    // Insert all of them using just one multi-row query.
    $query = db_insert('queue')->fields(['name', 'data', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }

    // Execute the query and finish the call.
    if ($id = $query->execute()) {
      $id = (int)$id;

      // A multiple row-insert doesn't give back all the individual IDs, so
      // calculate them back by applying subtraction.
      for ($i = 1; $i <= count($records); $i++) {
        $item_ids[] = $id;
        $id++;
      }
      return $item_ids;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::numberOfItems().
   */
  public function numberOfItems() {
    return $this->dbqueue->numberOfItems();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::claimItem().
   */
  public function claimItem($lease_time = 3600) {
    if ($item = $this->dbqueue->claimItem($lease_time)) {
      $item->item_id = (int)$item->item_id;
      return $item;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600) {
    $returned_items = $item_ids = [];

    // Retrieve all items in one query.
    $items = $this->database->queryRange('SELECT data, created, item_id FROM {queue} q WHERE expire = 0 AND name = :name ORDER BY created ASC', 0, $claims, [':name' => $this->name]);

    // Iterate all returned items and unpack them.
    foreach ($items as $item) {
      if (!$item) continue;
      $item_ids[] = $item->item_id;
      $item->item_id = (int)$item->item_id;
      $item->data = unserialize($item->data);
      $returned_items[] = $item;
    }

    // Update the items (marking them claimed) in one query.
    if (count($returned_items)) {
      $update = $this->database->update('queue')
        ->fields([
          'expire' => time() + $lease_time,
        ])
        ->condition('item_id', $item_ids, 'IN')
        ->condition('expire', 0)
        ->execute();
    }

    // Return the generated items, whether its empty or not.
    return $returned_items;
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::releaseItem().
   */
  public function releaseItem($item) {
    return $this->dbqueue->releaseItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItemMultiple(array $items) {
    $item_ids = [];
    foreach ($items as $item) {
      $item_ids[] = $item->item_id;
    }
    $update = $this->database->update('queue')
      ->fields([
        'expire' => 0,
      ])
      ->condition('item_id', $item_ids, 'IN')
      ->execute();
    if ($update) {
      return [];
    }
    else {
      return $items;
    }
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteItem().
   */
  public function deleteItem($item) {
    return $this->dbqueue->deleteItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemMultiple(array $items) {
    $item_ids = [];
    foreach ($items as $item) {
      $item_ids[] = $item->item_id;
    }
    $update = $this->database
      ->delete('queue')
      ->condition('item_id', $item_ids, 'IN')
      ->execute();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createQueue().
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created when
    // Drupal is first installed) so there is nothing we need to do to create
    // a new queue.
    return $this->dbqueue->createQueue();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteQueue().
   */
  public function deleteQueue() {
    return $this->dbqueue->deleteQueue();
  }
}
