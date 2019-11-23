<?php

namespace Drupal\commerce_sheets_product\Plugin\Action;

use Drupal\commerce_sheets\Action\ExportBase;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Exports one or more product attribute values to a spreadsheet file.
 *
 * @Action(
 *   id = "commerce_sheets_export_product_attribute_value",
 *   label = @Translation("Export selected attribute value"),
 *   type = "commerce_product_attribute_value"
 * )
 */
class ExportAttributeValue extends ExportBase {

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
        'commerce_sheets export any commerce_product_attribute_value',
        "commerce_sheets export any $type commerce_product_attribute_value",
      ],
      'OR'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntities(array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatPluginId() {
    return 'content_entity';
  }

  /**
   * {@inheritdoc}
   */
  protected function alterFormatconfiguration(array &$format_configuration) {
  }

}
