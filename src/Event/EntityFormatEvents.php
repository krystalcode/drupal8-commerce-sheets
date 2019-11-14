<?php

namespace Drupal\commerce_sheets\Event;

/**
 * Defines events related to entity formats.
 */
final class EntityFormatEvents {

  /**
   * Name of the event fired after initializing property and plugin definitions.
   *
   * @Event
   *
   * @see \Drupal\commerce_cart\Event\EntityFormatEvent
   */
  const POST_INIT = 'commerce_sheets.entity_format.post_init';

}
