<?php

namespace Drupal\commerce_sheets\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber that sets the completed timestamp for an import.
 */
class SetCompletedTimestamp implements EventSubscriberInterface {

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new SetCompletedTimestamp object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_sheets_import.cancel.pre_transition' => 'setTimestamp',
      'commerce_sheets_import.complete.pre_transition' => 'setTimestamp',
      'commerce_sheets_import.fail.pre_transition' => 'setTimestamp',
    ];
    return $events;
  }

  /**
   * Sets the import's completed timestamp, if not already set.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function setTimestamp(WorkflowTransitionEvent $event) {
    $import = $event->getEntity();
    if (!$import->getCompletedTime()) {
      $import->setCompletedTime($this->time->getCurrentTime());
    }
  }

}
