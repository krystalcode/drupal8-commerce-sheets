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
  public function fromCellGetValue($cell) {
    $value = $cell->getValue();
    if (!$value) {
      return;
    }

    $value_parts = explode(' ', $value);
    return [
      'number' => $value_parts[0],
      'unit' => $value_parts[1],
    ];
  }

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
