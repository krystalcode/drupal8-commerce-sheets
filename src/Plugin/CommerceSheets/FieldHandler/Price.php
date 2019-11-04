<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerBase;

/**
 * Provides a handler plugin for price fields.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "price",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class Price extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function toCellValue($field) {
    if ($field->isEmpty()) {
      return;
    }

    return (string) $field->first()->toPrice();
  }

}
