<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\UsageType;

use Drupal\commerce_recurring\BillingCycle;

class Gauge extends UsageTypeBase {

  /**
   * {@inheritdoc}
   */
  public function requiredSubscriptionTypeInterfaces() {
    return [
      '\Drupal\commerce_recurring\Usage\SubscriptionFreeUsageInterface',
      '\Drupal\commerce_recurring\Usage\SubscriptionInitialUsageInterface',
    ];
  }

  /**
   * Gauge usage groups expect usage records to span the timespan of all
   * active plan history entries within the billing cycle.
   */
  public function isComplete(BillingCycle $cycle) {
    $records = $this->usageHistory($cycle)->fetchCycleRecords($this->name, $this->subscription, $cycle);

    // Get the length of the cycle in seconds.
    $cycleLength = $cycle->getEndTime()->diff($cycle->getStartTime())->format('s') + 1;

    // Add up the length of each record. Note that we use usageHistory() here
    // (instead of the storage fetch method) because we want the records which
    // have already been massaged to start and end with the billing cycle start
    // and end timestamps. Otherwise nothing would ever add up.
    $recordLength = 0;
    foreach ($records as $record) {
      $recordLength += $record->getStart() - $record->getEnd() + 1;
    }

    // These should match for a gauge group.
    //
    // @TODO: Respect non-active subscription statuses once history is locked
    // down.
    return $cycleLength == $recordLength;
  }

  /**
   * Figure out charges.
   */
  public function getCharges(BillingCycle $cycle) {

  }

  /**
   * Gauge usage needs to make sure to move any overlapping records out of the
   * way so that even bad code cannot deliberately violate the completeness
   * rules.
   */
  public function addUsage($quantity, $start, $end = NULL) {
    // Get all the raw records for this group and subscription.
    $records = $this->storage->fetchCycleRecords($this->name, $this->subscription);

    $newRecord = $this->storage->createRecord()
      ->setSubscription($this->subscription)
      ->setProductVariation($this->groupInfo['product_variation'])
      ->setStartDate($start)
      ->setEndDate($end)
      ->setQuantity($quantity);

    $startTime = $start->getTimestamp();
    $endTime = $end->getTimestamp();

    // We store arrays of records to update and delete.
    $updates = [$newRecord];
    $deletions = [];

    foreach ($records as $record) {
      // The first thing to do is find all records which overlap the new record
      // somehow.
      if (($record->getEnd() >= $startTime || $record->getEnd() === NULL) && $record->getStart() < $startTime) {
        $record->setEnd($startTime - 1);
        $updates[] = $record;
      }

      // What else we do to preserve sanity depends on whether this is a
      // completed record or not.
      if ($endTime === NULL) {
        // The new record has no end. That means we merely to need to delete all
        // other records which come after it, if any.
        if ($record->getStart() >= $startTime) {
          $deletions[] = $record;
        }
      }
      else {
        if ($record->getStart() >= $startTime && $record->getEnd <= $endTime) {
          // This record has a start and end already. We delete records that are
          // inside of it.
          $deletions[] = $record;
        }
        elseif ($record->getStart() >= $startTime && $record->getEnd() > $endTime) {
          $record->setStart($endTime + 1);
          $updates[] = $record;
        }
      }
    }

    $this->storage->setRecords($updates);
    $this->storage->deleteRecords($deletions);
  }

  /**
   * Get the current usage for a billing cycle.
   */
  public function currentUsage(BillingCycle $cycle = NULL) {
    // Figure out how to get the current cycle, if one exists.


    /* @var \Drupal\commerce_recurring\Usage\UsageRecordInterface[]*/
    $records = $this->storage->fetchCycleRecords($this->name, $this->subscription, $cycle);
    // Get all the records for the cycle and take the most recent one.
    $latest = NULL;
    foreach ($records as $record) {
      if (is_null($latest) || $record->getStart()->getTimestamp() > $latest->getStart()->getTimestamp()) {
        $latest = $record;
      }
    }

    if (!$latest) {
      // No usage registered.
      return 0;
    }
    else {
      return $latest->getQuantity();
    }
  }


  /**
   * Fix up records when the subscription changes somehow.
   */
  public function onSubscriptionChange() {
    // @TODO
    //
  }

}

