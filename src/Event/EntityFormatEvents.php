<?php

namespace Drupal\commerce_sheets\Event;

/**
 * Defines events related to entity formats.
 */
final class EntityFormatEvents {

  /**
   * Name of the event fired before instantiating an entity format plugin.
   *
   * It allows altering the format by altering the plugin's configuration.
   *
   * @Event
   *
   * @see \Drupal\commerce_sheets\Event\EntityFormatPreConstructEvent
   */
  const PRE_CONSTRUCT = 'commerce_sheets.entity_format.pre_construct';

  /**
   * Name of the event fired after initializing property and plugin definitions.
   *
   * @Event
   *
   * @see \Drupal\commerce_sheets\Event\EntityFormatEvent
   */
  const POST_INIT = 'commerce_sheets.entity_format.post_init';

}
