<?php

namespace Drupal\commerce_recurring\Usage;

use Drupal\commerce_recurring\BillingCycle;

interface SubscriptionFreeUsageInterface {
  /**
   * Get the free quantity which corresponds to a given usage charge.
   *
   * This is required by both the Gauge and Counter usage type plugins.
   * Subscription types which want to use usage groups but don't need this
   * behavior can simply return 0 for all group names.
   *
   * @param string $usageGroup
   *   The name of the usage group to which the proposed charge belongs.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation of the proposed charge.
   *
   * @param \Drupal\commerce_recurring\SubscriptionHistoryInterface $history
   *   The subscription history object that corresponds to the proposed charge.
   *
   * @return int
   *   The free quantity to be deducted from the proposed charge.
   */
  public function getFreeQuantity($usageGroup, $variation, $history);
}
