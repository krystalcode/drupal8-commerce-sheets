<?php

namespace Drupal\commerce_sheets_product\Plugin\Action;

use Drupal\commerce_sheets\Action\ExportBase;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Exports one or more products to a spreadsheet file.
 *
 * @Action(
 *   id = "commerce_sheets_export_product",
 *   label = @Translation("Export selected product"),
 *   type = "commerce_product"
 * )
 */
class ExportProduct extends ExportBase {

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
        'commerce_sheets export any commerce_product',
        "commerce_sheets export any $type commerce_product",
      ],
      'OR'
    );
  }

  /**
   * {@inheritdoc}
   *
   * @I Add validation that all given entities are of the same type and bundle
   */
  protected function validateEntities(array $entities) {}

  /**
   * {@inheritdoc}
   */
  protected function getFormatPluginId() {
    return 'product_with_variations';
  }

  /**
   * {@inheritdoc}
   */
  protected function alterFormatconfiguration(array &$format_configuration) {
    $product_type = $this->entityTypeManager
      ->getStorage('commerce_product_type')
      ->load($format_configuration['entity_bundle']);

    $format_configuration['associated_entities'] = [
      'format' => [
        'entity_bundle' => $product_type->getVariationTypeId(),
      ],
    ];
  }

}
