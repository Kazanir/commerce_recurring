<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\commerce_recurring\RecurringCron;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Tests the logic to determine which orders should be refreshed.
 *
 * @group commerce_recurring
 */
class RecurringOrderRefreshTest extends CommerceRecurringKernelTestBase {
  
  protected function setUp() {
    parent::setUp(); // TODO: Change the autogenerated stub

    \Drupal::getContainer()->set('datetime.time', new CustomTime(\Drupal::time()->getRequestTime()));
  }

  /**
   * Tests the logic to fill up the recurring order queue for refresh and close.
   */
  public function testRecurringOrderRefreshQueue() {
    // Create a recurring order by creating a subscription.
    $currentUser = $this->createUser([], []);
    \Drupal::currentUser()->setAccount($currentUser);

    $subscription = Subscription::create([
      'type' => 'repeated_order',
      'billing_schedule' => $this->billingSchedule,
      'uid' => $currentUser,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'amount' => new Price('2', 'USD'),
      'state' => 'pending',
      'started' => \Drupal::time()->getRequestTime() - 5,
      'ended' => \Drupal::time()->getRequestTime() + 1000,
    ]);
    $subscription->save();

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $result = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->pager(1)
      ->execute();
    $this->assertEmpty($result);

    $subscription->getState()->applyTransition($subscription->getState()->getTransitions()['activate']);
    $subscription->save();

    $orders = $order_storage->loadMultiple($order_storage->getQuery()
      ->condition('type', 'recurring')
      ->execute());
    $this->assertCount(1, $orders);
    $order = reset($orders);

    // Ensure the refresh queue is empty.
    $this->assertEquals(0, \Drupal::queue('commerce_recurring_refresh')->numberOfItems());

    // Fast forward in time and run cron.
    
    \Drupal::time()->setTime($subscription->get('started')->value + 100);
    // We don't trigger the cron directly as this processes the queue items
    // already.
    RecurringCron::create(\Drupal::getContainer())->cron();

    $this->assertEquals(1, \Drupal::queue('commerce_recurring_order_refresh')->numberOfItems());
    $this->assertEquals(['order_id' => $order->id()], \Drupal::queue('commerce_recurring_order_refresh')->claimItem()->data);
    $this->assertEquals(1, \Drupal::queue('commerce_recurring_order_close')->numberOfItems());
    $this->assertEquals(['order_id' => $order->id()], \Drupal::queue('commerce_recurring_order_close')->claimItem()->data);
  }

  /**
   * Tests the actual logic of recurring a recurring order.
   */
  public function testRecurringOrderRefreshLogic() {
    // Create a recurring order by creating a subscription.
    $currentUser = $this->createUser();
    \Drupal::currentUser()->setAccount($currentUser);

    $subscription = Subscription::create([
      'type' => 'repeated_order',
      'billing_schedule' => $this->billingSchedule,
      'uid' => $currentUser,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'amount' => new Price('2', 'USD'),
      'state' => 'pending',
      'started' => \Drupal::time()->getRequestTime() - 5,
      'ended' => \Drupal::time()->getRequestTime() + 1000,
    ]);
    $subscription->save();

    $subscription->getState()->applyTransition($subscription->getState()->getTransitions()['activate']);
    $subscription->save();

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $orders = $order_storage->loadMultiple($order_storage->getQuery()
      ->condition('type', 'recurring')
      ->execute());
    $this->assertCount(1, $orders);
    $order = reset($orders);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $next_order */
    $next_order = $subscription->getType()->refreshRecurringOrder($subscription, $order);
    $this->assertNotEquals($next_order->id(), $order->id());

    $this->assertEquals('recurring', $next_order->bundle());
    $this->assertEquals(\Drupal::time()->getRequestTime() + 45, $next_order->get('started')->value);
    $this->assertEquals(\Drupal::time()->getRequestTime() + 95, $next_order->get('ended')->value);
    $this->assertCount(1, $next_order->getItems());
    $this->assertEquals(2, $next_order->getItems()[0]->getUnitPrice()->getNumber());
    $this->assertEquals('recurring', $next_order->getItems()[0]->bundle());
    $this->assertEquals(1, $next_order->getItems()[0]->getQuantity());
  }

}

class CustomTime implements TimeInterface {

  /**
   * @var int
   */
  protected $time;

  /**
   * CustomTime constructor.
   * @param int $time
   */
  public function __construct($time) {
    $this->time = $time;
  }

  /**
   * @param int $time
   */
  public function setTime($time) {
    $this->time = $time;
  }

  public function getRequestTime() {
    return $this->time;
  }

  public function getRequestMicroTime() {
    return $this->time;
  }

  public function getCurrentTime() {
    return $this->time;
  }

  public function getCurrentMicroTime() {
    return $this->time;
  }

}
