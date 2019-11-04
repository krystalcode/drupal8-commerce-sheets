<?php

namespace Drupal\commerce_sheets_product\Plugin\Action;

use Drupal\commerce_sheets\Action\ExportBase;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Exports one or more products to a spreadsheet file.
 *
 * @Action(
 *   id = "commerce_sheets_export_product",
 *   label = @Translation("Export selected product"),
 *   type = "commerce_product"
 * )
 *
 * @I Review which functions should be included in the base class.
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
   */
  protected function validateEntities(array $entities) {}

  /**
   * {@inheritdoc}
   */
  protected function filterBundleFields(array $field_definitions) {
    return $this->filterFields(
      $field_definitions,
      ['variations']
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function sortFields(array $field_definitions) {
    $read_only_field_names = [
      'product_id',
      'type',
    ];
    return parent::sortFields($field_definitions, $read_only_field_names);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldPlugin($field_definition) {
    switch ($field_definition->getName()) {
      case 'product_id':
        return $this->createFieldPlugin('integer', TRUE);
    }

    return parent::getFieldPlugin($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function hasSecondaryEntity() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondaryEntityPluginId() {
    return 'commerce_sheets_export_product_variation';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondaryEntityTypeId() {
    return 'commerce_product_variation';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondaryEntityTypeLabel() {
    return 'Product Variation';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondaryEntityBundleId(EntityInterface $entity) {
    return $this->entityTypeManager
      ->getStorage('commerce_product_type')
      ->load($entity->bundle())
      ->getVariationTypeId();
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondaryEntityFieldName() {
    return 'variations';
  }

}
