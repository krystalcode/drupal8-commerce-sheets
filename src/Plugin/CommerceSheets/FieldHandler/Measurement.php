<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerBase;

/**
 * Provides a handler plugin for measurement fields.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "measurement",
 *   label = @Translation("Measurement"),
 *   field_types = {
 *     "physical_measurement"
 *   }
 * )
 */
class Measurement extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function toCellValue($field) {
    if ($field->isEmpty()) {
      return;
    }

    return (string) $field->first()->toMeasurement();
  }

}
