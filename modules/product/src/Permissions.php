<?php

namespace Drupal\commerce_sheets_product;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductTypeInterface;

/**
 * Provides dynamic permissions for exporting products of different types.
 */
class Permissions {

  use StringTranslationTrait;

  /**
   * Returns an array of product type permissions.
   *
   * @return array
   *   An array of permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function permissions() {
    $permissions = [
      'commerce_sheets export any commerce_product' => [
        'title' => $this->t('Export any product of any type')
      ]
    ];

    // Generate export permissions for all product types.
    foreach (ProductType::loadMultiple() as $type) {
      $permissions += $this->buildPermissions($type);
    }

    return $permissions;
  }

  /**
   * Returns a list of permissions for the given product type.
   *
   * @param \Drupal\commerce_product\Entity\ProductTypeInterface $type
   *   The product type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ProductTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "commerce_sheets export any $type_id commerce_product" => [
        'title' => $this->t('%type_name: Export any product', $type_params),
      ],
    ];
  }

}
