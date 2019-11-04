<?php

namespace Drupal\commerce_sheets_product\Plugin\Action;

use Drupal\commerce_sheets\Action\ExportBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Exports one or more product variations to a spreadsheet file.
 *
 * @Action(
 *   id = "commerce_sheets_export_product_variation",
 *   label = @Translation("Export selected variation"),
 *   type = "commerce_product_variation"
 * )
 */
class ExportVariation extends ExportBase {

  /**
   * {@inheritdoc}
   */
  public function access(
    $object,
    AccountInterface $account = NULL,
    $return_as_object = FALSE
  ) {
    $type = $object->bundle();

    return AccessResult::allowedIfHasPermissions(
      $account,
      [
        'commerce_sheets export any commerce_product_variation',
        "commerce_sheets export any $type commerce_product_variation",
      ],
      'OR'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntities(array $entities) {}

  /**
   * {@inheritdoc}
   */
  protected function filterBaseFields(
    array $field_definitions,
    array $blacklisted_fields = []
  ) {
    return $this->filterFields(
      parent::filterBaseFields($field_definitions),
      ['product_id']
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function sortFields(array $field_definitions) {
    $read_only_field_names = [
      'sku',
      'type',
      'variation_id',
    ];

    // @I Sort fields to display Variation ID and Type first, then SKU
    $sorted_field_definitions = parent::sortFields(
      $field_definitions,
      $read_only_field_names
    );

    // Put attribute fields at the end.
    $attribute_field_definitions = array_filter(
      $sorted_field_definitions,
      function ($field_name) {
        return strpos($field_name, 'attribute_') === 0;
      },
      ARRAY_FILTER_USE_KEY
    );

    $non_attribute_field_definitions = array_filter(
      $sorted_field_definitions,
      function ($field_name) {
        return strpos($field_name, 'attribute_') !== 0;
      },
      ARRAY_FILTER_USE_KEY
    );

    return $non_attribute_field_definitions + $attribute_field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldPlugin($field_definition) {
    switch ($field_definition->getName()) {
      case 'variation_id':
        return $this->createFieldPlugin('integer', TRUE);

      case 'sku':
        return $this->createFieldPlugin('text', TRUE);
    }

    return parent::getFieldPlugin($field_definition);
  }

}
