<?php

namespace Drupal\commerce_recurring\Usage;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

interface UsageRecordInterface {

  /**
   * Get the group name to which this record belongs.
   *
   * @return string
   */
  public function getGroupName();

  /**
   * Set the group name to which this record belongs.
   *
   * @param string $groupName
   *
   * @return this
   */
  public function setGroupName($groupName);

  /**
   * Get the subscription with which this record is associated.
   *
   * @return \Drupal\commerce_recurring\Entity\SubscriptionInterface
   */
  public function getSubscription();

  /**
   * Set the subscription with which this record is associated.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *
   * @return self
   */
  public function setSubscription(SubscriptionInterface $subscription);

  /**
   * Get the product variation which this record will charge for.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  public function getProductVariation();

  /**
   * Set the product variation which this record will charge for.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *
   * @return self
   */
  public function setProductVariation(ProductVariationInterface $variation);

  /**
   * Get the quantity of this record.
   *
   * @return int
   */
  public function getQuantity();

  /**
   * Set the quantity of this record.
   *
   * @param int $quantity
   *
   * @return self
   */
  public function setQuantity($quantity);

    /**
   * Get the start time of this record.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getStartDate();

  /**
   * Set the start time of this record.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *
   * @return self
   */
  public function setStartDate(DrupalDateTime $start);

  /**
   * Get the start timestamp of this record.
   *
   * @return int
   */
  public function getStart();

  /**
   * Set the start timestamp of this record.
   *
   * @param int|NULL $start
   *
   * @return self
   */
  public function setStart($start);

  /**
   * Get the end time of this record.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getEndDate();

  /**
   * Set the end time of this record.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *
   * @return self
   */
  public function setEndDate(DrupalDateTime $end);

  /**
   * Get the end timestamp of this record.
   *
   * @return int
   */
  public function getEnd();

  /**
   * Set the end timestamp of this record.
   *
   * @param int|NULL $end
   *
   * @return self
   */
  public function setEnd(DrupalDateTime $end);
}

