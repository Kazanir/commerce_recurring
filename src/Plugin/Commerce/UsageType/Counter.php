<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\UsageType;

use Drupal\commerce_recurring\BillingCycle;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_recurring\Usage\UsageRecord;

class Counter extends UsageTypeBase {

  /**
   * {@inheritdoc}
   */
  public function requiredSubscriptionTypeInterfaces() {
    return [
      '\Drupal\commerce_recurring\Usage\SubscriptionFreeUsageInterface',
    ];
  }

  /**
   * Counter usage just needs to be registered for the given start date. The
   * end date argument (if any) is ignored.
   */
  public function addUsage($quantity, DrupalDateTime $start, DrupalDateTime $end = NULL) {
    $end = $start;
    $record = $this->storage->createRecord()
      ->setSubscription($this->subscription)
      ->setProductVariation($this->groupInfo['product_variation'])
      ->setStartDate($start)
      ->setEndDate($end)
      ->setQuantity($quantity);

    // Counter usage is simple. We set up the record and store it.
    $this->storage->setRecords([$record]);
  }

  /**
   * {@inheritdoc}
   */
  public function currentUsage(BillingCycle $cycle) {
    // @TODO: Figure out how to get the current cycle, if one exists.


    /* @var \Drupal\commerce_recurring\Usage\UsageRecordInterface[]*/
    $records = $this->usageHistory($cycle);
    // Sum up the quantity of all records in the cycle.
    $quantity = 0;
    foreach ($records as $record) {
      $quantity += $record->getQuantity();
    }

    return $quantity;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: Define what a charge is and how to make one
   * @TODO: Write the subscription history interface/class
   */
  public function getCharges(BillingCycle $cycle) {
    // Add up all of the counter records, grouping by product variation ID in
    // case someone decided to get fancy.
    $records = $this->usageHistory($cycle);
    foreach ($records as $record) {
      $variationId = $record->getProductVariation()->id();
      $variations[$variationId] = $record->getProductVariation();
    }
    // We only want a unique list of the available product variation IDs.
    $variationIds = array_unique(array_keys($variations));

    foreach ($histories as $history) {

      $quantities = array_fill_keys($variationIds, 0);
      foreach ($records as $record) {
        // Check if this record belongs in this history.
        if ($record->getStart() >= $history->getStart() && $record->getStart() <= $history->getEnd()) {
          $id = $record->getProductVariation->id();
          $quantities[$id] += $record->getQuantity();
        }
      }

      // Now we have a set of quantities keyed by product. Use the license to
      // get a free quantity for each.
      foreach ($quantities as $variationId => $quantity) {
        $freeQuantity = $this->subscription->getFreeQuantity($this->name, $history->getId(), $variations[$variationId]);
        $quantities[$variationId] = max(0, $quantity - $freeQuantity);
      }

      // Add the variation-keyed quantity list to this history ID for later turning into charges.
      $finalQuantities[$history->getId()] = $quantities;
    }

    // Now we generate charges.
    $charges[] = [];

    foreach ($finalQuantities as $historyId => $productQuantities) {
      foreach ($productQuantities as $variationId => $quantity) {
        $this->generateCharge($quantity, $variations[$variationId], $histories[$historyId]);
      }
    }

    return $charges;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateCharge($quantity, $productVariation, $subscriptionHistory) {
    // @TODO: Figure this part out! Yay.
  }

}
