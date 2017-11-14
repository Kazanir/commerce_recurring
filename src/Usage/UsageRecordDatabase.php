<?php

namespace Drupal\commerce_recurring\Usage;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class UsageRecordDatabase {

  /**
   * The ID of this usage record, if it has been saved to storage.
   *
   * @var int
   */
  protected $usage_id = NULL;

  /**
   * The name of the usage group to which this record belongs.
   *
   * @var string
   */
  protected $usage_group;

  /**
   * The ID of this record's subscription, if any.
   *
   * @var int
   */
  protected $subscription_id;

  /**
   * The ID of this record's product variation, if any.
   *
   * @var int
   */
  protected $product_variation_id;

  /**
   * The quantity of this record.
   *
   * @var int
   */
  protected $quantity;

  /**
   * The start timestamp of this record.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $start;

  /**
   * The end timestamp of this record.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $end;

  /**
   * The Drupal entity type manager. Used to load subscriptions and products
   *   since these database records only store IDs.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $typeManager;

  /**
   * Get an array of values for easy insertion via the Drupal database layer.
   *
   * @return array
   */
  public function getDatabaseValues() {
    return [
      'usage_id' => $this->usage_id,
      'usage_group' => $this->usage_group,
      'subscription_id' => $this->subscription_id,
      'product_variation_id' => $this->product_variation_id,
      'quantity' => $this->quantity,
      'start' => $this->start,
      'end' => $this->end,
    ];
  }

  /**
   * Class-specific constructor which injects the storage service for use later.
   *
   * @param UsageRecordStorageDatabase $storage
   *   The storage engine which created this record.
   */
  public function __construct(EntityTypeManager $typeManager) {
    $this->typeManager = $typeManager;
  }

  /**
   * Get the ID of this record in storage. Used to figure out inserts vs merges
   * in the default database storage implementation.
   *
   * Note: There is no setter for the ID, this can only be set by the storage
   * engine.
   *
   * @return int
   */
  public function getId() {
    return $this->usage_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupName() {
    return $this->usage_group;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupName($groupName) {
    $this->usage_group = $groupName;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscription() {
    $storage = $this->typeManager->getStorage('commerce_subscription');

    return $storage->load($this->subscription_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setSubscription(SubscriptionInterface $subscription) {
    $this->subscription_id = $subscription->id();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductVariation() {
    $storage = $this->typeManager->getStorage('commerce_product_variation');

    return $storage->load($this->product_variation_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setProductVariation(ProductVariationInterface $variation) {
    $this->product_variation_id = $variation->id();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return (int) $this->quantity;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->quantity = (int) $quantity;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return is_null($this->start) ? NULL : new DrupalDateTime($this->start);
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate(DrupalDateTime $start) {
    $this->start = $start->getTimestamp();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStart() {
    return $this->start;
  }

  /**
   * {@inheritdoc}
   */
  public function setStart($start) {
    $this->start = $start;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return is_null($this->end) ? NULL : new DrupalDateTime($this->end);
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate(DrupalDateTime $end) {
    $this->end = $end->getTimestamp();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnd() {
    return $this->end;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnd($end) {
    $this->end = $end;

    return $this;
  }

}

