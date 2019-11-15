<?php

namespace Drupal\commerce_sheets_product\Plugin\CommerceSheets\EntityFormat;

use Drupal\commerce_sheets\EntityFormat\ContentEntityFormatBase;

/**
 * Provides a format plugin for products with their variations.
 *
 * @CommerceSheetsEntityFormat(
 *   id = "product_with_variations",
 *   label = @Translation("Product with variations")
 * )
 */
class ProductWithVariations extends ContentEntityFormatBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $excluded_properties = array_merge(
      parent::defaultConfiguration()['excluded_properties']['names'],
      ['variations']
    );

    return [
      'entity_type_id' => 'commerce_product',
      'excluded_properties' => [
        'names' => $excluded_properties,
      ],
      'associated_entities' => [
        'type' => 'entity_reference',
        'field' => 'variations',
        'format' => [
          'entity_type_id' => 'commerce_product_variation',
        ],
      ],
    ] + parent::defaultConfiguration();
  }

}
