<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\UsageType;

use Drupal\commerce_recurring\BillingCycle;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_recurring\Usage\UsageRecordStorageInterface;

/**
 * Usage type plugin base class.
 *
 * Implements logic which is likely to be shared between all implementations.
 */
abstract class UsageTypeBase implements UsageTypeInterface {

  /**
   * The usage record storage class.
   *
   * @var \Drupal\commerce_recurring\Usage\UsageRecordStorageInterface
   */
  protected $storage;

  /**
   * The group name.
   *
   * @var string
   */
  protected $name;

  /**
   * The group definition/info.
   *
   * @var array
   */
  protected $info;

  /**
   * The subscription entity which owns this instance of the usage group.
   *
   * @var \Drupal\commerce_recurring\Entity\SubscriptionInterface
   */
  protected $subscription;

  /**
   * Instantiate a new usage type plugin.
   */
  public function __construct(UsageRecordStorageInterface $storage, $name, $info, SubscriptionInterface $subscription) {
    $this->storage = $storage;
    $this->name = $name;
    $this->info = $info;

    // We have to make sure that the subscription implements the necessary
    // interfaces to work with these usage groups.
    //
    // @see \Drupal\commerce_recurring\UsageTypeInterface::requiredSubscriptionInterfaces()
    foreach ($this->requiredSubscriptionTypeInterfaces() as $interface) {
      if (!($this->subscription->getType() instanceof $interface)) {
        throw new \LogicException('Usage groups of type ' . static::class . ' can only be attached to subscription types which implement ' . $interface);
      }
    }

    $this->subscription = $subscription;
  }

  /**
   * Get the subscription of this usage group.
   *
   * @return \Drupal\commerce_recurring\Entity\SubscriptionInterface
   */
  public function getSubscription() {
    return $this->subscription;
  }

  /**
   * Get the name of the usage group.
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get a part of the usage group definition.
   *
   * @param string $property
   *   The property or group info key to get.
   */
  public function getInfo($property) {
    if (!empty($this->info[$property])) {
      return $this->info[$property];
    }
  }

  /**
   * The default behavior is for usage groups to not enforce change scheduling.
   */
  public function enforceChangeScheduling($property, $oldValue, $newValue) {
    return FALSE;
  }

  /**
   * The default behavior is to regard usage as complete. Usage types with
   * remote storage or record completeness requirements override this method.
   */
  public function isComplete() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function usageHistory(BillingCycle $cycle) {
    // Here we fetch the records from storage, and then massage them to line
    // up with the start and end of the billing cycle.
    $records = $this->storage->fetchCycleRecords($cycle);
    $cycleStart = $cycle->getStartDate()->getTimestamp();
    $cycleEnd = $cycle->getEndDate()->getTimestamp();

    foreach ($records as $record) {
      if ($record->getStart() < $cycleStart) {
        $record->setStart($cycleStart);
      }
      if (is_null($record->getEnd()) || $record->getEnd() > $cycleEnd) {
        $record->setEnd($cycleEnd);
      }
    }

    // That's all we need to do here.
    // @TODO: The original module had static caching for this...worth it?
    // I don't know what the new D8 patterns for this are.
    return $records;
  }
}

