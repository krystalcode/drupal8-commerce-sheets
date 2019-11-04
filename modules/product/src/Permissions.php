<?php

namespace Drupal\commerce_sheets_product;

use Drupal\commerce_product\Entity\ProductTypeInterface;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface as VariationTypeInterface;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for exporting products of different types.
 */
class Permissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $variationTypeStorage;

  /**
   * Constructs a new Permissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation
  ) {
    $this->productTypeStorage = $entity_type_manager
      ->getStorage('commerce_product_type');
    $this->variationTypeStorage = $entity_type_manager
      ->getStorage('commerce_product_variation_type');
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Returns an array of product type permissions.
   *
   * @return array
   *   An array of permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function productPermissions() {
    $permissions = [
      'commerce_sheets export any commerce_product' => [
        'title' => $this->t('Export any product of any type'),
      ],
    ];

    // Generate export permissions for all product types.
    foreach ($this->productTypeStorage->loadMultiple() as $type) {
      $permissions += $this->productTypePermissions($type);
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
  protected function productTypePermissions(ProductTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "commerce_sheets export any $type_id commerce_product" => [
        'title' => $this->t('%type_name: Export any product', $type_params),
      ],
    ];
  }

  /**
   * Returns an array of product variation type permissions.
   *
   * @return array
   *   An array of permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function variationPermissions() {
    $permissions = [
      'commerce_sheets export any commerce_product_variation' => [
        'title' => $this->t('Export any product variation of any type'),
      ],
    ];

    // Generate export permissions for all variation types.
    foreach ($this->variationTypeStorage->loadMultiple() as $type) {
      $permissions += $this->variationTypePermissions($type);
    }

    return $permissions;
  }

  /**
   * Returns a list of permissions for the given product variation type.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $type
   *   The product variation type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function variationTypePermissions(VariationTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "commerce_sheets export any $type_id commerce_product_variation" => [
        'title' => $this->t(
          '%type_name: Export any product variation',
          $type_params
        ),
      ],
    ];
  }

}
