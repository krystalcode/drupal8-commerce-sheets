<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerBase;

/**
 * Provides a handler plugin for text fields.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "text",
 *   label = @Translation("Text"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class Text extends FieldHandlerBase {

}
