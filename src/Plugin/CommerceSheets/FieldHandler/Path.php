<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerBase;

/**
 * Provides a handler plugin for path fields.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "path",
 *   label = @Translation("Path"),
 *   field_types = {
 *     "path"
 *   }
 * )
 */
class Path extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function fromCellToField($cell, $field) {
    if ($this->getLocked()) {
      return;
    }

    $is_pathauto = FALSE;

    // Detect if we have a Pathauto field first - and handle accordingly.
    // If the cell does not have an alias defined, it will get its alias from
    // the pathauto pattern.
    $field_definition = $field->getFieldDefinition();
    $type_definition = \Drupal::service('plugin.manager.field.field_type')
      ->getDefinition($field_definition->getType());
    $type_properties = $type_definition['class']::propertyDefinitions(
      $field_definition->getFieldStorageDefinition()
    );
    if (isset($type_properties['pathauto'])) {
      $is_pathauto = TRUE;
    }

    $value = $cell->getValue();
    if (!$value) {
      $value = NULL;
    }
    $field->alias = $value;

    if ($is_pathauto) {
      $field->pathauto = $value ? 0 : 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toCellValue($field) {
    // Detect if we have a Pathauto field first - and handle accordingly.
    // If the field does not have an alias defined, it will get its alias from
    // the pathauto pattern. We do not export a value in that case.
    $field_definition = $field->getFieldDefinition();
    $type_definition = \Drupal::service('plugin.manager.field.field_type')
      ->getDefinition($field_definition->getType());
    $type_properties = $type_definition['class']::propertyDefinitions(
      $field_definition->getFieldStorageDefinition()
    );
    if (isset($type_properties['pathauto'])) {
      return !$field->isEmpty() && !$field->pathauto ? $field->alias : '';
    }

    return $field->isEmpty() ? '' : $field->alias;
  }

}
