<?php

namespace Drupal\commerce_recurring\Usage;

use Drupal\commerce_recurring\BillingCycle;

/**
 * Storage interface for usage records.
 */
interface UsageRecordStorageInterface {

  /**
   * Fetch all records which pertain to a given group, subscription, and
   * recurring order.
   *
   * @param string $group_name
   *   The group name.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface
   *   The subscription.
   *
   * @param \Drupal\commerce_recurring\BillingCycle
   *   The billing cycle.
   *
   * @return \Drupal\commerce_recurring\Usage\UsageRecordInterface[]
   *   The usage records.
   */
  public function fetchCycleRecords($group_name, $subscription, $cycle);

  /**
   * Create or update one or more usage records.
   *
   * @param \Drupal\commerce_recurring\Usage\UsageRecordInterface[]
   *   The usage records to be created or updated.
   *
   * @return void
   */
  public function setRecords(array $records);

  /**
   * Delete one or more usage records.
   *
   * @param \Drupal\commerce_recurring\Usage\UsageRecordInterface[]
   *   The usage records to be created or updated.
   *
   * @return void
   */
  public function deleteRecords(array $records);

}

