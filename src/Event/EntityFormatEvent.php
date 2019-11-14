<?php

namespace Drupal\commerce_sheets\Event;

use Drupal\commerce_sheets\EntityFormat\EntityFormatInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event object for events that allow altering entity formats.
 */
class EntityFormatEvent extends Event {

  /**
   * The entity format plugin.
   *
   * @var \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface
   */
  protected $format;

  /**
   * Constructs a new EntityFormatEvent object.
   *
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The format plugin object.
   */
  public function __construct(EntityFormatInterface $format) {
    $this->format = $format;
  }

  /**
   * Returns the format plugin object.
   *
   * @return \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface
   *   The format plugin object.
   */
  public function getFormat() {
    return $this->format;
  }

}
