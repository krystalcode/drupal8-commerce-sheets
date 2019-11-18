<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerBase;

/**
 * Provides a handler plugin for entity reference fields.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReference extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function fromCellGetValue($cell) {
    $values = $cell->getValue();
    if (!$values) {
      return;
    }

    $ids = array_map(
      function ($value) {
        $id = NULL;

        // The value should be in the format "label (entity id)'; match the ID
        // from inside the parentheses.
        if (preg_match("/.+\s\(([^\)]+)\)/", $value, $matches)) {
          $id = $matches[1];
        }

        return $id;
      },
      explode(',', $values)
    );

    return array_filter($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function fromCellToField($cell, $field) {
    if ($this->getLocked()) {
      return;
    }

    $ids = $this->fromCellGetValue($cell);
    if (!$ids) {
      $field->setValue(NULL);
    }

    $field->setValue($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function toCellValue($field) {
    $values = [];

    foreach ($field->referencedEntities() as $entity) {
      $values[] = $entity->label() . ' (' . $entity->id() . ')';
    }

    return implode(', ', $values);
  }

}
