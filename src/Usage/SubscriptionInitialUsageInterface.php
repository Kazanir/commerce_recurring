<?php

namespace Drupal\commerce_recurring\Usage;

use Drupal\commerce_recurring\BillingCycle;

interface SubscriptionInitialUsageInterface {
  /**
   * Get the initial quantity which should be registered for the usage group.
   *
   * This is required by just the Gauge usage type for registering usage when
   * a new subscription history modification is made or when the subscription
   * is initially activated.
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
  public function getInitialUsage($usageGroup, $variation, $history);
}
