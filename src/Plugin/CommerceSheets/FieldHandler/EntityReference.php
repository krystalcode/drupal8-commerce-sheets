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
  public function toCellValue($field) {
    $values = [];

    foreach ($field->referencedEntities() as $entity) {
      $values[] = $entity->label() . ' (' . $entity->id() . ')';
    }

    return implode(', ', $values);
  }

}
