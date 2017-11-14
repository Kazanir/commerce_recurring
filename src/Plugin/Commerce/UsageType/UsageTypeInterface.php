<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\UsageType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\BillingCycle;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Usage group plugin type.
 */
interface UsageTypeInterface {
  // @TODO: How to do this?
  /**
   * Returns the list of required interfaces which a subscription type must
   * implement in order to leverage usage groups of this type.
   *
   * This allows usage groups to require the subscription type (which also
   * returns the list of usage groups) to implement specific logic around
   * free and initial quantities which are not required by all usage group
   * types, and which need to be implemented by the subscription type as
   * their logic can vary from usage group to usage group. (The alternative
   * is putting callbacks into the usage group definition.)
   *
   * @return string[]
   */
  public static function requiredSubscriptionTypeInterfaces();

  /**
   * Determines whether this usage group plugin should block a given property
   * of a subscription from being changed.
   *
   * @param string $property
   *   The property which is being changed.
   *
   * @param mixed $currentValue
   *   The current value of the property.
   *
   * @param mixed $newValue
   *   The new proposed value of the property.
   */
  public function enforceChangeScheduling($property, $currentValue, $newValue);

  /**
   * Returns a list of usage records for this usage group and a given recurring
   * order.
   *
   * @TODO: Should this be on the group itself, or are we spawning a plugin for
   * each group?
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order for which usage
   */
  public function usageHistory(BillingCycle $cycle);

  /**
   * Adds usage for this usage group and subscription and recurring order.
   * Because this function's parameters change with each implementation, we
   * declare the interface method with a single variadic parameter, allowing
   * each implementation to override it with its own list of more specific
   * parameters if desired.
   *
   * @param mixed $usage
   *   The usage being added. In default implementations this is an int, but
   *   we leave it as mixed here so that other implementations feel free to
   *   modify this for their own use if they really want.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start time for this record.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The end time for this record.
   */
  public function addUsage($usage, DrupalDateTime $start, DrupalDateTime $end);

  /**
   * Gets the current usage (normally an integer, but who knows) for this usage
   * group. This is a convenience method that would (in 1.x) look up the current
   * recurring order for a user and get the usage based on this, but this is
   * probably brittle and it isn't clear if we want to keep this around.
   *
   * @TODO: Follow up on this.
   */
  public function currentUsage();

  /**
   * Checks whether usage records are complete for a given recurring
   * order or whether the subscription needs to "wait" on remote
   * services that might record usage data into the system later.
   *
   * @param \Drupal\commerce_order\OrderInterface $order
   *   The order for which usage completion should be checked.
   */
  public function isComplete(BillingCycle $cycle);

  /**
   * Returns the charges for this group and a given recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface
   *   The order for which charges are being computed.
   *
   * @return \Drupal\commerce_recurring\Charge[]
   *   The computed list of charges.
   */
  public function getCharges(BillingCycle $cycle);

  /**
   * We need something to react to changes in the subscription workflow
   * and plan. This is used both for groups with "default" usage to register
   * it as well as to make sure that groups which depend on plan changes or
   * timing can insert or modify records appropriately in reaction.
   *
   * @TODO: Figure out the parameters for this, if any.
   */
  public function onSubscriptionChange();
}

