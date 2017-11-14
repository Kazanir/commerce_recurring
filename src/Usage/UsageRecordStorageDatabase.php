<?php

namespace Drupal\commerce_recurring\Usage;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_recurring\BillingCycle;
use Drupal\Core\Database\StatementInterface;
use PDO;

/**
 * Provides the default database storage backend for usage records.
 */
class UsageRecordStorageDatabase implements UsageRecordStorageInterface {

  /**
   * The table name to query from.
   *
   * @var string
   */
  protected $tableName = 'commerce_recurring_usage';

  /**
   * The usage record class to use.
   *
   * @var string
   */
  protected $recordClass = 'UsageRecordDatabase';

  /**
   * The database connection in use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the usage record storage.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for usage record storage.
   *
   * @param string $recordClass
   *   The fully-qualified name of the record class to be used.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $typeManager
   *   The entity type manager service.
   *
   */
  public function __construct(Connection $connection, $recordClass, $typeManager) {
    $this->connection = $connection;
    $this->recordClass = $recordClass;
    $this->typeManager = $typeManager;
  }

  /**
   * Fetch all records which pertain to a given group, subscription, and
   * recurring order.
   *
   * @param string $groupName
   *   The group name. Required.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription. Optional.
   *
   * @param \Drupal\commerce_recurring\BillingCycle $cycle
   *   The billing cycle. Optional.
   */
  public function fetchCycleRecords($groupName, SubscriptionInterface $subscription = NULL, BillingCycle $cycle = NULL) {
    $query = $this->connection->select($this->tableName);
    $query->condition('usage_group', $groupName);
    if (!is_null($subscription)) {
      $query->condition('subscription_id', $subscription->getIdentifier());
    }
    if (!is_null($cycle)) {
      // To accurately get all records, we need to find any that overlap with
      // the time period of the billing cycle.
      $start = $cycle->getStartDate();
      $end = $cycle->getEndDate();

      // Since some usage records have no end, we need to search for any which
      // end later than the cycle's start date or have no end.
      $ends = $query->orConditionGroup()
        ->condition('end', $start->format('U'), '>')
        ->isNull('end');

      // Combine that with a condition to find those which start earlier than
      // the cycle's end date and we have everything we need.
      $timing = $query->andConditionGroup()
        ->condition('start', $end->format('U'), '<')
        ->condition($ends);

      // Et voila.
      $query->condition($timing);
    }

    $query->addTag('commerce_recurring_usage');

    $results = $query->execute();

    if (!is_null($results)) {
      return $this->createFromStorage($results);
    }
    else {
      return [];
    }
  }

  /**
   * Factory method for turning raw records (from the database) into record
   *   objects. Leverages PDO::FETCH_CLASS
   *
   * @param array $raw
   *   The raw records as associative arrays.
   *
   * @return \Drupal\commerce_recurring\Usage\UsageRecordInterface[]
   *   The usage record objects.
   */
  public function createFromStorage(StatementInterface $results) {
    $results->setFetchMode(PDO::FETCH_CLASS, $this->recordClass, [$this->typeManager]);

    return $results->fetchAll();
  }

  /**
   * Create a new usage record object shell. This injects the type manager
   * service so the record can use it to fetch stuff.
   */
  public function createRecord() {
    $recordClass = $this->recordClass;
    // Syntax is a pain.
    $record = new $recordClass($this->typeManager);

    return $record;
  }

  /**
   * Insert a usage record.
   *
   * @param \Drupal\commerce_recurring\Usage\UsageRecordInterface[] $record
   *   The usage record to be inserted.
   */
  public function setRecords(array $records) {
    $txn = $this->connection->startTransaction();

    $inserts = [];
    $updates = [];
    foreach ($records as $record) {
      if ($record->getId()) {
        // Records which already have an ID must be updated.
        $updates[] = $record->getDatabaseValues();
      }
      else {
        $inserts[] = $record->getDatabaseValues();
      }
    }

    try {
      if (!empty($updates)) {
        foreach ($updates as $update) {
          $count = $this->connection->update($this->tableName)
            ->fields($update)
            ->condition('usage_id', $update['usage_id'])
            ->execute();

          // The number of rows matched had damn well better be 1.
          if ($count != 1) {
            throw new \LogicException("Failed to update usage record $update[usage_id].");
          }
        }
      }

      if (!empty($inserts)) {
        $query = $this->connection->insert($this->tableName);
        foreach ($inserts as $insert) {
          $query->values($insert);
        }

        // Unfortunately, we can't really check this to make sure the right
        // number were inserted without a lot more magic. Maybe @TODO?
        $query->execute();
      }
    }
    catch (\Exception $e) {
      // Roll this back.
      $txn->rollback();
      throw $e;
    }

    // We're done. Yay.
    $txn->commit();
  }

  /**
   * Delete one or more usage records.
   *
   * @param \Drupal\commerce_recurring\Usage\UsageRecordInterface[] $records
   *   The usage records to be deleted.
   */
  public function deleteRecords(array $records) {
    $txn = $this->connection->startTransaction();

    try {
      // Delete each record.
      foreach ($records as $record) {
        if ($record->getId()) {
          $this->connection->delete($this->tableName)
            ->condition('usage_id', $record->getId())
            ->execute();
        }
      }
    }
    catch (\Exception $e) {
      $txn->rollback();
      throw $e;
    }

    // We're done. Yay.
  }
}

