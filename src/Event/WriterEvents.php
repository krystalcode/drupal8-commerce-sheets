<?php

namespace Drupal\commerce_sheets\Event;

/**
 * Defines events that occur during writing.
 */
final class WriterEvents {

  /**
   * Name of the event fired before writing a property to the spreadsheet.
   *
   * Allows defining the value that will be written.
   *
   * @Event
   *
   * @see \Drupal\commerce_sheets\Event\WriterPropertyValueEvent
   */
  const PROPERTY_PRE_WRITE = 'commerce_sheets.writer.property_pre_write';

}
