<?php

namespace Drupal\commerce_sheets\Plugin\CommerceSheets\FieldHandler;

/**
 * Provides a handler plugin for bundle fields.
 *
 * That is, the field that indicates the bundle of an entity.
 *
 * @CommerceSheetsFieldHandler(
 *   id = "bundle",
 *   label = @Translation("Bundle"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class Bundle extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // We never allow the bundle of an entity to be changed.
    return [
      'locked' => TRUE,
    ] + parent::defaultConfiguration();
  }

}
