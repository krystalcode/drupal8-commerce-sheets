<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerBase;

/**
 * Provides a handler plugin for boolean fields.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   field_types = {
 *     "boolean"
 *   }
 * )
 *
 * @I Investigate and use Bool cell data type
 */
class Boolean extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function toCellValue($field) {
    // @I Make the format of the boolean value configurable
    return $field->value ? 'Y' : 'N';
  }

}
